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
            if (is_array($data)) {
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

}
