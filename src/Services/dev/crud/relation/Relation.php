<?php
namespace Echoyl\Sa\Services\dev\crud\relation;

use Echoyl\Sa\Services\dev\crud\ParseData;
use Illuminate\Support\Arr;

class Relation
{
    var $model_class;//当前模型class
    var $data;//当前模型主数据
    public function __construct($class,$data)
    {
        $this->model_class = $class;
        $this->data = $data;
    }


    public function afterPost($data)
    {
        $model = new $this->model_class;
        $parse_columns = $model->getParseColumns();
        foreach($parse_columns as $column)
        {
            $type = $column['type'];
            $name = $column['name'];

            $disable_after_post = Arr::get($column,'setting.disable_after_post',false);

            if($disable_after_post)
            {
                //禁用了关联后续操作
                continue;
            }
            
            switch ($type) {
                case 'model':
                    if(isset($data[$name]))
                    {
                        $idata = filterEmpty($data[$name]);
                        $this->hasOne($idata,$column);
                    }
                    break;
                case 'models':
                    if(isset($data[$name]))
                    {
                        $this->hasMany($data[$name],$column);
                    }
                break;
            }
        }
        return;
    }

    

    public function hasMany($datas,$column)
    {
        $ids = [];
        $data_id = $this->data['id'];
        $class = $column['class']??'';
        $foreign_key = $column['foreign_key']??'';
        if(!$class || !$foreign_key)
        {
            return;
        }
		foreach($datas as $data)
		{
            $data = filterEmpty($data);

            $id = Arr::get($data,'id',0);

            (new ParseData($class))->make($data,'encode',$id?'update':'insert');

			if($id)
			{
				//更新
				$class::where(['id'=>$id])->update($data);
				$ids[] = $id;
			}else
			{
				//插入
				$data[$foreign_key] = $data_id;
                $ids[] = (new $class)->insertGetId($data);
			}
		}
		//删除没有的
        $class::where([$foreign_key=>$data_id])->whereNotIn('id',$ids)->forceDelete();
		return;
    }

    /**
     * 处理管理模型数据 function
     * 1-1关系
     * @param [type] $data  数据
     * @param [type] $class 模型
     * @param [type] $where 模型的唯一条件
     * @return void
     */
    public function hasOne($data,$column)
    {
        $class = $column['class']??'';
        $foreign_key = $column['foreign_key']??'';
        $name = $column['name'];
        if(!$class || !$foreign_key)
        {
            return;
        }

        $model = new $class;
        $ext_data = [];
        if($foreign_key == 'id')
        {
            return;//暂时只支持更新外键不是id的数据
            $where = ['id'=>$this->data[$foreign_key]];
        }else
        {
            $where = $ext_data = [$foreign_key => $this->data['id']];
        }

        $item = $model->where($where)->first();

        if($item)
        {
            //更新
            $from = 'update';
        }else
        {
            //新增
            $from = 'insert';
        }
        (new ParseData($class))->make($data,'encode',$from);

        $data = array_merge($data,$ext_data);

        if($from == 'update')
        {
            $model->where($where)->update($data);
        }else
        {
            $id = (new $class)->insertGetId($data);
            if($foreign_key == 'id')
            {
                //反向更新主数据 关联键值
                $local_key = implode('_',[$name,'id']);
                $this->model_class::where(['id'=>$this->data['id']])->update([$local_key=>$id]);
            }
        }
        return;
    }
}