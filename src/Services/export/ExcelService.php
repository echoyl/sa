<?php
namespace Echoyl\Sa\Services\export;

use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

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
    
    public function __construct($config)
    {
        $config['filename'] = date("YmdHis").'.xlsx';
        $this->config = $config;
    }

    
    public function export($data,$formatData = false)
    {
        $v = new Vtiful($this->config);

        $data = $this->parseData($data,$formatData);

        return $v->export($data);
    }

    public function parseData($data,$formatData)
    {
        $head = Arr::get($this->config,'head');

        $columns = Arr::get($head,'columns',[]);

        $_data = [];
        foreach ($data as $datakey => $val) 
        {
            if($formatData)
            {
                $val = $formatData($val);
            }
            $data = [];
            foreach ($columns as $col) {
                $index = $col['key']??$col['cname'];
                $type = $col['type'] ?? '';
                $add_t = $col['t'] ?? '';
                $default = $col['default'] ?? '';
                $setting = Arr::get($col,'setting',[]);

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
                $data[] = $_val;
            }
            $_data[] = $data;
        }
        return $_data;
    }
}
