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

    /**
     * 获取数据是 配置类型的字段 名
     *
     * @param [type] $data
     * @return void | array
     */
    public static function getConfigFields($data)
    {
        if(empty($data))
        {
            return [];
        }
        $fields = [];
        foreach($data as $key=>$val)
        {
            if(isset($val['config']))
            {
                $fields[] = $key;
            }
        }
        return $fields;
    }

    /**
     * 将配置类型的数据中的图片类型解析
     *
     * @param [type] $data
     * @return void
     */
    public static function deImagesFromConfig(&$data)
    {
        $fields = self::getConfigFields($data);
        
        if(!empty($fields))
        {
            foreach($fields as $f)
            {
                $data[$f]['value'] = self::autoParseImages($data[$f]['value']);
            }
        }
        return;
    }

    public static function autoParseImages($data)
    {
        $img_fields = self::getImageFields($data);
        if(!empty($img_fields))
        {
            self::parseImages($data,$img_fields,false);
        }
        foreach($data as $key=>$deep_val)
        {
            if(is_array($deep_val) && !empty($deep_val))
            {
                //如果是
                foreach($deep_val as $k=>$v)
                {
                    if(is_array($v))
                    {
                        
                        $deep_val[$k] = self::autoParseImages($v);
                        
                    }
                    
                }
                $data[$key] = $deep_val;
                
            }
        }
        return $data;
    }

    /**
     * 检测数据里面哪些字段是图片类型
     *
     * @param [type] $data
     * @return void | array
     */
    public static function getImageFields($data)
    {
        if(empty($data))
        {
            return [];
        }
        $fields = [];
        foreach($data as $key=>$val)
        {
            if(is_array($val) && !empty($val))
            {
                if(isset($val[0]['value']) && isset($val[0]['uid']))
                {
                    $fields[] = $key;
                }
            }
        }
        return $fields;
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

    public static function parseImages(&$data, $keys = [], $encode = true,$params = [])
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $data[$key] = self::uploadParse($data[$key], $encode,$params);
            }
        }
        return $data;
    }

    public static function aliyunVideoParse($data,$encode = true,$params = [])
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
                    if(isset($item['play_url']))
                    {
                        unset($item['play_url']);
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
                return $data;
            }
            return $_data;
        }
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

        $search_val = false;
        if(is_string($search))
        {
            $json = self::json_validate($search);
            if($json !== false)
            {
                $json = array_values($json);
                $search_val = array_shift($json);
            }else
            {
                $search_val = $search;
            }
        }elseif(is_array($search))
        {
            if(empty($search))
            {
                return $model;
            }
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
        if(!is_string($string) || is_numeric($string))
        {
            return false;
        }
        
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

    public static function getFromObject($data,$keys)
    {
        $name = '';
        $key = $keys[0];

        if(isset($data[$key]))
        {
            if(is_array($data[$key]))
            {
                array_shift($keys);
                if(!empty($keys))
                {
                    return self::getFromObject($data[$key],$keys);
                }else
                {
                    $name = $data[$key];
                }
            }else
            {
                $name = $data[$key];
            }
        }
        return $name;
    }
    public static function parseMobile($mobile)
    {
        return substr($mobile,0,3).'****'.substr($mobile,7,4);
    }

    public static function isMobileNumber($mobile)
    {
        return preg_match("/^1\d{10}$/",$mobile);
    }

    public static function get($url,$query)
    {
        $client = new Client();
        //Log::channel('daily')->info('request query:',['query'=>$query,'url'=>$url]);

        try{
            $res = $client->request('GET',$url,[
                'query'=>$query,
                'timeout'=>10,
            ]);
        }catch(Exception $e)
        {
            return [1,$e->getMessage()];
        }

        
        $content = $res->getBody()->getContents();

        //Log::channel('daily')->info('request get result:',['content'=>$content,'url'=>$url]);

        $data = json_decode($content,true);
        return [0,$data];
    }

    public static function post($url,$post)
    {
        //Log::channel('daily')->info('post start:',['params'=>$post]);

        $client = new Client();
        //Log::channel('daily')->info('try post start:',['params'=>$post]);
        try{
            $res = $client->request('POST',$url,[
                'form_params'=>$post,
                'timeout'=>10,
            ]);
        }catch(Exception $e)
        {
            return [1,$e->getMessage()];
        }
        
        $content = $res->getBody()->getContents();

        //Log::channel('daily')->info('request post result:',['content'=>$content,'url'=>$url]);
        $data = json_decode($content,true);
        
        return [0,$data];
    }
}
