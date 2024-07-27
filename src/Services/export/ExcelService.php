<?php
namespace Echoyl\Sa\Services\export;

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
     * @var array 需要合并的行 [['A',1,2,'内容']]
     */
    var $merges = [];
    
    public function __construct($config)
    {
        $config['filename'] = date("YmdHis").'.xlsx';
        $this->config = $config;
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
            $data = [];
            
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

                if (is_array($index)) {
                    //数组index
                    $_val = HelperService::getFromObject($val, $index);
                    $_val = $_val ?: $default;
                } else {
                    //字符串 直接读取
                    $_val = $val[$index] ?? $default;
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
                $data[] = $_val;
                
            }
            $last_val_arr = $data;
            $_data[] = $data;
        }
        $this->merges = $merges;
        return $_data;
    }
}
