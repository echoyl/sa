<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\Setting;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Utils;
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

    /**
     * 通过键值获取配置信息
     *
     * @param [type] $key
     * @param string | int $type 当type为int时表示通过后台菜单id解析配置信息
     * @return void
     */
    public function get($key,$type = '')
    {
        $data = $this->getSet($key,$type);
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

    public function getBase($key = '')
    {
        return $this->get(implode('.',[$this->baseKey(),$key]),160);
    }

    public function webKey()
    {
        return 'web';
    }

    public function getWeb($key = '')
    {
        return $this->get(implode('.',[$this->webKey(),$key]),162);
    }

    public function systemKey()
    {
        return 'system';
    }

    public function getSystem($key = '')
    {
        return $this->get(implode('.',[$this->systemKey(),$key]),323);
    }

    public function getCacheKey($key = '')
    {
        return implode('_',[$this->cache_prefix,$key]);
    }

    public function post($key,$deep_img_fields = [],$method = 'POST')
    {
        $app_name = $this->appName();
        //编辑模式不再读取缓存
        $item = $this->getData($key,true);
        if($item && $item['value'])
        {
            $data = json_decode($item['value'],true);
        }else
        {
            $data = [];
        }
        $dev_menu = request('dev_menu');

		if(request()->isMethod('post') && $method == 'POST')
		{
			$post_data = filterEmpty(request('base'),['watermark']);

            $post_data = Utils::parseImageInPage($post_data,$dev_menu,$data,'encode',$deep_img_fields);

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
            Cache::forget($this->getCacheKey($key));
			return ['code'=>0,'msg'=>'提交成功','data'=>Utils::parseImageInPage($post_data,$dev_menu,false,'decode',$deep_img_fields)];
		}else
		{
            $data = Utils::parseImageInPage($data,$dev_menu,false,'decode',$deep_img_fields);
			return ['code'=>0,'data'=>$data];
		}
	}
	
    /**
     * 获取配置数据内容
     *
     * @param [string] $key 配置key 示例 base.banners
     * @param integer $dev_menu 如果存在菜单后台菜单id 会根据菜单id解析一遍设置
     * @return string | boolean | array
     */
	public function getSet($key,$dev_menu_id = 0)
    {
        static $sets = [];

        if(!$key)
        {
            return false;
        }

        if(isset($sets[$key]) && $sets[$key])
        {
            return $sets[$key];
        }

        $keys = explode('.',$key);
        $keys = array_filter($keys,fn($key)=>$key);
        $firstKey = array_shift($keys);
        

        $data = $this->getData($firstKey);

        if($data)
        {
            $value = json_decode($data['value'],true);
            //自动解析配置中的数据
            if($dev_menu_id && is_int($dev_menu_id))
            {
                $dev_menu = Utils::getDevMenu($dev_menu_id);
                
                $value = Utils::parseImageInPage($value,$dev_menu,false,'decode');
            }
            
            if(!empty($keys))
            {
                $value = Arr::get($value,implode('.',$keys),false);
            }
            
            $sets[$key] = $value;
        }else
        {
            $sets[$key] = false;
        }
        return $sets[$key];
    }
    
    public function getData($key,$force = false)
    {
        static $data = [];
        if(isset($data[$key]) && $data[$key])
        {
            return $data[$key];
        }
        //加入缓存设置
        $cache_key = $this->getCacheKey($key);
        $item = false;
        if(!$force)
        {
            $item = Cache::get($cache_key);
        }
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
