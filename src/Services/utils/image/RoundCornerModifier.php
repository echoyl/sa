<?php

namespace Echoyl\Sa\Services\utils\image;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;

class RoundCornerModifier implements ModifierInterface
{
    protected $radius;

    public function __construct(int $radius)
    {
        $this->radius = $radius;
    }

    public function apply(ImageInterface $image): ImageInterface
    {
        $imagick = $image->core()->native();
        $imagick->roundCorners($this->radius, $this->radius);

        return $image;
    }
}
