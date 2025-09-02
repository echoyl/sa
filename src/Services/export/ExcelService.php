<?php
namespace Echoyl\Sa\Services\export;

use Echoyl\Sa\Services\dev\crud\fields\Pca;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;
use \Vtiful\Kernel\Excel;

class ExcelService
{
    // public static function toArray($file)
    // {
    //     $data = Excel::toArray(new Import,$file);
    //     return $data;
    // }

    // public static function store($obj,$file)
    // {
    //     return Excel::store($obj,$file);
    // }
    var $config;
    /**
     * @var array 列表中返回的search数据。这里内置一个渲染数据的方法，当search中存在 name+s的数据时 自动获取数据
     */
    var $search = [];
    /**
     * @var array 需要合并的行 [['A',1,2,'内容']]
     */
    var $merges = [];
    
    public function __construct($config,$search = [])
    {
        $config['filename'] = date("YmdHis").'.xlsx';
        $this->config = $config;
        $this->tableConfigToColumns();
        foreach($search as $key=>$val)
        {
            //$_search = [];
            if(!is_array($val))
            {
                continue;
            }
            $_search = $this->setDeepData($val);
            // foreach($val as $k=>$v)
            // {
            //     if(!is_numeric($k))
            //     {
            //         continue;
            //     }
            //     $value = Arr::get($v,'value',Arr::get($v,'id'));
            //     $label = Arr::get($v,'label',Arr::get($v,'title'));
            //     if($value !== null)
            //     {
            //         $_search[$value] = $label;
            //     }
            // }
            if(!empty($_search))
            {
                $this->search[$key] = $_search;
            }
        }
    }

    /**
     * 格式化search数据格式
     *
     * @param [type] $datas
     * @param string $prefix_label 前缀标签 用于多级数据的拼接
     * @return void
     */
    public function setDeepData($datas,$prefix_label = '')
    {
        $_search = [];
        foreach($datas as $k=>$v)
        {
            if(!is_numeric($k))
            {
                continue;
            }
            $children = Arr::get($v,'children');
            $value = Arr::get($v,'value',Arr::get($v,'id'));
            $label = Arr::get($v,'label',Arr::get($v,'title'));
            
            $label = $prefix_label?implode(' - ',[$prefix_label,$label]):$label;

            if($value !== null)
            {
                if($children)
                {
                    $_search[$value] = [
                        'children'=>$this->setDeepData($children,$label),
                        'label'=>$label,
                    ];
                }else
                {
                    $_search[$value] = $label;
                }
            }
        }
        return $_search;
    }

    /**
     * 根据index获取数据，如果没有index则检测是否存在children字段信息
     *
     * @param [type] $datas
     * @param [type] $index
     * @return void
     */
    public function getDeepData($datas,$index,$children = 'children')
    {
        //d($datas,$index);
        if(isset($datas[$index]))
        {
            return $datas[$index];
        }else
        {
            foreach($datas as $data)
            {
                if(isset($data[$children]))
                {
                    return $this->getDeepData($data[$children],$index,$children);
                }
            }
            
        }
        return false;
    }

    public function tableConfigToColumns()
    {
        $columns = Arr::get($this->config,'head.columns');
        if($columns)
        {
            //已设置columns
            return;
        }
        $desc = HelperService::json_validate(Arr::get($this->config,'dev_menu.desc'));
        if(!$desc)
        {
            return;
        }

        $table_config = Arr::get($desc,'tableColumns',[]);

        $columns = collect($table_config)->filter(function($val){
            $valtype = Arr::get($val,'valueType');
            if(in_array($valtype,['option']))
            {
                return false;
            }
            $hide_in_table = Arr::get($val,'hideInTable');
            if($hide_in_table)
            {
                return false;
            }
            return isset($val['dataIndex']) && $val['dataIndex'];
        })->map(function($val){
            //检测hasone类型中如果dataindex是 xxx_id类型则转换
            $dont_set_types = ['cascader'];//如果是cascader类型的不进行转换
            $valueType = Arr::get($val,'valueType');
            if(is_string($val['dataIndex']) && strpos($val['dataIndex'],'_id') !== false && !in_array($valueType,$dont_set_types))
            {
                $data_name = str_replace('_id','',$val['dataIndex']);
                //获取lable名称
                $label_name = Arr::get($val,'fieldProps.fieldNames.label','title');
                $val['dataIndex'] = [$data_name,$label_name];
            }
            return ['key'=>$val['dataIndex'],'title'=>$val['title'],'type'=>$valueType];
        })->values();

        //d($columns);

        Arr::set($this->config,'head.columns',$columns);

        return;
    }

    
    public function export($data,$formatData = false)
    {
        $v = new Vtiful($this->config);

        $data = $this->parseData($data,$formatData);

        return $v->export($data,$this->merges);
    }

    

