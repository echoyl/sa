<?php
namespace Echoyl\Sa\Services;

use Exception;
use GuzzleHttp\Client;

class HelperService
{
    public static function picStr($pics = [])
    {
        $_pics = [];
        if(!empty($pics))
        {
            foreach($pics as $val)
            {
                $_pics[] = $val['value'];
            }
        }

        return implode(',',$_pics);
    }

    public static function picArr($pics = '')
    {
        if(!$pics)
        {
            return [];
        }
        $pics = explode(',',$pics);
        $_pics = [];
        foreach($pics as $val)
        {
            $_pics[] = [
                'url'=>tomedia($val),
                'value'=>$val
            ];
        }

        return $_pics;
    }

    public static function userContent($content)
    {
        if(is_array($content))
        {
            return $content;
        }
        return strip_tags($content);
        //$search = ['<script',"</script>"];
        //$replace = ['',''];
        //return str_replace($search,$replace,$content);
    }

    public static function list($model,$callback,$order_by = [],$where = [])
    {
        $page = webapi_request('page',1);
        $page_size = webapi_request('page_size',10);

        $m = $model->where($where);

        $count = $m->count();
        foreach($order_by as $order)
        {
            $m = $m->orderBy($order[0],$order[1]);
        }
        $list = $m->offset(($page-1)*$page_size)->limit($page_size)->get()->toArray();
        $data = [];
        foreach($list as $val)
        {
            $data[] = $callback($val);
        }

        return ['code'=>0,'msg'=>'','data'=>[
            'count'=>$count,'list'=>$data
        ]];
    }
	
	public static function withs($model,$ids,$title = '')
    {
        if(!is_array($ids))
        {
            $ids = explode(',',$ids);
        }
        $data = $model->whereIn('id',$ids)->orderbyRaw('FIND_IN_SET(id,?)',implode(',',$ids))->get();
        if($title)
        {
            return $data->pluck('title');
        }else
        {
            return $data->toArray();
        }
    }

    public static function enImages($data, $keys = [])
    {
        return self::parseImages($data, $keys);
    }
    /**
     * Undocumented function
     *
     * @param array $data 数据
     * @param array $keys 需要转化为图片的键值
     * @param boolean $fill_empty 是否自动填充 空白数据
     * @return void
     */
    public static function deImages(&$data, $keys = [], $fill_empty = false)
    {
        $data = self::parseImages($data, $keys, false);
        if ($fill_empty) {
            foreach ($keys as $key) {
                if (!isset($data[$key]) || empty($data[$key])) {
                    $data[$key] = [['url' => '']];
                }
            }
        }
        return $data;
    }

    public static function deImagesArr(&$data, $keys = [])
    {
        $data = self::parseImages($data, $keys, false);
        foreach ($keys as $key) {
            if (!isset($data[$key]) || empty($data[$key])) {
                $data[$key] = [];
            }else
            {
                $ret = [];
                foreach($data[$key] as $val)
                {
                    $ret[] = $val['url'];
                }
                $data[$key] = $ret;
            }
        }
        return $data;
    }

    /**
     * 网站显示图片 直接返回图片url
     *
     * @param [type] $data
     * @param array $keys
     * @param boolean $fill_empty 默认返回空值
     * @return void
     */
    public static function deImagesOne(&$data, $keys = [], $fill_empty = true,$params = [])
    {
        $data = self::parseImages($data, $keys, false,$params);
        if ($fill_empty) {
            foreach ($keys as $key) {
                if (!isset($data[$key]) || empty($data[$key]) || !is_array($data[$key])) {
                    $data[$key] = ['url' => '', 'name' => ''];
                } else {
                    $data[$key] = $data[$key][0]??['url' => '', 'name' => ''];
                }
            }
        }
        return $data;
    }

