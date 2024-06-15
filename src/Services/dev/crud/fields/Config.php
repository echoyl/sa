<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Echoyl\Sa\Services\WebMenuService;

class Config extends BaseField
{

    public function decode($options = [])
    {

        $val = $options['val'];
        
        if($val)
        {
            $wms = new WebMenuService;
            $val = $wms->getSpecs($val,true);
        }

        return $this->getData($val);
    }
}