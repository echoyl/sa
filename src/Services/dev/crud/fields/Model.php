<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

/**
 * 占位
 */
class Model extends BaseField
{
    public function encode($options = [])
    {
        $isset = $options['isset'];
        $val = '__unset';

        return $this->getData($val, $isset);
    }

    public function decode($options = [])
    {

        $val = $options['val'];
        $col = $this->config['col'];
        $deep = $options['deep'];
        $isset = $options['isset'];
        if (isset($col['class'])) {
            $cls = new $col['class'];
            $cls_p_c = $cls->getParseColumns();
            if (! empty($cls_p_c) && $deep <= 3 && $isset) {
                // model类型只支持1级 多级的话 需要更深层次的with 这里暂时不实现了
                // 思路 需要在生成controller文件的 with配置中 继续读取关联模型的关联
                // $this->parseWiths($val,$cls_p_c);
                // $this->parseData($val,$in,$from,$cls_p_c,$deep+1);
            }
        }

        return $this->getData($val);
    }
}