    public function parseData($data,$formatData)
    {
        $head = Arr::get($this->config,'head');

        $columns = Arr::get($head,'columns',[]);
        //d($columns);
        $_data = [];
        $merges = [];
        $last_val_arr = [];
        $last_col_row = [];
        foreach ($data as $datakey => $val) 
        {
            if($formatData)
            {
                $val = $formatData($val);
            }
            $data_item = [];
            
            foreach ($columns as $colkey=>$col) {
                $index = $col['key']??$col['cname'];
                $type = $col['type'] ?? '';
                $add_t = $col['t'] ?? '';
                $default = $col['default'] ?? '';
                $setting = Arr::get($col,'setting',[]);
                $row_merge = Arr::get($setting,'row_merge',false);//是否需要检测该列是否要合并

                if(is_string($index) && strpos($index,'.') !== false)
                {
                    $index = explode('.',$index);
                }
                $index = Utils::uncamelize($index);
                if (is_array($index)) {
                    //数组index
                    $_val = HelperService::getFromObject($val, $index);
                    $_val = $_val || $_val === 0 ?$_val: $default;//如果值为0需要保留下来
                } else {
                    //字符串 直接读取
                    $_val = $val[$index] ?? $default;
                }

                //读取search数据
                $names = (is_array($index)?implode('.',$index):$index).'s';
                if(isset($this->search[$names]))
                {
                    //修改为解析以逗号为分隔字符串信息
                    $_vals = explode(',',$_val);
                    $_val_arr = [];
                    //d($names,$this->search);
                    foreach($_vals as $_vals_val)
                    {
                        $deep_val = $this->getDeepData($this->search[$names],$_vals_val);
                        if($deep_val !== false && !is_array($deep_val))
                        {
                            $_val_arr[] = $deep_val;
                        }
                    }
                    $_val = implode(',',$_val_arr);
                }

                if ($type) {
                    switch ($type) {
                        case 'date':
                            $dateformat = Arr::get($setting,'dateformat','Y-m-d');
                            $_val = date($dateformat, strtotime($_val));
                            break;
                        case 'int':
                            break;
                        case 'price':
                            $_val = intval($_val) / 100;
                            if(!$_val)
                            {
                                $_val = '0';
                            }
                            break;
                        case 'enum':
                            $_val = $index['enum'][$_val] ?? $default;
                            break;
                        case 'index':
                            $_val = ++$datakey;
                            break;
                        case 'pca':
                            //多层数据的解析 获取去除最后一个元素的数组
                            if(is_array($index) && count($index) > 1)
                            {
                                array_pop($index);
                                $config = ['data'=>HelperService::getFromObject($val, $index)];
                            }else
                            {
                                $config = [
                                    'data'=>$val
                                ];
                            }
                            $cs = new Pca($config);
                            $_val = $cs->decodeStr();
                            break;
                        case 'uploader':
                            //如果是上传类型 导出数据不使用该值
                            $_val = '';
                            break;
                    }
                }
                if ($add_t) {
                    $_val .= "\t";
                }
                $last_val = Arr::get($last_val_arr,$colkey,false);
                if($row_merge)
                {
                    //开启了行合并
                    $column_name = Excel::stringFromColumnIndex($colkey);
                    if($last_val === $_val)
                    {
                        //和上一个数据相同表示要合并
                        $merges[] = [$column_name,$last_col_row[$colkey],$datakey,$_val];
                    }else
                    {
                        //不同重新设置开始行数
                        $last_col_row[$colkey] = $datakey;
                    }
                }
                $data_item[] = $_val;
                
            }
            $last_val_arr = $data_item;
            $_data[] = $data_item;
        }
        $this->merges = $merges;
        return $_data;
    }

    /**
     * 通过UploadedFile 获取excel数据
     *
     * @param [\Illuminate\Http\UploadedFile] $file
     * @return array
     */
    public static function getData($file)
    {
        $v = new Vtiful();

        $data = $v->getSheetData($file);

        return $data;
    }

    /**
     * 统一使用 当前service 调用 Excel中的方法
     * 
     * @param string $cellCoordinates
     * @return void
     */
    public static function columnIndexFromString(string $cellCoordinates)
    {
        return Excel::columnIndexFromString($cellCoordinates);
    }
}
