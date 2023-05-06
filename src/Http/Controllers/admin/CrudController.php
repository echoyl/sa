<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Services\HelperService;

/**
 * 后台crud基础类 都走这个
 * @method mixed afterPost($id,$data)  提交完数据后通过返回id再继续操作
 * @method mixed beforePost($data,$id = 0)  提交数据前的检测数据
 * @method mixed handleSearch() 数据列表中额外的搜索调价等
 * @method mixed postData(&$item) 获取数据时格式化数据
 * @method mixed checkPost($item) 检测是否可以提交数据
 * @method mixed listData(&$list) 列表数据格式化
 */
class CrudController extends ApiBaseController
{
    public $model;
    public $with_column = [];
    public $with_sum = [];//hasmany需要求和的配置
    public $dont_post_columns = []; //多余字段不需要提交数据库
    public $default_post = [];
    public $json_columns = [];
    public $displayorder = [];
    public $can_be_null_columns = []; //可以设置为空的字段
    public $with_count = [];//计算总数量
    public $select_columns = [];

    public $model_id = 0;//系统所选的模型id值

    var $service;

    /**
     * 搜索项配置
     */
    public $search_config = [];

    public $parse_columns = [];
    public $withs = [];

    public function defaultSearch($m)
    {
        $title = request('title', '');
        if ($title) {
            $m = $m->where([['title', 'like', '%' . urldecode($title) . '%']]);

        }

        $model_parse_columns = $this->getParseColumns();

        $parse_columns = !empty($model_parse_columns)?$model_parse_columns:$this->parse_columns;
        foreach ($parse_columns as $col) {
            $name = $col['name'];
            $type = $col['type'];
            $search_val = request($name, '');
            if (empty($search_val) && $search_val !== 0) {
                continue;
            }

            if(isset($col['table_menu']) && $col['table_menu'])
            {
                //d($search_val);
                if($search_val != 'all')
                {
                    if(is_array($search_val))
                    {
                        $m = $m->whereIn($name, $search_val);
                    }else
                    {
                        $m = $m->where($name, $search_val);
                    }
                    
                }
                continue;
            }

            switch ($type) {
                case 'state':
                    $m = $m->where($name, $search_val);
                    break;
                case 'cascader': //单选分类
                    if (is_numeric($search_val)) {
                        $category_id = [$search_val];
                    } else {
                        $category_id = json_decode($search_val, true);
                    }

                    if (isset($col['class'])) {
                        $cids = [];
                        $cmodel = new $col['class'];
                        foreach ($category_id as $cid) {
                            if (is_array($cid)) {
                                $cid = array_pop($cid);
                            }

                            $_cids = $cmodel->childrenIds($cid);
                            $cids = array_merge($cids, $_cids);
                        }
                        $m = $m->whereIn($name, $cids);
                    }

                    break;
                case 'cascaders': //分类多选
                    if (is_numeric($search_val)) {
                        $category_id = [$search_val];
                    } else {
                        $category_id = json_decode($search_val, true);
                    }

                    if (isset($col['class'])) {
                        $cids = [];
                        $cmodel = new $col['class'];
                        foreach ($category_id as $cid) {
                            if (is_array($cid)) {
                                $cid = array_pop($cid);
                            }

                            $_cids = $cmodel->childrenIds($cid);
                            $cids = array_merge($cids, $_cids);
                        }
                        $cids = array_unique($cids);
                        //d($cids);
                        if (!empty($cids)) {
                            $m = $m->where(function ($q) use ($cids, $name) {
                                foreach ($cids as $cid) {
                                    $q->orWhereRaw("FIND_IN_SET(?,{$name})", [$cid]);
                                }
                            });
                        }
                    }

                    break;
                case 'selects':
                    if (is_numeric($search_val)) {
                        $category_id = $search_val;
                    } else {
                        $json = HelperService::json_validate($search_val);
                        if($json)
                        {
                            $category_id = $json;
                        }else
                        {
                            $category_id = [$search_val];
                        }
                        $len = count($category_id);
                        if($len <= 0)
                        {
                            break;
                        }
                        $category_id = array_pop($category_id);
                    }

                    $m = $m->whereRaw("FIND_IN_SET(?,{$name})", [$category_id]);
                    break;
                case 'pca':
                    //地区筛选
                    $search_val = json_decode($search_val, true);
                    $keys = ['province','city','area'];
                    foreach($search_val as $k=>$v)
                    {
                        if(isset($keys[$k]))
                        {
                            $m = $m->where($keys[$k],$v);
                        }
                        
                    }
                    break;
            }
        }

        $state = request('state', 'all');
        if ($state != 'all') {
            $m = $m->where('state', $state);

        }

        $startTime = request('startTime', '');
        $endTime = request('endTime', '');

        if ($startTime) {
            $m = $m->where([['created_at', '>=', $startTime]]);
        }
        if ($endTime) {
            $m = $m->where([['created_at', '<=', date("Y-m-d H:i:s", strtotime($endTime) + 3600 * 24 - 1)]]);
        }

        //搜索配置 自动根据name搜索对应的字段
        if(!empty($this->search_config))
        {
            foreach($this->search_config as $sc)
            {
                $name = $sc['name'];
                
                $columns = $sc['columns'];
                $type = $sc['type']??'';
                
                if($type == 'has')
                {
                    $search_val = request(HelperService::uncamelize($name),'');
                    $m = HelperService::searchWhereHas($m,$name,$columns,$search_val);
                }else
                {
                    $search_val = request($name,'');
                    
                    $where_type = $sc['where_type']??'=';
                    if($where_type == 'whereBetween' || $where_type == 'whereIn')
                    {
                        
                        if($search_val)
                        {
                            if(is_numeric($search_val))
                            {
                                $search_val = [$search_val];
                            }else
                            {
                                $search_val = is_string($search_val) ? json_decode($search_val,true):$search_val;
                            }
                            $m = $m->$where_type($columns[0],$search_val);
                        }
                    }else
                    {
                        $m = HelperService::searchWhere($m,$columns,$search_val,$where_type);
                    }
                    
                }
                
            }
        }

        return $m;
    }

