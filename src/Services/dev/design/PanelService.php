<?php
namespace Echoyl\Sa\Services\dev\design;

use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

class PanelService extends BaseService
{
    var $name = 'other_config';

    public function pieData($config)
    {
        return array_merge([
            'radius'=>0.9,'innerRadius'=>0.6
        ],$config);
    }

    public function tableData($config)
    {
        $cpage = Arr::get($config,'cpage',10);
        $pagination = Arr::get($config,'pagination',[]);
        
        if(!$cpage)
        {
            $pagination = false;
        }else
        {
            $pagination['pageSize'] = $cpage;
        }

        $config['pagination'] = $pagination;

        return $config;
    }

    public function tabData($config)
    {
        $cpage = Arr::get($config,'cpage',10);
        $pagination = Arr::get($config,'pagination',[]);
        
        if(!$cpage)
        {
            $pagination = false;
        }else
        {
            $pagination['pageSize'] = $cpage;
        }

        $config['pagination'] = $pagination;

        return $config;
    }

    public function colData($base,$active_data = [])
    {
        $type = Arr::get($base,'type');
        $config = Arr::get($base,'config',[]);
        $defaultConfig = Arr::get($base,'defaultConfig',[]);

        if($type == 'table')
        {
            $defaultConfig = $this->tableData($defaultConfig);
        }elseif($type == 'StatisticCard')
        {
            //配置chart
            $chart = Arr::get($config,'chart',[]);
            $new_chart = Arr::get($defaultConfig,'chart',[]);
            $chart = array_merge($chart,$new_chart);
            Arr::set($defaultConfig,'chart',$chart);
        }elseif($type == 'tab' || $type == 'rows')
        {
            $base['rows'] = $active_data['rows']??[];
        }

        $config = array_merge($config,$defaultConfig);
        Arr::set($base,'config',$config);
        return $base;
    }

    public function rowData($base)
    {
        return array_merge(['cols'=>[]],$base);
    }

    //计算列宽
    public function calSpan($cols)
    {
        //计算出已经设置过的span的宽度
        $total = 24;
        $spaned = 0;
        $un_len = 0;
        foreach($cols as $key=>$col)
        {
            if(isset($col['customer_span']) && $col['customer_span'])
            {
                $spaned += $col['customer_span'];
                $cols[$key]['span'] = $col['customer_span'];
            }else
            {
                $un_len++;
            }
        }

        $left = $total - $spaned;

        if($left < 1 || !$un_len)
        {
            return $cols;
        }
        //剩余的均分

        $span = floor($left / $un_len);

        if($span < 0)
        {
            return $cols;//没了
        }

        foreach($cols as $key=>$col)
        {
            if(isset($col['customer_span']) && $col['customer_span'])
            {
                $col['span'] = $col['customer_span'];
            }else
            {
                $col['span'] = $span;
            }

            $cols[$key] = $col;
        }

        return $cols;

    }

    public function setColSpan($active,$rows)
    {
        array_pop($active);
        $new_rows = Arr::get($rows,implode('.',$active));

        $new_rows = $this->calSpan($new_rows);

        Arr::set($rows,implode('.',$active),array_values($new_rows));
        return $rows;
    }

    public function isSameRow($active,$over)
    {
        array_pop($active);
        array_pop($over);
        return $active == $over;
    }

    public function sort($columns)
    {
        $config = $this->config;
        $name = $this->name;

        if(!isset($config['panel']))
        {
            $config['panel'] = [];
        }
        $rows = $config['panel'];

        [$active_item,$over_item] = $columns;

        [$active,$active_data] = $this->getColumnIndex($rows,$active_item['uid']);
        [$over] = $this->getColumnIndex($rows,$over_item['uid']);


        //$active_count = count($active);
        $over_count = count($over);
       
        if($active_item['devData']['itemType'] == 'col')
        {
            if($over_item['devData']['itemType'] == 'col')
            {
                //列到列
                if($this->isSameRow($active,$over))
                {
                    //same group
                    [$keys,$last_key,$top_data] = $this->getTopData($active,$rows);
                    $top_data = HelperService::arrayMove($top_data,$last_key,$over[$over_count - 1]);
                    $rows = $this->setData($rows,$keys,$top_data);
                }else
                {
                    //different group
                    [$over_keys,$over_last_key,$over_top_data] = $this->getTopData($over,$rows);
                    //$over_top_data[] = $active_data;
                    array_splice($over_top_data,$over_last_key + 1,0,[$active_data]);
                    $rows = $this->setData($rows,$over_keys,$over_top_data);
                    $rows = $this->setColSpan($over,$rows);
                    //remove the active data
                    $rows = $this->removeActive($active,$rows);
                    $rows = $this->setColSpan($active,$rows);
                }
            }elseif($over_item['devData']['itemType'] == 'row')
            {
                //列到行
                $rows = $this->differentSortp($active,$over,$rows,'cols');
            }
        }

        if($active_item['devData']['itemType'] == 'row')
        {
            
            if($over_item['devData']['itemType'] == 'col')
            {
                //行到列中
                $rows = $this->differentSortp($active,$over,$rows,'rows');
            }elseif($over_item['devData']['itemType'] == 'row')
            {
                //d($this->isSameRow($active,$over),$active,$over);
                //行到行后面
                if($this->isSameRow($active,$over))
                {
                    //same col
                    [$keys,$last_key,$top_data] = $this->getTopData($active,$rows);
                    $top_data = HelperService::arrayMove($top_data,$last_key,$over[$over_count - 1]);
                    $rows = $this->setData($rows,$keys,$top_data);
                }else
                {
                    //different col
                    [$over_keys,$over_last_key,$over_top_data] = $this->getTopData($over,$rows);
                    //$over_top_data[] = $active_data;
                    array_splice($over_top_data,$over_last_key + 1,0,[$active_data]);
                    $rows = $this->setData($rows,$over_keys,$over_top_data);
                    //remove the active data
                    $rows = $this->removeActive($active,$rows);
                }
            }
        }

        $config['panel'] = $rows;
        $this->model->where(['id'=>$this->id])->update([$name=>json_encode($config)]);
        return true;
    }

