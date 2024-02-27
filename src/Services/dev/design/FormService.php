<?php
namespace Echoyl\Sa\Services\dev\design;

use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

class FormService extends BaseService
{
    var $name = 'form_config';
    
    /**
     * 所有编辑操作
     *
     * @param [type] $base
     * @param ['edit','add','addTab','addGroup','delete'] $action_type
     * @param ['base','more'] $form_type
     * @return void
     */
    public function edit($base,$action_type = 'edit',$form_type = 'base')
    {
        $config = $this->config;
        $name = $this->name;

        
        $uid = Arr::get($base,'uid');

        if(!isset($config['tabs']))
        {
            $config['tabs'] = [];
        }

        $tabs = $config['tabs'];

        [$active,$old_value] = $this->getColumnIndex($tabs,$uid);

        $new_uid = HelperService::uuid();

        if($action_type == 'edit')
        {
            if(!$active)
            {
                return;
            }
            $count = count($active);
            $keys = implode('.',$active);

            //原值
            //$old_value = Arr::get($tabs,$keys);

            if($count == 5)
            {
                //编辑列的时候需要检测 是编辑base还是more
                // if($form_type == 'base')
                // {
                //     //基础修改
                //     if(isset($old_value['props']))
                //     {
                //         $base['props'] = isset($base['props'])?array_merge($old_value['props'],$base['props']):$old_value['props'];
                //     }
                // }
                $new_set = $base;
            }else
            {
                //编辑tab 分组时 需要获取其它值 所有这里是合并变量
                $new_set = array_merge($old_value,$base);
            }

            Arr::set($tabs,$keys,$new_set);
            
        }elseif($action_type == 'addTab')
        {
            //新增tab
            $base = array_merge($base,['config'=>[],'uid'=>$new_uid]);
            if($active)
            {
                //向后插入tab
                array_splice($tabs,$active[0] + 1,0,[$base]);
                $tabs = array_values($tabs);
            }else
            {
                $tabs[] = $base;
            }
            
            //d($tabs);
            
        }elseif($action_type == 'addGroup')
        {
            //添加分组
            if(!$active)
            {
                return;
            }
            $count = count($active);
            //原值
            $keys = implode('.',$active);
            $base['uid'] = $new_uid;
            $empty_group_data = ['columns'=>[]];
            if($count == 1)
            {
                //在tab中插入分组
                $old_value['config'][] = array_merge($empty_group_data,$base);
                Arr::set($tabs,$keys,$old_value);
            }elseif($count == 3)
            {
                //在分组后插入分组
                [$keys,$last_key,$top_data] = $this->getTopData($active,$tabs);
                array_splice($top_data,$last_key + 1,0,[$empty_group_data]);
                Arr::set($tabs,$keys,array_values($top_data));
            }
            
        }elseif($action_type == 'delete')
        {
            //删除数据
            if(!$active)
            {
                return;
            }
            Arr::forget($tabs,implode('.',$active));
            if(count($active) == 5)
            {
                //删除列的话 需要重新再计算 每列的span 宽度
                // 和 panel 不一样，span计算这里不做判断
            }
            $tabs = $this->formatTopData($active,$tabs);
        }else
        {
            //添加列
            
            $base['uid'] = $new_uid;

            if(!$active)
            {
                return;
            }
            $count = count($active);
            //原值
            $keys = implode('.',$active);
            if($count == 3)
            {
                //在组中插入列
                $old_value['columns'][] = $base;
                Arr::set($tabs,$keys,$old_value);
            }elseif($count == 5)
            {
                //在列后插入列
                [$keys,$last_key,$top_data] = $this->getTopData($active,$tabs);
                array_splice($top_data,$last_key + 1,0,[$base]);
                Arr::set($tabs,$keys,array_values($top_data));
            }
            
        }
        $config['tabs'] = $tabs;

        $this->model->where(['id'=>$this->id])->update([$name=>json_encode($config)]);
        return;
    }

    public function sort($columns)
    {
        $config = $this->config;
        $name = $this->name;

        [$active_key,$over_key] = $columns;

        if(!isset($config['tabs']))
        {
            $config['tabs'] = [];
        }

        $tabs = $config['tabs'];

        [$active,$active_data] = $this->getColumnIndex($tabs,$active_key);
        [$over] = $this->getColumnIndex($tabs,$over_key);

        $active_count = count($active);
        $over_count = count($over);

        if($active_count == 1 && $over_count == 1)
        {
            //tab - tab
            $tabs = HelperService::arrayMove($tabs,$active[0],$over[0]);
        }

        if($active_count == 3 && $over_count == 3)
        {
            //group - group
            [$keys,$last_key,$top_data] = $this->getTopData($active,$tabs);
            //d($top_data,$active,$over);
            $top_data = HelperService::arrayMove($top_data,$active[2],$over[2]);
            Arr::set($tabs,$keys,$top_data);
        }

        if($active_count == 3 && $over_count == 1)
        {
            if($active[0] == $over[0])
            {
                //same tab continue
                return false;
            }
            //group - tab
            $tabs = $this->differentSort($active,$over,$tabs);
        }

        if($active_count == 5 && $over_count == 5)
        {
            // col - col
            if($active[2] == $over[2])
            {
                //same group
                [$keys,$last_key,$top_data] = $this->getTopData($active,$tabs);
                $top_data = HelperService::arrayMove($top_data,$active[4],$over[4]);
                Arr::set($tabs,$keys,$top_data);
            }else
            {
                //different group
                [$over_keys,$over_last_key,$over_top_data] = $this->getTopData($over,$tabs);
                //$over_top_data[] = $active_data;
                array_splice($over_top_data,$over_last_key + 1,0,[$active_data]);
                Arr::set($tabs,$over_keys, array_values($over_top_data));
                //remove the active data
                $tabs = $this->removeActive($active,$tabs);
            }
        }

        if($active_count == 5 && $over_count == 3)
        {
            //col - group
            if($active[2] == $over[2])
            {
                //same group continue
                return false;
            }
            $tabs = $this->differentSort($active,$over,$tabs,'columns');
        }

        if($active_count == 5 && $over_count == 1)
        {
            //col - tab
            return false;
        }


        $config['tabs'] = $tabs;

        $this->model->where(['id'=>$this->id])->update([$name=>json_encode($config)]);

        return true;
    }

     /**
     * 获取tabs中字段的索引及数据信息
     * form字段的结构为 tab - group - col 3维
     * @param [type] $tabs
     * @param [type] $uid
     * @return void
     */
    public function getColumnIndex($tabs,$uid)
    {
        $index = $index_data = false;
        foreach($tabs as $tk => $tab)
        {
            //如果是tab的话
            if(isset($tab['uid']) && $tab['uid'] == $uid)
            {
                $index = [$tk];
                $index_data = $tab;
            }else
            {
                foreach($tab['config'] as $gk => $group)
                {
                    if($group['uid'] == $uid)
                    {
                        $index = [$tk,'config',$gk];
                        $index_data = $group;
                    }elseif(isset($group['columns']))
                    {
                        foreach($group['columns'] as $ck => $column)
                        {
                            if($column['uid'] == $uid)
                            {
                                $index = [$tk,'config',$gk,'columns',$ck];
                                $index_data = $column;
                            }
                        }
                    }
                    
                }
            }
            
        }
        return [$index,$index_data];
    }
}