    public function index()
    {
        $psize = request('pageSize', 10);
        $page = request('current', 1);

        $search = [];

        if (method_exists($this, 'handleSearch')) {
            [$m, $search] = $this->handleSearch();
        } else {
            $m = $this->model;
        }

        $this->parseWiths($search);

        $m = $this->defaultSearch($m);

        $count = $m->count();
        $select_columns = request('select','');
        if (!empty($this->with_column) && empty($select_columns)) {
            $m = $m->with($this->with_column);
        }
        if (!empty($this->with_count)) {
            $m = $m->withCount($this->with_count);
        }

        if (!empty($this->with_sum)) {
            foreach($this->with_sum as $with_sum)
            {
                $m = $m->withSum($with_sum[0],$with_sum[1]);
            }
            
        }

        $has_id = false;
        $sort_type = ['descend' => 'desc', 'ascend' => 'asc'];
        if (request('sort')) {
            //添加排序检测
            $sort = json_decode(request('sort'), true);
            if (!empty($sort)) {
                foreach ($sort as $skey => $sval) {
                    $m = $m->orderBy($skey, $sort_type[$sval] ?? 'desc');
                }

                $has_id = true;
            }
        }

        if (!empty($this->displayorder)) {
            foreach ($this->displayorder as $val) {
                $m = $m->orderBy($val[0], $val[1]);
            }
        } else {
            //默认按照id排序
            if (!$has_id) {
                $m = $m->orderBy('id', 'desc');
            }

        }
        if(!empty($this->select_columns))
        {
            $m = $m->select($this->select_columns);
        }else
        {
            
            if(!empty($select_columns))
            {
                $m = $m->select($select_columns);
            }
        }
        $list = $m->offset(($page - 1) * $psize)
            ->limit($psize)
            ->get()->toArray();

        foreach ($list as $key => $val) {
            $this->parseData($val, 'decode', 'list');
            $list[$key] = $val;
        }

        if (method_exists($this, 'listData')) {
            $this->listData($list);
        }
        return $this->list($list, $count, $search);

    }

    public function show()
    {
        return $this->post();
    }

    public function store()
    {
        return $this->post();
    }