    public function differentSortp($active, $over, $rows, $name = 'cols')
    {
        
        $rows = $this->differentSort($active,$over,$rows,$name);

        if($name == 'cols')
        {
            //只有移动列的时候 才计算宽度。行没有宽度
            //原先的计算列
            $rows = $this->setColSpan($active,$rows);
            //over后的新列计算
            $over[] = $name;
            $keys = implode('.',$over);
            $target = Arr::get($rows,$keys);
            $target = $this->calSpan($target);
            Arr::set($rows,$keys,$target);
        }
        
        return $rows;
    }
    
    /**
     * 所有编辑操作
     *
     * @param ['editRow','editCol','addRow','insertRow','addCol','insertCol','deleteRow','deleteCol'] $base
     * @return void
     */
    public function edit($base,$action_type = 'addRow')
    {
        $config = $this->config;
        $name = $this->name;

        if(!isset($config['panel']))
        {
            $config['panel'] = [];
        }
        
        $uid = Arr::get($base,'uid');

        unset($base['id']);

        $rows = $config['panel'];

        [$active,$active_data] = $this->getColumnIndex($rows,$uid);


        if($action_type != 'insertRow' && !$active)
        {
            //处理最外层增加行外 其它操作都需要 uid
            return;
        }

        if(strpos($action_type,'add') !== false || strpos($action_type,'insert') !== false)
        {
            //新增的话 需要重新设置uid
            $base['uid'] = HelperService::uuid();
        }

        switch($action_type)
        {
            case 'editRow':
                //编辑行
                $update = array_merge($active_data,$base);
                //d($update);
                Arr::set($rows,implode('.',$active),$update);
                $rows = array_values($rows);
            break;
            case 'editCol':
                //编辑列
                $update = $this->colData($base,$active_data);
                //d($update);
                Arr::set($rows,implode('.',$active),$update);
                $rows = $this->setColSpan($active,$rows);
            break;
            case 'insertRow':
                //列中插入行 没有列 表示最外层插入行
                if(!$active)
                {
                    //默认最外层插入数据
                    $rows[] = $this->rowData($base);
                }else
                {
                    $base_row = $this->rowData($base);
                    $active_data['rows'] = isset($active_data['rows'])?$active_data['rows']:[];
                    $active_data['rows'][] = $base_row;
                    Arr::set($rows,implode('.',$active),$active_data);
                }
                break;
            case 'insertCol':
                //插入列
                $active_data['cols'][] = $this->colData($base);
                $active_data['cols'] = $this->calSpan($active_data['cols']);
                Arr::set($rows,implode('.',$active),$active_data);
            break;

            case 'addRow':
                //行后插入行
                [$keys,$last_key,$top_data] = $this->getTopData($active,$rows);
                array_splice($top_data,$last_key + 1,0,[$base]);
                if(!$keys)
                {
                    //如果是最外层直接设置
                    $rows = array_values($top_data);
                }else
                {
                    Arr::set($rows,$keys,array_values($top_data));
                }
            break;

            case 'addCol':
                //在列后插入列
                [$keys,$last_key,$top_data] = $this->getTopData($active,$rows);
                array_splice($top_data,$last_key + 1,0,[$this->colData($base)]);
                $top_data = $this->calSpan(array_values($top_data));
                
                Arr::set($rows,$keys,$top_data);
            break;
            case 'deleteRow':
            case 'deleteCol':
                Arr::forget($rows,implode('.',$active));
                $rows = $this->formatTopData($active,$rows);
                if($action_type == 'deleteCol')
                {
                    $rows = $this->setColSpan($active,$rows);
                }
            break;
        }

        $config['panel'] = $rows;
        $this->model->where(['id'=>$this->id])->update([$name=>json_encode($config)]);
        return true;
    }

    /**
     * 获取tabs中字段的索引及数据信息
     *
     * @param [type] $tabs
     * @param [type] $uid
     * @return void
     */
    public function getColumnIndex($rows,$uid)
    {
        $index = $index_data = false;
        foreach($rows as $rk => $row)
        {

            if($row['uid'] == $uid)
            {
                $index = [$rk];
                $index_data = $row;
            }else
            {
                if(isset($row['cols']))
                {
                    foreach($row['cols'] as $ck => $col)
                    {
                        if($col['uid'] == $uid)
                        {
                            $index = [$rk,'cols',$ck];
                            $index_data = $col;
                        }else
                        {
                            if(isset($col['rows']))
                            {
                                //如果列中存在行，进入行中查询
                                [$deep_index,$deep_data] = $this->getColumnIndex($col['rows'],$uid);
                                if($deep_index)
                                {
                                    //找到后
                                    $index = array_merge([$rk,'cols',$ck,'rows'],$deep_index);
                                    $index_data = $deep_data;
                                }
                            }
                        }
                        if($index)
                        {
                            break;
                        }
                    }
                }
                
            }
            if($index)
            {
                break;
            }
        }
        return [$index,$index_data];
    }
}