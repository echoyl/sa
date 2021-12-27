<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\Sets;

class SetsService
{
    public function __construct($model = null)
	{
        if(!$model)
        {
            $this->model = new Sets();
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
    public function post($key)
    {
        $item = $this->model->where(['key'=>$key])->first();

		if(request()->isMethod('post'))
		{
			$post_data = filterEmpty(request('base'));
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
