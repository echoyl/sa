<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Services\admin\LocaleService;
use Echoyl\Sa\Services\dev\crud\CrudService;
use Echoyl\Sa\Services\dev\crud\ParseData;
use Echoyl\Sa\Services\dev\crud\relation\Relation;
use Echoyl\Sa\Services\export\ExcelService;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

/**
 * 后台crud基础类 都走这个
 * @method mixed afterPost($id,$data)  提交完数据后通过返回id再继续操作
 * @method mixed beforePost(&$data,$id = 0,$item = [])  提交数据前的检测数据
 * @method mixed beforeMultiplePost(&$data,$id = []) 批量操作前检测
 * @method mixed handleSearch($search = []) 数据列表中额外的搜索条件等
 * @method mixed postData(&$item) 获取数据时格式化数据
 * @method mixed checkPost($item) 检测是否可以提交数据
 * @method mixed listData(&$list) 列表数据格式化
 * @method mixed setThis() 设置一个值 在select 获取数据的时候可以当做filter条件使用
 * @method mixed exportFormatData($val) 导出数据格式化数据方法
 * @method mixed beforeDestroy($m)  删除数据前的检测数据
 * @method mixed afterDestroy($item)  删除数据后置操作
 * @method mixed setBase($base)  post数据后操作中可以自定义设置base数据的方法
 * @property \App\Services\AdminAppService $service
 * @property \Illuminate\Database\Eloquent\Model $model
 */
class CrudController extends ApiBaseController
{
    public $model;
    public $model_class;
    public $with_column = [];
    public $with_sum = [];//hasmany需要求和的配置
    public $dont_post_columns = []; //多余字段不需要提交数据库
    public $default_post = [];
    public $json_columns = [];
    public $displayorder = [];
    public $can_be_null_columns = []; //可以设置为空的字段
    public $with_count = [];//计算总数量
    public $select_columns = [];
    public $uniqueFields = [];//检测唯一字段

    public $model_id = 0;//系统所选的模型id值

    var $service;
    var $action_type = '';//操作类型 add edit 
    var $is_post = false;
    var $page_size = 10;
    var $withTrashed = false;
    var $group_by = '';
    var $category_fields = [//分类字段检测，如果某个菜单有预设cid
        [
            'request_name'=>'cid',//请求参数名 可以是数组
            'field_name'=>'category_id',//数据库字段名 
        ]
    ];

    var $listDataName = 'listData';
    var $handleSearchName = 'handleSearch';

    /**
     * @var array 自定义失败消息提醒文字
     */
    var $fail_reason_customer = [];

    /**
     * @var array 搜索项配置
     */
    public $search_config = [];

    public $parse_columns = [];
    public $withs = [];

