<?php
namespace Echoyl\Sa\Http\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use League\Glide\Responses\LaravelResponseFactory;
use League\Glide\ServerFactory;

class ImageController extends Controller
{
    public function show(Filesystem $filesystem, $path)
    {

        $size_arr = config('sa.imageSize');

        $size = request('p');

        //d($size_arr,$size_arr[$size]);

        if(!isset($size_arr[$size]))
        {
            return response()->file(public_path('storage/'.$path));
        }

        $server = ServerFactory::create([
            'response' => new LaravelResponseFactory(app('request')),
            'source' => $filesystem->getDriver(),
            'cache' => $filesystem->getDriver(),
            'cache_path_prefix' => '.cache',
            'base_url' => 'img',
            'presets'=>$size_arr
        ]);

        return $server->getImageResponse($path, ['p'=>$size]);
    }
}
