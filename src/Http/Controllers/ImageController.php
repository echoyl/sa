<?php

namespace Echoyl\Sa\Http\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\App;
use League\Glide\Responses\LaravelResponseFactory;
use League\Glide\Responses\SymfonyResponseFactory;
use League\Glide\ServerFactory;

class ImageController extends Controller
{
    public function show(Filesystem $filesystem, $path)
    {

        $size_arr = config('sa.imageSize');

        $size = request('p');

        // 解析真实路径并校验目录边界，避免通过 ../ 或软链读取 storage 目录之外的文件
        $base = realpath(public_path('storage'));
        $full = realpath(public_path('storage/'.$path));

        if (! $base || ! $full || ! is_file($full) || ! str_starts_with($full, $base.DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        if (! isset($size_arr[$size]) || ! class_exists('League\Glide\ServerFactory')) {
            return response()->file($full);
        }

        $less11 = version_compare(App::version(), '11') < 0;

        $server = ServerFactory::create([
            'response' => $less11 ? new LaravelResponseFactory(app('request')) : new SymfonyResponseFactory(app('request')),
            'source' => $filesystem->getDriver(),
            'cache' => $filesystem->getDriver(),
            'cache_path_prefix' => '.cache',
            'base_url' => 'img',
            'presets' => $size_arr,
        ]);

        return $server->getImageResponse($path, ['p' => $size]);
    }
}