    public static function parseImages($data, $keys = [], $encode = true,$params = [])
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $data[$key] = self::uploadParse($data[$key], $encode,$params);
            }
        }
        return $data;
    }

    public static function uploadParse($data,$encode = true,$params = [])
    {
        if($encode)
        {
            if (is_array($data) && !empty($data)) {
                $_data = [];
                foreach ($data as $item) {
                    if(isset($item['url']))
                    {
                        unset($item['url']);
                    }
                    $_data[] = $item;
                }

                
                return json_encode($_data);
            }else
            {
                return '';
            }
        }else
        {
            $_data = [];
            if($data)
            {
                $data = is_string($data)? json_decode($data, 'true'):$data;
                if(is_array($data))
                {
                    foreach($data as $key=>$val)
                    {
                        $query = !empty($params)?http_build_query($params):'';

                        $media = '';

                        if(isset($val['value']))
                        {
                            $media = tomedia($val['value'],$query?true:false);
                        }elseif(isset($val['url']))
                        {
                            $media = tomedia($val['url'],$query?true:false);
                        }

                        if($media)
                        {
                            $data[$key]['url'] = $query?implode('?',[$media,$query]):$media;
                        }
                    }
                    return $data;
                }
            }
            return $_data;
        }
        
    }

    public static function asynUrl($url,$data = [],$method = 'GET')
    {
        $client = new Client();

		$options = [
			'headers' => [
				'Authorization' => request()->header('Authorization'),
				'Sa-Remember' => request()->header('Sa-Remember'),
			],
			'timeout'=>1
		];
        if($method == 'GET')
        {
            $options['query'] = $data;
        }else
        {
            $options['form_params'] = $data;
        }

		try{
			$client->request($method, $url, $options);
		}catch(Exception $e)
		{
			//异步执行url 无返回
		}
        return;
    }


    public static function searchWhereHas($model,$name,$columns,$search)
    {
        if($search === '')
        {
            return $model;
        }
        $search_val = $search;
        $search = self::json_validate($search);
        if($search !== false)
        {
            $search = array_values($search);
            $search_val = array_shift($search);
        }
        
        if(!$search_val)
        {
            return $model;
        }
        $model = $model->whereHas($name,function($q) use($columns,$search_val){
            foreach($columns as $key=>$val)
            {
                if($key == 0)
                {
                    $q->where([[$val, 'like', '%' . $search_val . '%']]);
                }else
                {
                    $q->orWhere([[$val, 'like', '%' . $search_val . '%']]);
                }
            }
        });
        return $model;
    }

    public static function searchWhere($model,$columns,$search_val,$type)
    {
        if($search_val === '')
        {
            return $model;
        }
        if($type == 'like')
        {
            $search_val = '%' . $search_val . '%';
        }

        if(count($columns) == 1)
        {
            //只搜索一个字段
            $model = $model->where([[$columns[0], $type, $search_val]]);
        }else
        {
            //多个字段搜索
            $model = $model->where(function($q) use($columns,$search_val,$type){
                foreach($columns as $key=>$val)
                {
                    if($key == 0)
                    {
                        $q->where([[$val, $type, $search_val]]);
                    }else
                    {
                        $q->orWhere([[$val, $type, $search_val]]);
                    }
                }
            });
        }

        return $model;
    }

    public static function uncamelize($camelCaps,$separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    public static function json_validate($string)
    {
        // decode the JSON data
        $result = json_decode($string,true);

        // switch and check possible JSON errors
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = ''; // JSON is valid // No error has occurred
                break;
            case JSON_ERROR_DEPTH:
                $error = 'The maximum stack depth has been exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Invalid or malformed JSON.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Control character error, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON.';
                break;
            // PHP >= 5.3.3
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
            // PHP >= 5.5.0
            case JSON_ERROR_RECURSION:
                $error = 'One or more recursive references in the value to be encoded.';
                break;
            // PHP >= 5.5.0
            case JSON_ERROR_INF_OR_NAN:
                $error = 'One or more NAN or INF values in the value to be encoded.';
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $error = 'A value of a type that cannot be encoded was given.';
                break;
            default:
                $error = 'Unknown JSON error occured.';
                break;
        }

        if ($error !== '') {
            // throw the Exception or exit // or whatever :)
            return false;
        }

        // everything is OK
        return $result;
    }
    public static function format_var_export($data = [],$tab_len = 0,$json = false)
    {
        if(!$data)
        {
            return '[]';
        }
        $start_tab = $tab_len > 0?str_repeat("\t",$tab_len):'';
        $end_tab = $tab_len - 1 > 0?str_repeat("\t",$tab_len-1):'';
        if($json)
        {
            $string = $data;
        }else
        {
            $data = self::arrayrecursive($data, 'urlencode', true);
            $string = json_encode($data); 
            $string = urldecode($string); 
        }
        
        $string = str_replace("[[", "[\r{$start_tab}[", $string);
        $string = str_replace("],[", "],\r{$start_tab}[", $string);
        $string = str_replace("]]", "]\r{$end_tab}]", $string);
        $string = str_replace("{", "\r{$start_tab}[", $string);
        $string = str_replace("},", "],", $string);
        $string = str_replace("}", "]", $string);
        $string = str_replace("]]", "],\r{$end_tab}]", $string);
        $string = str_replace("::", "@@", $string);
        $string = str_replace(":", " => ", $string);
        $string = str_replace("@@", "::", $string);
        $string = str_replace("\"@php", "", $string);
        $string = str_replace("@endphp\"", "", $string);
        //$string = var_export($data, TRUE);
        // $string = str_replace("=> \n  array (", "=> [", $string);
        // $string = str_replace("),", "],", $string);
        // $string = str_replace(");", "];", $string);
        // $string = str_replace("array (", "[", $string);
        // $string = str_replace("  ", "    ", $string);
        return $string;
    }

    public static function arrayrecursive($array, $function) 
    { 
        foreach ($array as $key => $value) { 
            if (is_array($value)) { 
                $array[$key] = self::arrayrecursive($array[$key],$function); 
            } else {
                if(is_string($value))
                {
                    $array[$key] = $function($value); 
                }
                
            } 
        }
        return $array;
    } 

    public static function getChild($model,$parseData = false,$order_by = [],$pid = 0,$pname = 'parent_id',$max_level = 0,$level = 1)
    {
        $list = clone $model;
        $list = $list->where([$pname => $pid]);
        if(!empty($order_by))
        {
            foreach($order_by as $orderby)
            {
                $list = $list->orderBy($orderby[0],$orderby[1]);
            }
        }
        $list = $list->get()->toArray();
        foreach ($list as $key => $val) {
            if($parseData)
            {
                $list[$key] = $parseData($val);
            }
            if($max_level == 0 || $max_level > $level)
            {
                $children = self::getChild($model,$parseData,$order_by,$val['id'], $pname,$max_level,$level+1);
                if (!empty($children)) {
                    $list[$key]['children'] = $children;
                }
            }
        }
        return $list;
    }

    public static function arrayResetKey($arr = [],$key = 'id')
    {
        $data = [];
        foreach($arr as $val)
        {
            if(isset($val[$key]))
            {
                $data[$val[$key]] = $val;
            }
        }
        return $data;
    }
}
