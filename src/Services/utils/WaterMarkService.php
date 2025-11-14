<?php

namespace Echoyl\Sa\Services\utils;

use Echoyl\Sa\Services\utils\ImageService as Image;
use Illuminate\Support\Arr;

class WaterMarkService
{
    public $path;

    public $setting;

    public function __construct($path, $setting = [])
    {
        $this->setting = $setting;
        $this->path = $path;
    }

    public function create()
    {
        $watermark = $this->setting;

        $enable = Arr::get($watermark, 'enable', false);

        if (! $enable) {
            return;
        }

        $type = Arr::get($watermark, 'type', 'text');

        if ($type == 'text') {
            $this->text();
        } elseif ($type == 'image') {
            $this->image();
        }

    }

    public function image()
    {
        $setting = $this->setting;
        $path = $this->path;
        $image = Arr::get($setting, 'image', []);

        $url = Arr::get($image, 'url');

        if (! $url || ! is_array($url) || empty($url)) {
            return;
        }
        $img = Image::read($path);
        $watermark_path = storage_path('app/public/'.$url[0]['value']);

        $align = Arr::get($setting, 'align', 'left');
        $valign = Arr::get($setting, 'valign', 'bottom');
        $offset_x = Arr::get($setting, 'offset_x', 0);
        $offset_y = Arr::get($setting, 'offset_y', 0);
        $opacity = Arr::get($setting, 'opacity', 100);

        $angle = Arr::get($setting, 'angle', 0);

        $position = implode('-', [$align, $valign]);

        $watermark_image = Image::read($watermark_path);

        if ($angle) {
            $watermark_image->manager->rotate($angle);
        }

        $img->place($watermark_image, $position, $offset_x, $offset_y, $opacity)->save($path);

    }

    /**
     * 图片生成文字水印
     *
     * @param [string] $path 图片路径地址
     * @param [array] $setting 水印设置
     * @return void
     */
    public function text()
    {
        $setting = $this->setting;
        $text = Arr::get($setting, 'text', []);

        $content = Arr::get($text, 'content');

        if (! $content) {
            return;
        }

        $color = Arr::get($text, 'color', '#ffffff');
        $fontsize = Arr::get($text, 'fontsize', '12');
        // d($text);
        // d($fontsize);
        $path = $this->path;
        $img = Image::read($path);

        $align = Arr::get($setting, 'align', 'left');
        $valign = Arr::get($setting, 'valign', 'bottom');
        $offset_x = Arr::get($setting, 'offset_x', 0);
        $offset_y = Arr::get($setting, 'offset_y', 0);

        $angle = Arr::get($setting, 'angle', 0);

        $position = implode('-', [$align, $valign]);

        $image_size = $img->align($position, $offset_x, $offset_y);
        // d($image_size);

        $img->text($content, $image_size[0], $image_size[1], function ($font) use ($color, $fontsize, $align, $valign, $angle) {
            $font->file(storage_path('app/public/font/msyhbd.ttc'));
            $font->color($color);
            $font->size(intval($fontsize));
            $font->align($align);
            $font->valign($valign);
            if ($angle) {
                $font->angle($angle);
            }
        })->save($path);

    }
}
