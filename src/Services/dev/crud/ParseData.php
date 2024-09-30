<?php
namespace Echoyl\Sa\Services\dev\crud;

use Illuminate\Support\Arr;

class ParseData
{
    var $model_class;//当前模型class
    //由于这些参数设置在了控制器，导致无法获取。暂时不用。先只适用于relation处理
    //之后需要将控制器的部分属性转移至模型
    var $params = [];

    public function __construct($class,$params = [])
    {
        $this->model_class = $class;
        $this->params = $params;
    }

    public function getParam($name,$default = [])
    {
        return Arr::get($this->params,$name,$default);
    }

    public function make(&$data, $in = 'encode', $from = 'detail',$deep = 1)
    {
        $unsetNames = [];

        $model = new $this->model_class;

        $parse_columns = [];

        $can_be_null_columns = $this->getParam('can_be_null_columns');

        if(method_exists($model,'getParseColumns'))
        {
            $parse_columns = $model->getParseColumns();
        }

        foreach ($parse_columns as $col) {
            $name = $col['name'];
            $type = $col['type'];
            $encode = $in == 'encode'?true:false;

            $isset = array_key_exists($name,$data) ? true : false;
            if (!$isset && $from == 'update') {
                //更新数据时 不写入默认值
                //d($this->parse_columns,$this->can_be_null_columns);
                if(!in_array($name,$can_be_null_columns))
                {
                    continue;
                }
            }
            $col['default'] = $col['default']??"";

            $val = $isset ? $data[$name] : $col['default'];
            if(!$isset)
            {
                $check_category_field = $this->checkCategoryField($name,$col['default']);
                //$val = $check_category_field['array_val'];
                if($check_category_field['array_val'])
                {
                    $data[$name] = $check_category_field['array_val'];
                    $val = $check_category_field['array_val'];
                }
            }
            if($type == 'model')
            {
                if($encode)
                {
                    //提交数据时 不需要处理 将数据删除
                    $val = '__unset';
                    if($isset)
                    {
                        unset($data[$name]);
                    }
                }else
                {
                    $cls = new $col['class'];
                    if($deep <= 3 && $isset && $val)
                    {
                        //model类型只支持1级 多级的话 需要更深层次的with 这里暂时不实现了
                        //思路 需要在生成controller文件的 with配置中 继续读取关联模型的关联
                        //20240930 更深一层的 parseWiths 暂时取消掉
                        //$this->parseWiths($val,$cls_p_c);
                        (new ParseData($cls))->make($val,$in,$from,$deep+1);
                    }
                    $data[$name] = $val;
                }
            }else
            {
                $config = [
                    'data'=>$data,'col'=>$col,
                ];
                $cs = new CrudService($config);
                $data = $cs->make($type,[
                    'encode'=>$encode,
                    'isset'=>$isset,
                    'from'=>$from,
                    'deep'=>$deep
                ]);
            }
        }
        if(isset($data['originData']))
        {
            unset($data['originData']);
        }
        return $unsetNames;
    }

    public function checkCategoryField($name,$default = '')
    {
        $category_fields = $this->getParam('category_fields');

        $field = collect($category_fields)->first(function($q) use($name){
            return $q['field_name'] == $name;
        });
        $orval = request($name);//原始请求值
        $rval = $lval = $array_val = $default;

        if($field)
        {
            $rval = request($field['request_name'],$default);//预设请求值
            if($rval)
            {
                if(is_array($rval))
                {
                    $len = count($rval);
                    $lval = $rval[$len - 1];
                }else
                {
                    $lval = $rval;
                }
                if(!$orval)
                {
                    //未传数据 自动读取映射字段
                    $array_val = is_numeric($rval)?[$rval]:(is_array($rval)?$rval:json_decode($rval,true));
                }
            }
            
        }
        
        
        return [
            'search_val'=>$orval?:$rval,//处理过后搜索值
            'last_val'=>$lval,//分类的id值 数字类型
            'array_val'=>$array_val
        ];
    }
}