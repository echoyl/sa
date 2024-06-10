<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class Content extends BaseField
{

    public function encode($options = [])
    {
        $name = $this->name;
        $data = $this->config['data'];
        $val = $options['val'];
        $origin_val = $options['origin_val'];
        
        $src_val = $this->getSrc($val);
        $origin_src_val = $this->getSrc($origin_val);

        $this->diffFileVal($src_val,$origin_src_val);

        $val = $this->replace($val);
        
        $data[$name] = $val;

        return $data;
    }

    public function replace($content)
    {
        $matches = $this->getMatch($content);

        $replace = rtrim(env('APP_URL'),'/').'/storage/';

        $search = $replace.$this->tmp_prefix;

        foreach($matches as $src)
        {
            $content = str_replace($search,$replace,$content);
        }
        return $content;
    }

    public function getMatch($content)
    {
        $pattern = '/<img[^>]*src=[\'"]([^\'"]+)[\'"]/i';

        preg_match_all($pattern, $content, $matches);

        return $matches[1];
    } 

    public function getSrc($content)
    {
        $data = [];

        $matches = $this->getMatch($content);

        $prefix = rtrim(env('APP_URL'),'/').'/storage/';

        foreach($matches as $src)
        {
            if(strpos($src,$prefix) !== false)
            {
                $data[] = [
                    'value'=>str_replace($prefix,'',$src),
                ];
            }
            
        }

        return $data;
    }

}