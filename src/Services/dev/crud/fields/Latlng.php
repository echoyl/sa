<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Illuminate\Support\Arr;

class Latlng extends BaseField
{
    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];
        $val = $options['val'];
        $isset = $options['isset'];

        if($isset)
        {
            $lat = Arr::get($val,0,'');
            $lng = Arr::get($val,1,'');
            $data['lat'] = is_null($lat)?'':$lat;
            $data['lng'] = is_null($lng)?'':$lng;
            
            if(!in_array($name,['lat','lng']))
            {
                //如果用了其它字段需要将该字段移除
                $val = '__unset';
                unset($data[$name]);
            }
        }

        return $data;
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $name = $this->name;
        
        if(isset($data['lat']) && $data['lat'])
        {
            $data[$name] = [$data['lat'],$data['lng']];
        }

        return $data;
    }
}