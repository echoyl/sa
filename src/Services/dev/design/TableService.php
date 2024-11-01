<?php
namespace Echoyl\Sa\Services\dev\design;

use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

class TableService extends BaseService
{
    var $name = 'table_config';
    
    /**
     * 所有编辑操作
     *
     * @param [type] $base
     * @param ['edit','add','addTab','addGroup','delete'] $action_type
     * @return void
     */
    public function edit($base,$action_type = 'edit',$form_type = 'base')
    {
        $config = $this->config;
        $name = $this->name;
        $columns = $config?$config:[];
        $uid = Arr::get($base,'uid');

        if($action_type == 'setColumns')
        {
            //table 快速设置列字段展示
            $checked = $base['checked'];
            $key = $base['key'];
            unset($base['checked']);
            if($checked)
            {
                //勾选 新增
                $action_type = 'add';
            }else
            {
                //取消勾选 删除
                $action_type = 'delete';
            }
            [$active,$old_value] = $this->getColumnIndex($columns,$key,'key');
        }else
        {
            [$active,$old_value] = $this->getColumnIndex($columns,$uid);
        }

        //d($active,$old_value,$action_type);
        
        

        

        

        $new_uid = HelperService::uuid();

        if($action_type == 'edit')
        {
            if($active === false)
            {
                return;
            }

            //编辑列的时候需要检测 是编辑base还是more
            if($form_type == 'base')
            {
                //基础修改
                if(isset($old_value['props']))
                {
                    //$base['props'] = isset($base['props'])?array_merge($old_value['props'],$base['props']):$old_value['props'];
                }
            }

            $columns[$active] = $base;
            
        }elseif($action_type == 'delete')
        {
            //删除数据
            if($active === false)
            {
                return;
            }
            Arr::forget($columns,$active);
            $columns = array_values($columns);

        }else
        {
            //添加列
            
            $base['uid'] = $new_uid;
            if($active === false)
            {
                //最后插入
                $columns[] = $base;
            }else
            {
                //某列后插入
                array_splice($columns,$active + 1,0,[$base]);
                $columns = array_values($columns);
            }
            
        }

        $this->model->where(['id'=>$this->id])->update([$name=>json_encode($columns)]);
        return;
    }

    public function sort($columns)
    {
        $config = $this->config;
        $name = $this->name;

        [$active_key,$over_key] = $columns;


        $columns = $config?:[];

        [$active] = $this->getColumnIndex($columns,$active_key);
        [$over] = $this->getColumnIndex($columns,$over_key);

        //d($active,$over);

        $columns = HelperService::arrayMove($columns,$active,$over);
        //d($active,$over,$columns);
        $this->model->where(['id'=>$this->id])->update([$name=>json_encode($columns)]);

        return true;
    }

     /**
     * 获取tabs中字段的索引及数据信息
     * form字段的结构为 tab - group - col 3维
     * @param [type] $tabs
     * @param [type] $uid
     * @return void
     */
    public function getColumnIndex($columns,$uid,$key_name = 'uid')
    {
        $index = $index_data = false;
        //d($columns);
        foreach($columns as $key=>$val)
        {
            $uid_val = Arr::get($val,$key_name);
            if($uid == $uid_val)
            {
                $index = $key;
                $index_data = $val;
                break;
            }
        }
        
        return [$index,$index_data];
    }
}