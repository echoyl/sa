<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\Setting;

class SetsService
{
    var $model;
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
        return env('APP_NAME','');
    }

    public function baseKey()
    {
        return implode('_',[$this->appName(),'base']);
    }

    public function getBase($key = '',$type = '')
    {
        return $this->get(implode('.',[$this->baseKey(),$key]),$type);
    }

    public function webKey()
    {
        return implode('_',[$this->appName(),'web']);
    }

    public function getWeb($key = '',$type = '')
    {
        return $this->get(implode('.',[$this->webKey(),$key]),$type);
    }

    public function post($key)
    {
        $item = $this->model->where(['key'=>$key])->first();

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
				$this->model->insert($data);
			}
			return ['code'=>0,'msg'=>'提交成功'];
		}else
		{
			if($item && $item['value'])
			{
				$data = json_decode($item['value'],true);
                $img_fields = HelperService::getImageFields($data);

                if(!empty($img_fields))
                {
                    HelperService::parseImages($data,$img_fields,false);
                }

                HelperService::deImagesFromConfig($data);

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
        $data[$key] = $this->model->where(['key'=>$key])->first();
        return $data[$key];
    }

}