    public function post()
    {
        //sleep(10);
        $id = request('id', 0);
        $id = $id ?: request('base.id', 0);
        $m = $this->model;
        if (!empty($this->with_column)) {
            $m = $m->with($this->with_column);
        }
        if(!is_array($id))
        {
            $item = $m->where(['id' => $id])->first();

            if (!empty($item)) {
                $item = $item->toArray();
                if (method_exists($this, 'checkPost')) {
                    $ret = $this->checkPost($item, $id); //编辑数据检测
                    if ($ret) {
                        return $ret;
                    }
                }
            } else {
                $item = $this->default_post; //数据的默认值
                $item['created_at'] = now()->toDateTimeString();
            }
        }else
        {
            if (method_exists($this, 'beforeMorePost')) {
                $ret = $this->beforeMorePost($id); //批量操作检测所有数据id值
                if ($ret) {
                    return $ret;
                }
            }
        }
        $type = request('actype');

        if (request()->isMethod('post')) {
            switch ($type) {
                case 'status':
                    $name = request('field', 'status');
                    if ($name == 'status') {
                        $val = request('status');
                    } else {
                        $val = request('val');
                    }
                    $data = [$name => $val];
                    break;
                case 'state':
                    $val = request('state');
                    $data = ['state' => $val];
                    //批量操作
                    $this->parseData($data, 'encode', 'update');
                    $this->model->whereIn('id',$id)->update($data);
                    return $this->success();
                    break;
                case 'displayorder':
                    $data = ['displayorder' => intval(request('displayorder'))];
                    break;
                default:
                    $data = filterEmpty(request('base'), $this->can_be_null_columns); //后台传入数据统一使用base数组，懒得每个字段赋值

                    //设置不需要提交字段
                    if (!empty($this->dont_post_columns)) {
                        foreach ($this->dont_post_columns as $c) {
                            if (isset($data[$c])) {
                                unset($data[$c]);
                            }
                        }
                    }
                    //json数据列
                    if (!empty($this->json_columns)) {
                        foreach ($this->json_columns as $c) {
                            if (isset($data[$c])) {
                                $data[$c] = json_encode($data[$c]);
                            }
                        }
                    }

                    if (method_exists($this, 'beforePost')) {
                        $ret = $this->beforePost($data, $id,$item); //操作前处理数据 如果返回数据表示 数据错误 返回错误信息
                        if ($ret) {
                            return $ret;
                        }
                    }

            }
            //d($data);
            if (!empty($id)) {
                $this->parseData($data, 'encode', 'update');
                $this->model->where(['id' => $id])->update($data);
            } else {
                $data['created_at'] = $data['created_at']??now();
                $this->parseData($data);
                $id = $this->model->insertGetId($data);
            }
            $ret = null;
            

            //返回插入或更新后的数据
            $new_data = $this->model->where(['id' => $id])->with($this->with_column)->first()->toArray();

            if (method_exists($this, 'afterPost')) {
                $ret = $this->afterPost($id,$new_data); //数据更新或插入后的 补充操作
            }

            $this->parseData($new_data, 'decode', 'list');
            if(method_exists($this,'postData'))
            {
                $this->postData($new_data);
            }
            
            return $ret ?: $this->success($new_data);
        } else {
            $this->parseData($item, 'decode');
            $this->parseWiths($item);
            if (method_exists($this, 'postData')) {
                $this->postData($item); //postData为预处理数据格式
            }
        }

        //json数据列
        if (!empty($this->json_columns)) {
            foreach ($this->json_columns as $c) {
                if (isset($item[$c]) && $item[$c]) {
                    $item[$c] = json_decode($item[$c], true);
                } else {
                    //$item[$c] = [];
                }
            }
        }
        return $this->success($item);
    }

    public function destroy()
    {
        $id = request('id', 0);
        if ($id) {
            if (!is_array($id)) {
                $id = [$id];
            }
        }
        if (!empty($id)) {
            $m = $this->beforeDestroy($this->model->whereIn('id', $id));

            $items = $m->get();
            foreach ($items as $val) {
                $val->delete();
            }
            return ['code' => 0, 'msg' => '删除成功'];
        }
        return ['code' => 1, 'msg' => '参数错误'];
    }

    public function beforeDestroy($m)
    {
        return $m;
    }

    public function copyOne()
    {
        $id = request('id', 0);
        $item = $this->model->where(['id' => $id])->first();
        if (!$item) {
            return ['code' => 1, 'msg' => '操作失败'];
        }
        $data = $item->toArray();
        unset($data['id']);
        $data['title'] .= '-副本';
        $this->model->insert($data);
        return ['code' => 0, 'msg' => '复制成功'];
    }

    public function parseWiths(&$data)
    {
        $ret = [];
        foreach ($this->withs as $with) {
            $name = $with['name'] . 's';
            if(isset($with['class']))
            {
                $data[$name] = (new $with['class'])->format($with['cid']??0);
            }elseif(isset($with['data']))
            {
                $data[$name] = $with['data'];
            }
            $ret[] = $with['name'];
        }

        $model_parse_columns = $this->getParseColumns();

        $parse_columns = !empty($model_parse_columns)?$model_parse_columns:$this->parse_columns;

        $table_menu = [];

        foreach ($parse_columns as $with) {
            if(isset($with['with']))
            {
                $name = $with['name'] . 's';
                if(isset($with['class']))
                {
                    $data[$name] = (new $with['class'])->format($with['cid']??0);
                }elseif(isset($with['data']))
                {
                    $data[$name] = $with['data'];
                }
            }
            
            $ret[] = $with['name'];
            //检测是否有table_menu设置
            if(isset($with['table_menu']) && !isset($data['table_menu']))
            {
                $table_menu[$with['name']] = $with['data']??[];
            }
        }

        if(!empty($table_menu))
        {
            $data['table_menu'] = $table_menu;
        }

        return $ret;
    }

