<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Echoyl\Sa\Services\dev\design\FormService;
use Echoyl\Sa\Services\dev\utils\Utils;
use Illuminate\Support\Arr;

class Config extends BaseField
{

    public function decode($options = [])
    {
        $val = $options['val'];
        
        if($val)
        {
            $dev_menu = $this->getItemConfig();
            $val = is_string($val)?json_decode($val,true):$val;
            $val = Utils::parseImageInPage($val,$dev_menu,false,'decode');
        }else
        {
            $val = '{}';   
        }

        return $this->getData($val);
    }

    public function encode($options = [])
    {
        $val = $options['val'];
        if($val && $val != '{}')
        {
            $origin_val = $options['origin_val'];
            //$dev_menu = request('dev_menu');
    
            $dev_menu = $this->getItemConfig();
            $val = Utils::parseImageInPage($val,$dev_menu,$origin_val);

            $val = json_encode($val);
        }

        return $this->getData($val);
    }

    /**
     * 获取字段配置
     *
     * @return void
     */
    public function getItemConfig()
    {
        $menu = request('dev_menu');
        //这里通过name获取当前字段的配置信息
        $desc = Arr::get($menu,'form_config');
        
        if(!$desc)
        {
            return [];
        }

        $desc = json_decode($desc,true);

        $tabs = $desc['tabs'];

        $fs = new FormService(0);

        [,$data] = $fs->getColumnIndex($tabs,is_string($this->name)?[$this->name]:$this->name,'key');

        return Arr::get($data,'props.fieldProps.config');
    }
}