<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class Link extends BaseField
{
    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];

        $isset = $options['isset'];

        if($isset)
        {
            unset($data[$name]);
        }

        return $data;
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $from = $options['from'];
        $val = $options['val'];
        $col = $this->config['col'];
        $isset = $options['isset'];
        
        if ($from == 'list') 
        {
            $uris = [];
            if(isset($col['uri']) && is_array($col['uri']))
            {
                foreach($col['uri'] as $uri)
                {
                    $uris[] = implode('=',[$uri[0],$data[$uri[1]]]);
                }
            }
            $val = [
                'title'=>$val,
                'href'=>implode('?',[$col['path'],implode('&',$uris)])
            ];
        }else
        {
            $val = '__unset';
        }

        return $this->getData($val,$isset);
    }
}