    public function defaultSearch($m)
    {
        $origin_model = $this->getModel();
        $m = $this->globalDataSearch($m,$origin_model);
        //
        $title = request('title', '');
        if ($title) {
            $has_customer_search = collect($this->search_config)->first(function($item){
                return $item['name'] == 'title';
            });
            if(!$has_customer_search)
            {
                $m = LocaleService::search($m,['title', 'like', '%' . urldecode($title) . '%'],$origin_model);
            }
        }

        $parse_columns = $this->getParseColumns();

        $ps = new ParseData($this->getModelClass(),['category_fields'=>$this->category_fields]);

        foreach ($parse_columns as $col) {

            $cs = new CrudService([
                'col'=>$col,
            ]);

            $name = $col['name'];
            $type = $col['type'];
            $search_val = request($name, '');

            $check_search_val = $ps->checkCategoryField($name);
            
            $search_val = $check_search_val['search_val'];

            if(isset($col['table_menu']) && $col['table_menu'])
            {
                if($search_val != 'all' && $search_val !== '')
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

            if (empty($search_val) && $search_val !== 0) {
                //无查询值情况的默认查询
                switch ($type) {
                    case 'cascader': //单选分类
                    case 'cascaders':
                    case 'selects':
                    case 'select':
                        if(isset($col['class']) && isset($col['cid']) && $col['cid'])
                        {
                            $cmodel = new $col['class'];
                            $big_cids = $cmodel->childrenIds($col['cid']);
                            $m = $m->whereIn($name, $big_cids);
                        }
                        break;
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
                        $category_id = is_array($search_val)?$search_val:json_decode($search_val, true);
                    }

                    if (isset($col['class'])) {
                        $cids = [];
                        $cmodel = new $col['class'];
                        //检测$category_id是二位数组还是一维数组
                        $is_2 = false;
                        foreach($category_id as $cid)
                        {
                            if(is_array($cid))
                            {
                                //只要有一个子项是数组 则 传入的就是多选
                                $is_2 = true;
                                $cid = array_pop($cid);
                                $_cids = $cmodel->childrenIds($cid);
                                $cids = array_merge($cids, $_cids);
                            }
                        }
                        if(!$is_2)
                        {
                            $cid = array_pop($category_id);
                            $_cids = $cmodel->childrenIds($cid);
                            $cids = array_merge($cids, $_cids);
                        }
                        if(isset($col['cid']) && $col['cid'])
                        {
                            $big_cids = $cmodel->childrenIds($col['cid']);
                            $cids = array_intersect($big_cids,$cids);
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

                    if (isset($col['class']) && is_array($category_id)) {
                        $cids = [];
                        $cmodel = new $col['class'];

                        //这里检测是否是多选还是单选
                        $mutiple = true;
                        foreach ($category_id as $cid) {
                            if (!is_array($cid)) {
                                //某个元素不是数组 表示 是单选
                                $mutiple = false;
                                break;
                            }
                        }

                        if($mutiple)
                        {
                            foreach ($category_id as $cid) {
                                if (is_array($cid)) {
                                    $cid = array_pop($cid);
                                }
    
                                $_cids = $cmodel->childrenIds($cid);
                                $cids = array_merge($cids, $_cids);
                            }
                        }else
                        {
                            $cid = array_pop($category_id);
                            $cids = $cmodel->childrenIds($cid);
                        }

                        
                        if(isset($col['cid']) && $col['cid'])
                        {
                            $big_cids = $cmodel->childrenIds($col['cid']);
                            $cids = array_intersect($big_cids,$cids);
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
                case 'checkbox':
                    if (is_numeric($search_val)) {
                        $category_id = [$search_val];
                    } else {
                        $json = HelperService::json_validate($search_val);
                        //d(empty($json));
                        if($json !== false)
                        {
                            if(empty($json))
                            {
                                break;
                            }
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
                        //$category_id = array_pop($category_id);
                    }
                    $m = $m->where(function($query) use($category_id,$name){
                        foreach($category_id as $k=>$id)
                        {
                            if(!$k)
                            {
                                $query->whereRaw("FIND_IN_SET(?,{$name})", [$id]);
                            }else
                            {
                                $query->orWhereRaw("FIND_IN_SET(?,{$name})", [$id]);
                            }
                        }
                    });

                    
                    break;
                case 'select':
                    $m = $m->where($name,$search_val);
                    break;
                case 'pca':
                    $m = $cs->search($m,'pca',['search_val'=>$search_val]);
                    break;
            }
        }

        $state = request('state', 'all');
        if ($state != 'all') {
            $m = $m->where('state', $state);

        }

        // $startTime = request('startTime', '');
        // $endTime = request('endTime', '');

        // if ($startTime) {
        //     $m = $m->where([['created_at', '>=', $startTime]]);
        // }
        // if ($endTime) {
        //     $m = $m->where([['created_at', '<=', date("Y-m-d H:i:s", strtotime($endTime) + 3600 * 24 - 1)]]);
        // }

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
                    //d($search_val);
                    if(isset($sc['default']) && $sc['default'] && $search_val == $sc['default'])
                    {
                        //如果等于默认值 修改查询类型为 doesntHave
                        $m = $m->doesntHave($name);
                    }else
                    {
                        $m = HelperService::searchWhereHas($m,$name,$columns,$search_val);
                    }
                }else
                {
                    $search_val = request($name,'');
                    
                    $where_type = $sc['where_type']??'=';
                    if($where_type == 'has' || $where_type == 'doesntHave')
                    {
                        if($search_val)
                        {
                            if(is_array($columns))
                            {
                                $columns = implode('.',$columns);
                            }
                            $m = $m->$where_type($columns);
                        }
                    }elseif($where_type == 'whereBetween' || $where_type == 'whereIn')
                    {
                        $m = HelperService::searchWhereBetweenIn($m,$columns,$search_val,$where_type);
                    }else
                    {
                        $m = HelperService::searchWhere($m,$columns,$search_val,$where_type,['origin_model'=>$origin_model]);
                    }
                    
                }
                
            }
        }

        return $m;
    }

    public function handleSearch($search = [])
    {
        $m = $this->model;
        //系统渲染search数据后 还可以自定义handleSearch 修改注入信息
        return [$m,$search];
    }

    /**
     * 处理排序 如果请求有sort参数根据该参数排序，否则按照displayorder排序，默认按id倒叙
     *
     * @param \Illuminate\Database\Eloquent\Model $m
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function handleSort($m)
    {
        $has_id = false;
        $sort_type = ['descend' => 'desc', 'ascend' => 'asc'];
        $sort = request('sort');
        if ($sort) {
            //添加排序检测
            $sort = is_string($sort)?json_decode(request('sort'), true):$sort;
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
        return $m;
    }

    public function index()
    {
        $psize = request('pageSize', $this->page_size);
        $page = request('current', 1);
        $this->action_type = 'list';
        $search = [];

        $this->parseWiths($search);

        if (method_exists($this, $this->handleSearchName)) 
        {
            $method = $this->handleSearchName;
            [$m, $csearch] = $this->$method($search);
        }else
        {
            [$m, $csearch] = $this->handleSearch($search);
        }

        $search = array_merge($search,$csearch);

        $m = $this->defaultSearch($m);

        if($this->group_by)
        {
            $count = $m->distinct($this->group_by)->count();
            $m = $m->groupBy($this->group_by);
        }else
        {
            $count = $m->count();
        }

        
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

        //处理排序
        $m = $this->handleSort($m);
        
        if(!empty($this->select_columns))
        {
            $m = $m->select($this->select_columns);
        }else
        {
            
            if(!empty($select_columns))
            {
                //$m = $m->select($select_columns);
                //清除前端传来的select列
            }
        }
        $list = $m->offset(($page - 1) * $psize)
            ->limit($psize)
            ->get()->toArray();
        $has_customer_list = method_exists($this, $this->listDataName);
        foreach ($list as $key => $val) {
            if($has_customer_list)
            {
                $val['origin_data'] = $val;//保存原始数据 可以在自定义列表数据中消费
            }
            $this->parseData($val, 'decode', 'list');
            $list[$key] = $val;
        }

        if ($has_customer_list) {
            $method = $this->listDataName;
            $rlist = $this->$method($list);

            if($rlist)
            {
                //增加如果自定义处理了列表数据使用处理过的数据 比如增加了行(可能会碰到合并行的需求)
                $list = $rlist;
            }
            //再次循环一次将 原始数据删除
            $list = collect($list)->map(function($v){
                unset($v['origin_data']);
                return $v;
            });

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

    public function checkUnique($data,$id = 0)
    {
        if(empty($this->uniqueFields))
        {
            return;
        }
        $key = '';
        //$message = '';
        $is_has = false;
        foreach($this->uniqueFields as $field)
        {
            
            $where = [];
            if(is_array($field))
            {
                //增加了提示语 检测格式 如果有 columns和message
                $keys = [];
                foreach($field as $k=>$v)
                {
                    if(is_numeric($k))
                    {
                        $keys[] = $v;
                    }else
                    {
                        if($k == 'columns')
                        {
                            $keys = $v;
                        }
                        if($k == 'message')
                        {
                            $key = $v;
                        }
                    }
                }
                $key = $key?$key:implode('-',$keys).'数据已存在';
                foreach($keys as $f)
                {
                    if(!isset($data[$f]) || !$data[$f])
                    {
                        //未设置该值或无该值时不进行检测
                        continue;
                    }
                    $where[$f] = $data[$f];
                }
            }else
            {
                if(!isset($data[$field]) || !$data[$field])
                {
                    continue;
                }
                $where[$field] = $data[$field];
                $key = $field;
            }
            if(empty($where))
            {
                continue;
            }

            $has = $this->model->where($where);
			if($id)
			{
				$has = $has->where([['id','!=',$id]]);
			}
            if($has->first())
			{
                $is_has = true;
				break; 
			}
        }

        return $is_has?$key:'';
    }

    public function post()
    {
        if (request()->isMethod('post'))
        {
            $this->is_post = true;
        }
        //sleep(10);

        $id = request('id', 0);
        $id = $id ?: request('base.id', 0);
        $m = $this->model;
        if (!empty($this->with_column)) {
            $m = $m->with($this->with_column);
        }
        if(!is_array($id))
        {
            if($this->withTrashed)
            {
                $item = $m->where(['id' => $id])->withTrashed()->first();
            }else
            {
                $item = $m->where(['id' => $id])->first();
            }
            

            if (!empty($item)) {
                $this->action_type = 'edit';
                $item = $item->toArray();
                if (method_exists($this, 'checkPost')) {
                    $ret = $this->checkPost($item, $id); //编辑数据检测
                    if ($ret) {
                        return $ret;
                    }
                }
            } else {
                if (method_exists($this, 'checkPost')) {
                    $ret = $this->checkPost($item, $id); //新增数据检测
                    if ($ret) {
                        return $ret;
                    }
                }
                $this->action_type = 'add';
                $item = $this->default_post; //数据的默认值
                $item['created_at'] = now()->toDateTimeString();
            }
        }else
        {
            $this->action_type = 'edit';
            if (method_exists($this, 'beforeMorePost')) {
                $ret = $this->beforeMorePost($id); //批量操作检测所有数据id值
                if ($ret) {
                    return $ret;
                }
            }
        }
        $type = request('actype');
        $base = request('base');

        if ($this->is_post) {
            //+检测字段的唯一性
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
                    $val = request('state',0);
                    $data = ['state' => $val?1:0];
                    //批量操作
                    //$this->parseData($data, 'encode', 'update');
                    //d($data);
                    if(is_array($id))
                    {
                        if (method_exists($this, 'beforeMultiplePost')) {
                            $ret = $this->beforeMultiplePost($data, $id); //操作前处理数据 如果返回数据表示 数据错误 返回错误信息
                            if ($ret) {
                                return $ret;
                            }
                        }
                        $this->model->whereIn('id',$id)->update($data);
                    }else
                    {
                        if (method_exists($this, 'beforePost')) {
                            $ret = $this->beforePost($data, $id,$item); //操作前处理数据 如果返回数据表示 数据错误 返回错误信息
                            if ($ret) {
                                return $ret;
                            }
                        }
                        $this->model->where('id',$id)->update($data);
                    }
                    return $this->success();
                    break;
                case 'displayorder':
                    $data = ['displayorder' => intval(request('displayorder'))];
                    $this->model->where('id',$id)->update($data);
                    return $this->success();
                    break;
                default:
                    //设置不需要提交字段
                    if (!empty($this->dont_post_columns)) {
                        foreach ($this->dont_post_columns as $c) {
                            if (isset($base[$c])) {
                                unset($base[$c]);
                            }
                        }
                    }
                    $data = filterEmpty($base, $this->can_be_null_columns); //后台传入数据统一使用base数组，懒得每个字段赋值
                    //json数据列
                    if (!empty($this->json_columns)) {
                        foreach ($this->json_columns as $c) {
                            if (isset($data[$c])) {
                                $data[$c] = json_encode($data[$c]);
                            }
                        }
                    }
            }
            if (method_exists($this, 'beforePost')) {
                $ret = $this->beforePost($data, $id,$item); //操作前处理数据 如果返回数据表示 数据错误 返回错误信息
                if ($ret) {
                    return $ret;
                }
            }
            //全局数据提交检测
            $fail = $this->globalPostCheck($data,$item);
            if($fail)
            {
                return $fail;
            }
            $newModel = $this->getModel();
            //d($data);
            if (!empty($id)) {
                $data['originData'] = $item;
                $this->parseData($data, 'encode', 'update');
                $check_uniue_result = $this->checkUnique($data,$id);
                if($check_uniue_result)
                {
                    return $this->fail([1,$check_uniue_result]);
                }
                $newModel->where(['id' => $id])->update($data);
            } else {
                //插入数据
                $data['created_at'] = $data['created_at']??now();
                $this->parseData($data);
                $check_uniue_result = $this->checkUnique($data,$id);
                if($check_uniue_result)
                {
                    return $this->fail([1,$check_uniue_result]);
                }
                if($this->model->with_system_admin_id)
                {
                    $data = $this->model->getSysAdminIdData($data);
                }
                
                $id = $newModel->insertGetId($data);
            }
            $ret = null;

            //返回插入或更新后的数据
            $new_data = $this->model->where(['id' => $id])->with($this->with_column)->first()->toArray();

            //操作完数据后 读取可操作关联模型的数据处理 base为已经处理过不包含设定不传字段的数据
            $base = $this->setBase($base);
            (new Relation($this->getModelClass(),$new_data))->afterPost($base);

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
        //$id_count = count($id);
        $item_count = 0;
        if (!empty($id)) {
            $m = $this->getModel();
            $m = $this->beforeDestroy($m->whereIn('id', $id));

            $items = $m->get();
            $item_count = count($items);
            if(!$item_count)
            {
                return $this->failMsg($this->failMessage('delete_at','删除失败'));
            }
            foreach ($items as $val) {
                //增加删除检测字段类型是图片附件的，删除文件
                $fake_val = [];
                $fail = $this->globalPostCheck($fake_val,$val);
                if($fail)
                {
                    return $fail;
                }
                $val_arr = $val->toArray();
                $pval = [
                    'originData'=>$val_arr,
                ];
                $this->parseData($pval, 'encode','delete');
                $val->delete();
                $this->afterDestroy($val_arr);
            }
            return $this->success(null,[0,'成功删除 '.$item_count.' 条记录']);
        }
        return $this->failMsg('参数错误');
    }

    public function beforeDestroy($m)
    {
        return $m;
    }

    public function afterDestroy($item)
    {
        return;
    }

    public function setBase($base)
    {
        return $base;
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
        $ps = new ParseData($this->getModelClass(),['action_type'=>$this->action_type]);
        $ps->parseWiths($data);
        return;
    }

    public function getParseColumns($parse_columns = [])
    {
        if(!empty($parse_columns))
        {
            return $parse_columns;
        }
        if(method_exists($this->model,'getParseColumns'))
        {
            $parse_columns = $this->model->getParseColumns();
        }else
        {
            $parse_columns = [];
        }
        return !empty($parse_columns)?$parse_columns:$this->parse_columns;
    }

    public function parseData(&$data, $in = 'encode', $from = 'detail',$parse_columns = [],$deep = 1)
    {
        $ps = new ParseData($this->getModelClass(),[
            'action_type'=>$this->action_type,
            'can_be_null_columns'=>$this->can_be_null_columns,
            'category_fields'=>$this->category_fields
        ]);
        $ps->make($data,$in,$from,$deep);
        return;
    }

    public function setThis()
    {
        return [];
    }

    public function export($listData = false)
    {
        $model = HelperService::getDevModel($this->model->model_id);
        if(!$model)
        {
            return $this->fail([1,'model error']);
        }
        $search = [];//search数据将用于导出数据时数据的默认渲染
        $this->parseWiths($search);
        if (method_exists($this, $this->handleSearchName)) 
        {
            //handleSearchName 不为handleSearch时走自己的流程，如果需要调用handleSearch请在handleSearchName方法中调用
            $method = $this->handleSearchName;
            [$m,$search] = $this->$method($search);
        }else
        {
            [$m,$search] = $this->handleSearch($search);
        }

		$ids = request('ids');
		if($ids)
		{
			$m = $m->whereIn('id',$ids);
		}

		$m = $this->defaultSearch($m);

        $index = request('export_index',0);

        $setting = $model['setting']?json_decode($model['setting'],true):[];

        $default_config = ['label'=>'default','value'=>'default'];

        $export_config = Arr::get($setting,'export',[$default_config]);

        if($index)
        {
            $config = collect($export_config)->first(function($item) use($index){
                return $item['value'] == $index;
            });
            if(!$config)
            {
                $config = $default_config;
            }
        }else
        {
            $config = $export_config[0];
        }

        if(!$config)
        {
            return $this->fail([1,'export config error']);
        }

        //$ds->modelColumn2Export($model);
        $config['config']['dev_menu'] = request('dev_menu');//如果未设置表头，直接读取列表的表头进行导出
        
		$es = new ExcelService($config['config'],$search);

        $m = $this->handleSort($m);

        if (!empty($this->with_sum)) {
            foreach($this->with_sum as $with_sum)
            {
                $m = $m->withSum($with_sum[0],$with_sum[1]);
            }
        }

        $data = $m->with($this->with_column)->get()->toArray();

        if($listData && method_exists($this, $listData))
        {
            $data = $this->$listData($data);
            $formatData = false;
        }else
        {
            $formatData = method_exists($this, 'exportFormatData')?function($val){
                return $this->exportFormatData($val);
            }:false;
        }
		$ret = $es->export($data,$formatData);
		return $this->success($ret);
    }
    /**
     * 获取菜单对应的模型对象 防止设置$this->model 对象改变
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        if($this->model_class)
        {
            return new $this->model_class;
        }else
        {
            return $this->model;
        }
    }

    /**
     * 获取模型类 兼容之前未设置model_class的方法
     *
     * @return Model
     */
    public function getModelClass()
    {
        if($this->model_class)
        {
            return $this->model_class;
        }else
        {
            $class = get_class($this->model);
            return $class;
        }
    }
    /**
     * 全局修改数据前置检测
     *
     * @param [type] $data
     * @param [type] $item
     * @return void
     */
    public function globalPostCheck(&$data,$item)
    {
        $origin_model = $this->getModel();
        if(property_exists($origin_model,'admin_post_check'))
        {
            if(!$this->service->postCheck($data,$item,$origin_model))
            {
                return $this->fail([1,$this->failMessage('global_post_check')]);
            }
        }
        return;
    }
    /**
     * 全局筛选数据条件
     *
     * @param [type] $m
     * @param boolean $origin_model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function globalDataSearch($m,$origin_model = false)
    {
        $origin_model = $origin_model?:$this->getModel();
        if(property_exists($origin_model,'admin_data_search'))
        {
            $m = $this->service->dataSearch($m,$origin_model);
        }
        return $m;
    }

    /**
     * 自定义返回错误提示信息
     *
     * @param [type] $type
     * @param string $default
     * @return string
     */
    public function failMessage($type,$default = '')
    {
        $arr = array_merge($this->service->fail_reason,$this->fail_reason_customer);
        return $arr[$type]??$default;
    }
}
