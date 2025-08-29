<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\crud\CrudService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DateTime;
use Echoyl\Sa\Constracts\SaAdminAppServiceInterface;
use Echoyl\Sa\Constracts\SaServiceInterface;
use Echoyl\Sa\Services\admin\LocaleService;

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
        if(!$data)
        {
            return $data;
        }
        $img_fields = self::getImageFields($data);
        if(!empty($img_fields))
        {
            self::parseImages($data,$img_fields,false);
        }
        foreach($data as $key=>$deep_val)
        {
            if(in_array($key,$img_fields))
            {
                //已经是图片类型的不用再检测了
                continue;
            }
            if(is_array($deep_val) && !empty($deep_val))
            {
                //如果是
                //$deep_val = self::autoParseImages($deep_val);
                // foreach($deep_val as $k=>$v)
                // {
                //     if(is_array($v))
                //     {
                        
                //         $deep_val[$k] = self::autoParseImages($v);
                        
                //     }
                    
                // }
                $data[$key] = self::autoParseImages($deep_val);
                
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

    public static function map($model,$callback,$order_by = [],$where = [])
    {
        $page = webapi_request('page',1);
        $page_size = webapi_request('page_size',10);

        $m = $model->where($where);

        $count = $m->count();
        foreach($order_by as $order)
        {
            $m = $m->orderBy($order[0],$order[1]);
        }
        $data = $m->offset(($page-1)*$page_size)->limit($page_size)->get()->map(fn($item) => $callback($item))->toArray();

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
        foreach($keys as $key)
        {
            $config = [
                'data'=>$data,'col'=>['name'=>$key,'type'=>'image','default'=>''],
            ];
            $cs = new CrudService($config);
            //make后的图片数据变成了json需要重新转换一下
            $data = $cs->make('image',[
                'encode'=>true,
                'isset'=>isset($data[$key]),
            ]);
            if(isset($data[$key]) && $data[$key])
            {
                $data[$key] = json_decode($data[$key],true);
            }
        }
        //如果有原始数据 将原始数据删除
        Arr::forget($data,'originData');
        return self::parseImages($data, $keys);
    }

    /**
     * 字符串转数组，如果是数组则原值返回
     *
     * @param array | string $key
     * @return array | string
     */
    public static function str2Arr($key = [])
    {
        if(is_string($key))
        {
            return [$key];
        }else
        {
            return $key;
        }
    }

    /**
     * Undocumented function
     *
     * @param array $data 数据
     * @param array $keys 需要转化为图片的键值
     * @param boolean $fill_empty 是否自动填充 空白数据
     * @return array
     */
    public static function deImages(&$data, $keys = [], $fill_empty = false)
    {
        $keys = self::str2Arr($keys);
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
        $keys = self::str2Arr($keys);
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
        $keys = self::str2Arr($keys);
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

    /**
     * 处理图片附件链接 完整
     *
     * @param [type] $url
     * @param boolean $img
     * @return void
     */
    public static function tomedia($url,$img = false)
    {
        if(is_array($url))
        {
            $rt = [];
            foreach($url as $val)
            {
                if($val)
                {
                    $rt[] = self::tomedia($val,$img);
                }
                
            }
            return $rt;
        }else
        {
            if(strpos($url,'http') !== false || strpos($url,'https') !== false)
            {
                return $url;
            }else
            {
                return $url?self::getFileImagePrefix($img).$url:'';
            }
        }
    }

    public static function getFileImagePrefix($img = false)
    {
        $prefix = $img ?rtrim(env('APP_URL'),'/').'/img/storage' : rtrim(env('APP_URL'),'/').'/storage';//本地存储
        if(env('ALIYUN_OSS'))
        {
            //如果开启阿里云存储 返回
            $prefix = implode('/',[env('ALIYUN_DOMAIN'),env('ALIYUN_OSS')]);
        }
        return rtrim($prefix).'/';
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
                    $value = Arr::get($item,'value');
                    if(!$value)
                    {
                        continue;
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
                            $media = self::tomedia($val['value'],$query?true:false);
                        }elseif(isset($val['url']))
                        {
                            $media = self::tomedia($val['url'],$query?true:false);
                        }

                        if($media)
                        {
                            $data[$key]['url'] = $query?implode(strpos($media,'?') !== false?'&':'?',[$media,$query]):$media;
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
        $res = Http::withHeaders([
            'Authorization' => request()->header('Authorization'),
            'Sa-Remember' => request()->header('Sa-Remember'),
        ])->timeout(1);

		try{
            if($method == 'GET')
            {
                $res->get($url,$data);
            }else
            {
                $res->post($url,$data);
            }
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

    public static function searchWhereBetweenIn($model,$columns,$search_val,$where_type = 'whereBetween')
    {
        if($search_val)
        {
            if(is_numeric($search_val))
            {
                $search_val = [$search_val];
            }else
            {
                $search_val = is_string($search_val) ? json_decode($search_val,true):$search_val;
                if($where_type == 'whereBetween' && is_array($search_val) && isset($search_val[1]))
                {
                    //检测是否是日期
                    $d = DateTime::createFromFormat("Y-m-d",$search_val[1]);
                    if($d && $d->format('Y-m-d') === $search_val[1])
                    {
                        //是日期 自动追加至当天最后一秒
                        $search_val[1] .= ' 23:59:59';
                    }
                }
            }
            $model = $model->$where_type($columns[0],$search_val);
        }

        return $model;
    }

    /**
     * 模型搜索
     *
     * @param [type] $model 模型或query
     * @param [type] $columns 检索的字段
     * @param [type] $search_val 检索内容
     * @param [type] $type 检索类型 
     * @param array $more 更多参数 5个参数疯了
     * @return void
     */
    public static function searchWhere($model,$columns,$search_val,$type,$more = [])
    {
        if($search_val === '')
        {
            return $model;
        }

        if($search_val == 'all')
        {
            //保留关键字 搜索all 过滤掉
            return $model;
        }

        $search_val = urldecode($search_val);

        if($type == 'like')
        {
            $search_val = '%' . $search_val . '%';
        }

        $origin_model = Arr::get($more,'origin_model');

        if(count($columns) == 1)
        {
            //只搜索一个字段
            $model = LocaleService::search($model,[$columns[0], $type, $search_val],$origin_model);
        }else
        {
            //多个字段搜索
            $model = $model->where(function($q) use($columns,$search_val,$type,$origin_model){
                foreach($columns as $key=>$val)
                {
                    $q = LocaleService::search($q,[$val, $type, $search_val],$origin_model,$key);
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

    public static function getChildFromData($all_data,$parseData = false,$order_by = [],$pid = 0,$pname = 'parent_id',$max_level = 0,$level = 1)
    {
        $list = collect($all_data);
        $list = $list->where($pname,$pid);

        if(!empty($order_by))
        {
            $list = $list->sortBy($order_by);
            // foreach($order_by as $orderby)
            // {
            //     $list = $list->sortBy($orderby[0],$orderby[1]);
            // }
        }
        $list = $list->toArray();
        foreach ($list as $key => $val) {
            if($parseData)
            {
                $list[$key] = $parseData($val);
            }
            if($max_level == 0 || $max_level > $level)
            {
                $children = self::getChildFromData($all_data,$parseData,$order_by,$val['id'], $pname,$max_level,$level+1);
                if (!empty($children)) {
                    $list[$key]['children'] = $children;
                }
            }
        }
        return array_values($list);
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

    public static function distanceRaw($lat,$lng,$lat_name = 'lat',$lng_name = 'lng')
    {
        $lat = floatval($lat);
        $lng = floatval($lng);
        $distance = ' ACOS(SIN((' . $lat . ' * 3.1415) / 180 ) *SIN(('.$lat_name.' * 3.1415) / 180 ) +COS((' . $lat . ' * 3.1415) / 180 ) * COS(('.$lat_name.' * 3.1415) / 180 ) *COS((' . $lng . ' * 3.1415) / 180 - ('.$lng_name.' * 3.1415) / 180 ) ) * 6380';
        return $distance;
    }

    public static function distanceRawDb($lat,$lng,$lat_name = 'lat',$lng_name = 'lng',$name = 'distance')
    {
        return DB::raw(self::distanceRaw($lat,$lng,$lat_name,$lng_name).' as '.$name);
    }

    public static function parseMobile($mobile)
    {
        return substr($mobile,0,3).'****'.substr($mobile,7,4);
    }

    public static function isMobileNumber($mobile)
    {
        return preg_match("/^1\d{10}$/",$mobile);
    }

    public static function get($url,$query = [],$body = false,$pre = false)
    {
        //Log::channel('daily')->info('request query:',['query'=>$query,'url'=>$url]);

        try{
            $ins = Http::timeout(10);
            if($pre)
            {
                $ins = $pre($ins);
            }
            $res = $ins->get($url,$query);
        }catch(Exception $e)
        {
            return [1,$e->getMessage()];
        }

        $data = $body?$res->body():$res->json();
    
        return [0,$data];
    }

    public static function post($url,$post,$body = false,$pre = false)
    {
        //Log::channel('daily')->info('post start:',['params'=>$post]);
        try{
            $ins = Http::timeout(10);
            if($pre)
            {
                $ins = $pre($ins);
            }
            $res = $ins->post($url,$post);
        }catch(Exception $e)
        {
            return [1,$e->getMessage()];
        }

        $data = $body?$res->body():$res->json();
        
        return [0,$data];
    }

    public static function pwd($str)
    {
        return md5($str);
    }

    public static function secondsToTime($seconds) {

        $hours = floor($seconds / 3600);
      
        $minutes = floor(($seconds - ($hours * 3600)) / 60);
      
        $seconds = $seconds - ($hours * 3600) - ($minutes * 60);
      
        return str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.str_pad($seconds, 2, '0', STR_PAD_LEFT);
      
    }
    /**
     * 生成随机字符串
     *
     * @param integer $length
     * @return string
     */
    public static function uuid($length = 10)
    {
        return Str::random($length);
    }

    /**
     * 数组元素的移动
     *
     * @param [array] $arr 数组
     * @param [number] $from 移动的元素键值
     * @param [number] $to 移动到目标位置键值
     * @return array
     */
    public static function arrayMove($arr,$from,$to)
    {
        if($from == $to)
        {
            return $arr;
        }
        $active_data = $arr[$from];
        if($from < $to)
        {
            //往后
            array_splice($arr,$to + 1,0,[$active_data]);
            //将之前的数据删除
            unset($arr[$from]);
        }else
        {
            //往前
            //将之前的数据删除
            unset($arr[$from]);
            if($to == 0)
            {
                //已经是第一个了
                array_unshift($arr,$active_data);
            }else
            {
                array_splice($arr,$to,0,[$active_data]);
            }
            
        }
        return array_values($arr);
    }

    public static function isDev()
    {
        return env('APP_ENV') == 'local';
    }

    public static function getDevModel($model_id)
    {
        $model = new Model();
        $data = $model->where(['id'=>$model_id])->first();
        if($data)
        {
            $data = $data->toArray();
        }
        return $data;
    }

    public static function getAdminService()
    {
        return app()->make(SaAdminAppServiceInterface::class);
    }

    public static function getAppService()
    {
        return app()->make(SaServiceInterface::class);
    }
}