    public function getParseColumns()
    {
        if(method_exists($this->model,'getParseColumns'))
        {
            return $this->model->getParseColumns();
        }else
        {
            return [];
        }
    }

    public function parseData(&$data, $in = 'encode', $from = 'detail',$parse_columns = [])
    {
        $unsetNames = [];
        $is_first = true;
        if(!empty($parse_columns))
        {
            $is_first = false;
        }

        $model_parse_columns = $this->getParseColumns();

        $parse_columns = !empty($parse_columns)?$parse_columns:(!empty($model_parse_columns)?$model_parse_columns:$this->parse_columns);

        foreach ($parse_columns as $col) {
            $name = $col['name'];
            $type = $col['type'];
            $encode = $in == 'encode'?true:false;

            $isset = isset($data[$name])?true:false;
            $need_set = true;//如果是true表示需要 将$data[$name] 赋值一遍
            if (!$isset && $from == 'update') {
                //更新数据时 不写入默认值
                //d($this->parse_columns,$this->can_be_null_columns);
                if(!in_array($name,$this->can_be_null_columns))
                {
                    continue;
                }
            }
            $col['default'] = $col['default']??"";

            $val = $isset ? $data[$name] : $col['default'];
            switch ($type) {
                case 'model':
                    if($encode)
                    {
                        //提交数据时 不需要处理 将数据删除
                        $val = '__unset';
                    }else
                    {
                        $cls = new $col['class'];
                        $cls_p_c = $cls->getParseColumns();
                        if(!empty($cls_p_c) && $is_first && $isset)
                        {
                            //model类型只支持1级 多级的话 需要更深层次的with 这里暂时不实现了
                            //思路 需要在生成controller文件的 with配置中 继续读取关联模型的关联
                            $this->parseData($val,$in,$from,$cls_p_c);
                        }
                    }
                    
                    break;
                case 'image':
                    if(!$val && !$encode)
                    {
                        $val = '__unset';
                    }else
                    {
                        $val = HelperService::uploadParse($val ?? '', $encode ? true : false,$from == 'list'?['p'=>'s']:[]);
                    }
                    break;
                case 'cascader':
                case 'cascaders':
                    $_name = '_' . $name;
                    if ($encode) {
                        if (!empty($val)) {
                            $data[$_name] = json_encode($val);
                            $__val = [];
                            $val_len = count($val);
                            foreach ($val as $_key => $_val) {
                                if (is_numeric($_val)) {
                                    if($type == 'selects')
                                    {
                                        $__val[] = $_val;
                                    }else
                                    {
                                        if ($_key == $val_len - 1) {
                                            $__val[] = $_val;
                                        }
                                    }
                                } elseif (is_array($_val)) {
                                    $__val[] = array_pop($_val);
                                }
                            }
                            $val = implode(',', $__val);
                        } else {
                            $val = 0;
                        }
                    } else {
                        $val = isset($data[$_name]) && $data[$_name] ? json_decode($data[$_name], true) : '';
                    }
                    break;
                case 'select':
                    if ($encode) {
                        $val = is_numeric($val)?intval($val):$val;
                    }else{
                        if($val && $isset)
                        {
                            $val = is_numeric($val)?intval($val):$val;
                        }else
                        {
                            $val = '__unset';
                        }
                    }
                    break;
                case 'selects':
                    //select 不需要而外字段了
                    if ($encode) {
                        $val = is_array($val)?implode(',',$val):'';
                    }else{
                        if($val && $isset)
                        {
                            $val = is_string($val)?explode(',',$val):$val;
                            foreach($val as $k=>$v)
                            {
                                if(is_numeric($v))
                                {
                                    $val[$k] = intval($v);
                                }
                            }
                        }else
                        {
                            $val = '__unset';
                            
                        }
                    }
                    break;
                case 'state':
                    
                    if ($encode) {
                        if ($val == 1 || $val == 'enable') {
                            $val = 'enable';
                        } else {
                            $val = 'disable';
                        }
                    } else {
                        if ($from == 'detail') {
                            $val = $val == 'enable' ? true : false;
                        }
                    }
                    break;
                case 'pca':
                    if ($encode) {
                        if($isset)
                        {
                            if(isset($val[0]))
                            {
                                $data['province'] = $val[0];
                            }
                            if(isset($val[1]))
                            {
                                $data['city'] = $val[1];
                            }
                            if(isset($val[2]))
                            {
                                $data['area'] = $val[2];
                            }
                            if(!in_array($name,['province','city','area']))
                            {
                                //如果用了其它字段需要将该字段移除
                                $val = '__unset';
                            }else
                            {
                                $need_set = false;//字段重复不需要再设值了
                            }
                        }
                    } else {
                        if(isset($data['province']) && $data['province'])
                        {
                            $level = $col['level']??3;
                            if($level == 2)
                            {
                                $val = [$data['province'],$data['city']];
                            }elseif($level == 1)
                            {
                                $val = [$data['province']];
                            }else
                            {
                                $val = [$data['province'],$data['city'],$data['area']];
                            }
                            
                        }
                        
                    }
                    break;
                case 'latlng':
                case 'tmapInput':
                    if ($encode) {
                        if($isset)
                        {
                            if(isset($val[0]))
                            {
                                $data['lat'] = $val[0];
                            }
                            if(isset($val[1]))
                            {
                                $data['lng'] = $val[1];
                            }
                            if(!in_array($name,['lat','lng']))
                            {
                                //如果用了其它字段需要将该字段移除
                                $val = '__unset';
                            }else
                            {
                                $need_set = false;//字段重复不需要再设值了
                            }
                        }
                    } else {
                        if(isset($data['lat']) && $data['lat'])
                        {
                            $val = [$data['lat'],$data['lng']];
                        }
                    }
                    break;
                case 'price':
                    if($encode)
                    {
                        if($isset)
                        {
                            $val = intval($val * 100);
                        }
                    }else
                    {
                        if($isset)
                        {
                            $val = floatval($val / 100);
                        }
                    }
                    break;
                case 'with':
                    if($isset && $val)
                    {
                        foreach($val as $k=>$v)
                        {
                            $new_key = implode('_',[$name,$k]);
                            $data[$new_key] = $v;
                        }
                    }
                    break;
                case 'search_select':
                    //表单类型为 搜索select时
                    $id_name = $col['value']??'id';
                    if($encode)
                    {
                        if($isset && $val)
                        {
                            $val = $val[$id_name];
                        }
                    }else
                    {
                        //特定字段名称 label value
                        if(isset($col['data_name']) && isset($data[$col['data_name']]) && $isset)
                        {
                            $d = $data[$col['data_name']];
                            if(!$d)
                            {
                                $val = '__unset';
                            }else
                            {
                                $val = ['label'=>$d[$col['label']??'title'],'value'=>$d[$id_name],$id_name=>$d[$id_name]];
                            }
                            
                        }
                        if(!$val || !$isset)
                        {
                            $val = '__unset';
                        }
                        
                    }
                    break;
                case 'password':
                    if($encode)
                    {
                        if($isset && $val)
                        {
                            $val = md5($val);
                        }
                    }else
                    {
                        //特定字段名称 label value
                        $val = '__unset';
                    }
                    break;
                case 'link':
                    //如果字段是链接类型 返回数据的时候渲染成链接的格式
                    if($encode)
                    {
                        $val = '__unset';
                    }else
                    {
                        if ($from == 'list') 
                        {
                            $uris = [];
                            if(isset($col['uri']) && is_array($col['uri']))
                            {
                                foreach($col['uri'] as $uri)
                                {
                                    $uris[] = implode('=',[$uri[0],$data[$uri[1]]]);
                                }
                            }
                            $val = [
                                'title'=>$val,
                                'href'=>implode('?',[$col['path'],implode('&',$uris)])
                            ];
                        }else
                        {
                            $val = '__unset';
                        }
                        
                    }
                    break;
                case 'json':
                    if($encode)
                    {
                        if($val && !is_string($val))
                        {  
                            $val = json_encode($val);
                        }
                    }else
                    {
                        if($val)
                        {  
                            $val = json_decode($val,true);
                        }
                    }
                    break;
                // case 'enum':
                //     if($from == 'list' && $isset)
                //     {
                //         $val = $col['data'][$val]['value'];
                //     }
                //     break;
            }
            if($val === '__unset')
            {
                
                $unsetNames[] = $name;
                unset($data[$name]);
                continue;
            }
            if($need_set)
            {
                $data[$name] = $val;
            }
        }

        return $unsetNames;
    }

}
