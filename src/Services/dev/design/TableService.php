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
     * @param ['edit','add','addTab','addGroup','delete','copyToMenu'] $action_type
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
            unset($base['checked']);
            if($checked)
            {
                //勾选 新增
                $action_type = 'add';
                $active = false;//最后插入
            }else
            {
                //取消勾选 删除
                $key = $base['key'];
                $action_type = 'delete';
                [$active,$old_value] = $this->getColumnIndex($columns,$key,'key');
            }
            
        }else
        {
            [$active,$old_value] = $this->getColumnIndex($columns,$uid);
        }

        if($action_type == 'copyToMenu')
        {
            return $this->copyToMenu($base,$old_value);
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
            $copy = Arr::get($base,'copy');
            //如果是复制过来的列，就不使用新的uid，这样可以实现复制更新列
            if(!isset($base['uid']) || !$copy)
            {
                $base['uid'] = $new_uid;
            }
            
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

    /**
     * 复制列至目标菜单中
     *
     * @param [type] $base
     * @return int
     */
    public function copyToMenu($base,$col)
    {
        $to_id = Arr::get($base,'props.toMenuId');
        $type = Arr::get($base,'props.type','updateOrInsert');//updateOrInsert ,insert
        if(!$to_id)
        {
            return 0;
        }
        $new_table_service = new TableService($to_id);
        //如果之前复制过，后检测为跟新同步列，而不再插入数据
        [$active,$old_value] = $new_table_service->getColumnIndex([],$col['uid']);
        if($active !== false && $type == 'updateOrInsert')
        {
            $new_table_service->edit($col,'edit');
        }else
        {
            $col['copy'] = true;
            $col['checked'] = true;
            if($type == 'insert')
            {
                //如果强制新增 重新生成一个uid
                $col = $this->renewUid($col);
            }
            $new_table_service->edit($col,'setColumns');
        }
        
        return $to_id;
    }

    /**
     * 表格的列都是一级的，所以直接生成即可
     *
     * @param [type] $col
     * @return void
     */
    public function renewUid($col)
    {
        $col['uid'] = HelperService::uuid();
        return $col;
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
        if(empty($columns))
        {
            $config = $this->config;
            $columns = $config?$config:[];
        }
        
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