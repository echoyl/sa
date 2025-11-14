<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Echoyl\Sa\Services\HelperService;

class AliyunVideo extends BaseField
{
    public function encode($options = [])
    {
        $val = $options['val'];
        if ($val) {
            $val = HelperService::aliyunVideoParse($val, true);
        }

        return $this->getData($val);
    }

    public function decode($options = [])
    {

        $val = $options['val'];
        $isset = $options['isset'];

        if ($val) {
            $val = HelperService::aliyunVideoParse($val, false);
        } else {
            $val = '__unset';
        }

        return $this->getData($val, $isset);
    }
}
