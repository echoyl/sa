<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

/**
 * modalselect的单选模式，多选的话使用ModalSelects
 */
class ModalSelect extends BaseField
{
    public function encode($options = [])
    {

        $val = $options['val'];
        $col = $this->config['col'];
        $isset = $options['isset'];
        $id_name = $col['value']??'id';
        if($isset && is_array($val))
        {
            if(empty($val))
            {
                $val = 0;
            }else
            {
                if(isset($val[$id_name]))
                {
                    $val = $val[$id_name];
                }else
                {
                    //如果传输的数据是数组 这里暂时数据库中只存储逗号拼接的id值  如果之后需要关联模型处理再说
                    $_v = [];
                    foreach($val as $v)
                    {
                        if(isset($v['data']) && isset($v['data'][$id_name]))
                        {
                            $_v[] = $v['data'][$id_name];
                        }
                    }
                    if(!empty($_v))
                    {
                        $val = implode(',',$_v);
                    }else
                    {
                        $val = '';
                    }
                }
            }
            
        }

        return $this->getData($val);
    }

}