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

        //d($size_arr,$size_arr[$size]);

        if(!isset($size_arr[$size]))
        {
            return response()->file(public_path('storage/'.$path));
        }

        $less11 = version_compare(App::version(),'11') < 0;

        $server = ServerFactory::create([
            'response' => $less11?new LaravelResponseFactory(app('request')):new SymfonyResponseFactory(app('request')),
            'source' => $filesystem->getDriver(),
            'cache' => $filesystem->getDriver(),
            'cache_path_prefix' => '.cache',
            'base_url' => 'img',
            'presets'=>$size_arr
        ]);

        return $server->getImageResponse($path, ['p'=>$size]);
    }
}
