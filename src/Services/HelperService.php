<?php
namespace Echoyl\Sa\Services;

use Exception;
use Illuminate\Support\Facades\Log;
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
            'count'=>$count,'data'=>$data
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

    public static function uploadParse($data,$encode = true)
    {
        if($encode)
        {
            // $_data = [];
            // if(is_array($data))
            // {
            //     foreach($data as $item)
            //     {
            //         if(isset($item['response']))
            //         {
            //             $url = $item['response']['data']['value'];
            //         }else
            //         {
            //             $url = $item['value'];
            //         }
            //         $_data[] = [
            //             'name'=>$item['name'],
            //             'url'=>$url
            //         ];
            //     }
            // }
            
            //return json_encode($data);
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
                $data = json_decode($data,'true');
                if(is_array($data))
                {
                    foreach($data as $key=>$val)
                    {
                        $data[$key]['url'] = tomedia($val['value']);
                        // $_data[] = [
                        //     'name'=>$val['name'],
                        //     'url'=>tomedia($val['url']),
                        //     'value'=>$val['url']
                        // ];
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

    public static function searchWhere($model,$name,$columns,$search_val,$type)
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

}
