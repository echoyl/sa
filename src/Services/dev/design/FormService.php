<?php
namespace Echoyl\Sa\Services\dev\design;

use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

class FormService extends BaseService
{
    var $name = 'form_config';
    
    /**
     * 所有编辑操作
     * 除了增加组的操作外其它操作都要检测是否有空组，空组的话自动删除
     * @param [type] $base
     * @param ['edit','add','addTab','addGroup','delete'] $action_type
     * @param ['base','more'] $form_type
     * @return void
     */
    public function edit($base,$action_type = 'edit',$form_type = 'base')
    {
        $config = $this->config;
        
        $uid = Arr::get($base,'uid');

        if(!isset($config['tabs']))
        {
            //未设置默认设置一个初始化tab
            $config['tabs'] = [
                [
                    'tab'=>['title'=>'基础信息'],
                    'config'=>[],
                    'uid'=>HelperService::uuid()
                ]
            ];
            $this->config['tabs'] = $config['tabs'];
        }

        $tabs = $config['tabs'];

        if($action_type == 'setColumns')
        {
            //form 快速设置列字段展示
            //勾选后未增加操作 会增加一个组加当前字段的列。
            $checked = $base['checked'];
            unset($base['checked']);
            if($checked)
            {
                //勾选 新增
                //增加一个group
                //检测如果有columns字段表示是分组，类型变为addgroup
                $action_type = 'quickAdd';
                $active = [0];
                $old_value = $tabs[0];
                if(isset($base['columns']))
                {
                    //复制行
                    $action_type = 'addGroup';
                }elseif(isset($base['tab']))
                {
                    //复制tab
                    $action_type = 'addTab';
                    $active = false;
                }
            }else
            {
                $key = $base['key'];
                //取消勾选 删除
                $action_type = 'delete';
                [$active,$old_value] = $this->getColumnIndex($tabs,$key,'key');
            }
            //d($active,$old_value);
        }else
        {
            [$active,$old_value] = $this->getColumnIndex($tabs,$uid);
        }

        if($action_type == 'copyToMenu')
        {
            return $this->copyToMenu($base,$old_value);
        }

        //d('test');

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
            $copy = Arr::get($base,'copy');
            if(!isset($base['uid']) || !$copy)
            {
                $base['uid'] = $new_uid;
            }
            if(!isset($base['config']))
            {
                $base['config'] = [];
            }
            
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
            $copy = Arr::get($base,'copy');
            if(!isset($base['uid']) || !$copy)
            {
                $base['uid'] = $new_uid;
            }
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
        }elseif($action_type == 'quickAdd')
        {
            //快速新增列
            $keys = implode('.',$active);
            $copy = Arr::get($base,'copy');
            //如果是复制过来的列，就不使用新的uid，这样可以实现复制更新列
            if(!isset($base['uid']) || !$copy)
            {
                $base['uid'] = $new_uid;
            }
            $group = [
                'columns'=>[
                    $base
                ],
                'uid'=>HelperService::uuid()
            ];

            $active = [0,'conifg',count($tabs[0]['config']) - 1];
            //在组中插入列
            $old_value['config'][] = $group;
            Arr::set($tabs,$keys,$old_value);
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
        if($action_type != 'addGroup')
        {
            //处理添加分组外其他操作都要删除掉空的分组
            $tabs = $this->clearEmptyGroup($tabs);
        }
        $config['tabs'] = $tabs;
        $this->updateConfig($config);
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
        if(!$to_id)
        {
            return 0;
        }
        $new_form_service = new FormService($to_id);
        //如果之前复制过，后检测为跟新同步列，而不再插入数据
        [$active,$old_value] = $new_form_service->getColumnIndex([],$col['uid']);
        if($active)
        {
            $new_form_service->edit($col,'edit');
        }else
        {
            $col['copy'] = true;
            $col['checked'] = true;
            $new_form_service->edit($col,'setColumns');
        }
        
        return $to_id;
    }

    /**
     * 清除空组
     *
     * @param [type] $tabs
     * @return void
     */
    public function clearEmptyGroup($tabs)
    {
        foreach($tabs as $tab_key=>$tab)
        {
            $configs = $tab['config'];

            foreach($configs as $config_key=>$config)
            {
                if(empty($config['columns']))
                {
                    unset($configs[$config_key]);
                }
            }
            $tab['config'] = array_values($configs);
            $tabs[$tab_key] = $tab;
        }

        return $tabs;
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
                //$tabs = $this->formatTopData($active,$tabs);
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

        $tabs = $this->clearEmptyGroup($tabs);

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
    public function getColumnIndex($tabs,$uid,$key_name = 'uid')
    {
        if(empty($tabs))
        {
            $config = $this->config;
            $tabs = $config?$config['tabs']:[];
        }
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
                    if(!isset($group['uid']))
                    {
                        continue;
                    }
                    if($group['uid'] == $uid)
                    {
                        $index = [$tk,'config',$gk];
                        $index_data = $group;
                    }elseif(isset($group['columns']))
                    {
                        foreach($group['columns'] as $ck => $column)
                        {
                            if($column[$key_name] == $uid)
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