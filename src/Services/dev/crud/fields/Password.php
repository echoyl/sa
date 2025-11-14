<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Echoyl\Sa\Services\HelperService;

class Password extends BaseField
{
    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];
        $val = $options['val'];
        $isset = $options['isset'];

        if ($isset && $val) {
            $data[$name] = HelperService::pwd($val);
        }

        return $data;
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $name = $this->name;

        if ($options['isset']) {
            unset($data[$name]);
        }

        return $data;
    }
}
