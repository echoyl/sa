<?php
namespace Echoyl\Sa\Services\utils;

use Illuminate\Support\Facades\App;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;

/**
 * Class ImageService
 * 兼容Intervention\Image旧版和新版
 * @package Echoyl\Sa\Services\utils
 */
class ImageService
{
    var $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public static function isOld()
    {
        return version_compare(App::version(),'11') < 0;
    }

    public static function read($path):self
    {
        if(self::isOld())
        {
            $manager = Image::make($path);
        }else
        {
            $manager = new ImageManager(new Driver());
            $manager = $manager->read($path);
        }
        return new self($manager); 
    }

    public function height()
    {
        if(self::isOld())
        {
            return $this->manager->getHeight();
        }else
        {
            return $this->manager->height();
        }
        
    }

    public function width()
    {
        if(self::isOld())
        {
            return $this->manager->getWidth();
        }else
        {
            return $this->manager->width();
        }
    }

    public function scale($width,$height)
    {
        if(self::isOld())
        {
            $this->manager->resize($width, $height, function ($constraint) {$constraint->aspectRatio();});
        }else
        {
            $this->manager->scale($width,$height);
        }
        return $this;
    }

    public function rotate($angle)
    {
        $this->manager->rotate($angle);
        return $this;
    }

    public function place(ImageService $place,$position,$offset_x, $offset_y,$opacity = 100)
    {
        if(self::isOld())
        {
            $this->manager->insert($place->manager, $position,$offset_x, $offset_y);
        }else
        {
            $this->manager->place($place->manager, $position,$offset_x, $offset_y,$opacity);
        }
        return $this;
    }
    
    public function align($position, $offset_x, $offset_y)
    {
        if(self::isOld())
        {
            $size = $this->manager->getSize()->align($position, $offset_x, $offset_y);
            return [$size->pivot->x,$size->pivot->y];
        }else
        {
            $size = $this->manager->size()->movePivot($position, $offset_x, $offset_y);
            return [$size->pivot()->x(),$size->pivot()->y()];
        }
    }

    public function text($text,$x,$y,$c)
    {
        if(self::isOld())
        {
            $this->manager->text($text,$x,$y,$c);
        }else
        {
            $this->manager->text($text,$x,$y,$c);
        }
        return $this;
    }

    public function save($path)
    {
        return $this->manager->save($path);
    }


}