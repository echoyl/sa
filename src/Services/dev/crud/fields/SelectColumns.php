<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class SelectColumns extends BaseField
{
    public function encode($options = [])
    {
        $isset = $options['isset'];

        $val = '__unset';

        return $this->getData($val,$isset);
    }

}