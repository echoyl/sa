<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Echoyl\Sa\Services\dev\design\FormService;
use Echoyl\Sa\Services\dev\utils\Utils;
use Illuminate\Support\Arr;

class Json extends BaseField
{
    public function encode($options = [])
    {
        $val = $options['val'];
        
        if(empty($val))
        {
            $val = '';
        }else
        {
            if($val == '{}')
            {
                $val = '';
            }
        }

        $origin_val = $options['origin_val'];
        
        $val = $this->diffVal($val,$origin_val,true);

        if($val && !is_string($val))
        {
            $val = json_encode($val);
        }

        return $this->getData($val);
    }

    public function decode($options = [])
    {
        $val = $options['val'];
        if($val)
        {  
            if($val == '{}')
            {
                $val = '__unset';
            }else
            {
                $val = is_string($val)?json_decode($val,true):$val;
            }
        }else{
            $val = '__unset';
        }
        $val = $this->diffVal($val,$val,false);

        $isset = $options['isset'];

        return $this->getData($val,$isset);
    }

    /**
     * 检测字段配置 将其中的图片字段数据进行处理
     *
     * @return string | array
     */
    public function diffVal($post_data,$origin_val,$encode)
    {
        if(!$post_data || is_string($post_data))
        {
            return $post_data;
        }

        $origin_val = is_string($origin_val)?json_decode($origin_val,true):$origin_val;

        $menu = request('dev_menu');
        //这里通过name获取当前字段的配置信息
        $desc = Arr::get($menu,'form_config');
        
        if(!$desc)
        {
            return $post_data;
        }

        $desc = json_decode($desc,true);

        $tabs = $desc['tabs'];

        $fs = new FormService(0);

        $name = is_string($this->name)?[$this->name]:$this->name;

        [,$data] = $fs->getColumnIndex($tabs,$name,'key');

        if($data && in_array($data['type'],['formList','saFormList']))
        {
            $columns = Arr::get($data,'props.outside.columns.0.columns',[]);
            $value_map = array_flip(Utils::$value_type_map);
            foreach($columns as $col)
            {
                $type = Arr::get($col,'valueType');//检测每列类型
                $type = $value_map[$type]??$type;
                $field = Arr::get($col,'dataIndex');
                if(!$field || !in_array($type,['image','file','tinyEditor','mdEditor']))
                {
                    continue;
                }
                
                foreach($post_data as $dk=>$pd)
                {
                    $post_data[$dk] = Utils::parseImgFields($field,$pd,[$field,$type],Arr::get($origin_val,$dk),$encode,$type);
                }
            }
        }

        return $post_data;
    }
}