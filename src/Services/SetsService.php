<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\Setting;
use Echoyl\Sa\Services\dev\crud\CrudService;
use Echoyl\Sa\Services\dev\DevService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class SetsService
{
    var $model;
    var $cache_prefix = 'sets';
    public function __construct($model = null)
	{
        if(!$model)
        {
            $this->model = new Setting();
        }else
        {
            $this->model = $model;
        }
	}

    public function get($key,$type = '')
    {
        $data = $this->getSet($key);
        if($type == 'json')
        {
            $data = json_decode($data,'true');
        }
        return $data;
    }

    public function appName()
    {
        return DevService::appname();
    }

    public function baseKey()
    {
        return 'base';
    }

    public function getBase($key = '',$type = '')
    {
        return $this->get(implode('.',[$this->baseKey(),$key]),$type);
    }

    public function webKey()
    {
        return 'web';
    }

    public function getWeb($key = '',$type = '')
    {
        return $this->get(implode('.',[$this->webKey(),$key]),$type);
    }

    public function getCacheKey($key = '')
    {
        return implode('_',[$this->cache_prefix,$key]);
    }

    public function parseImgField($post_data,$imgf)
    {
        $config = [
            'data'=>$post_data,'col'=>['name'=>$imgf,'type'=>'image','default'=>''],
        ];
        $cs = new CrudService($config);
        //make后的图片数据变成了json需要重新转换一下
        $post_data = $cs->make('image',[
            'encode'=>true,
            'isset'=>true,
        ]);
        if($post_data[$imgf])
        {
            $post_data[$imgf] = json_decode($post_data[$imgf],true);
        }

        return $post_data;
    }

    public function post($key,$deep_img_fields = [])
    {
        $app_name = $this->appName();
        $item = $this->getData($key);
        //新增自动检测字段是否是图片 及 配置 类型


		if(request()->isMethod('post'))
		{
			$post_data = filterEmpty(request('base'));

            // $img_fields = HelperService::getImageFields($post_data);
            
            // if(!empty($img_fields))
            // {
            //     $post_data = HelperService::parseImages($post_data,$img_fields);
            // }
            //d($post_data);
            //只能获取到第一层的字段是否是图片类型
            $img_fields = array_merge($deep_img_fields,HelperService::getImageFields($post_data));
            foreach($img_fields as $imgf)
            {
                if(is_string($imgf))
                {
                    $post_data = $this->parseImgField($post_data,$imgf);
                }elseif(is_array($imgf))
                {
                    $d = Arr::get($post_data,implode('.',$imgf));
                    if($d)
                    {
                        $name = array_pop($imgf);
                        $top = Arr::get($post_data,implode('.',$imgf));
                        $top = $this->parseImgField($top,$name);
                        Arr::set($post_data,implode('.',$imgf), $top);
                    }
                }
            }
            //d($post_data);

			$data = [
				'key'=>$key,
				'value'=>json_encode($post_data)
			];
			if($item)
			{
				//更新数据
				$this->model->where(['id'=>$item['id']])->update($data);
			}else
			{
                $data['app_name'] = $app_name;
				$this->model->insert($data);
			}
            Cache::delete($this->getCacheKey($key));
			return ['code'=>0,'msg'=>'提交成功'];
		}else
		{
			if($item && $item['value'])
			{
				$data = json_decode($item['value'],true);
                $data = HelperService::autoParseImages($data);
			}else
			{
				$data = [];
			}
			return ['code'=>0,'data'=>$data];
		}
	}
	
	public function getSet($key)
    {
        static $sets = [];
        if(isset($sets[$key]) && $sets[$key])
        {
            return $sets[$key];
        }
        if(strpos($key,'.') !== false)
        {
            $keys = explode('.',$key);
            $firstKey = $keys[0];
        }else
        {
            $firstKey = $key;
            $keys = [$key];
        }
        $data = $this->getData($firstKey);
        if($data)
        {
            $value = json_decode($data['value'],true);
            foreach($keys as $i=>$k)
            {
                if($i > 0 && isset($value[$k]))
                {
                    $value = $value[$k];
                }
                
            }
            $sets[$key] = $value;
        }else
        {
            $sets[$key] = false;
        }
        return $sets[$key];
    }
    public function getData($key)
    {
        static $data = [];
        if(isset($data[$key]) && $data[$key])
        {
            return $data[$key];
        }
        //加入缓存设置
        $cache_key = $this->getCacheKey($key);
        $item = Cache::get($cache_key);
        if(!$item)
        {
            $item = $this->model->where(['key'=>$key,'app_name'=>$this->appName()])->first();
            if(!$item && $key == 'setting')
            {
                $item = $this->model->where(['key'=>$key,'app_name'=>'deadmin'])->first();
            }
            Cache::set($cache_key,$item);
        }
        $data[$key] = $item;
        
        return $data[$key];
    }

}
