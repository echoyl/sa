<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use DateTime;
use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Services\dev\crud\CrudService;
use Echoyl\Sa\Services\export\ExcelService;
use Echoyl\Sa\Services\HelperService;
use Echoyl\Sa\Services\WebMenuService;
use Illuminate\Support\Arr;

/**
 * 后台crud基础类 都走这个
 * @method mixed afterPost($id,$data)  提交完数据后通过返回id再继续操作
 * @method mixed beforePost(&$data,$id = 0,$item = [])  提交数据前的检测数据
 * @method mixed beforeMultiplePost(&$data,$id = []) 批量操作前检测
 * @method mixed handleSearch($search = []) 数据列表中额外的搜索调价等
 * @method mixed postData(&$item) 获取数据时格式化数据
 * @method mixed checkPost($item) 检测是否可以提交数据
 * @method mixed listData(&$list) 列表数据格式化
 * @method mixed setThis() 设置一个值 在select 获取数据的时候可以当做filter条件使用
 * @method mixed exportFormatData($val) 导出数据格式化数据方法
 * @property \App\Services\AdminAppService $service
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

    /**
     * 搜索项配置
     */
    public $search_config = [];

    public $parse_columns = [];
    public $withs = [];

    public function checkCategoryField($name,$default = '')
    {
        $field = collect($this->category_fields)->first(function($q) use($name){
            return $q['field_name'] == $name;
        });
        $orval = request($name);//原始请求值
        $rval = $lval = $array_val = $default;

        if($field)
        {
            $rval = request($field['request_name']);//预设请求值
            if($rval)
            {
                if(is_array($rval))
                {
                    $len = count($rval);
                    $lval = $rval[$len - 1];
                }else
                {
                    $lval = $rval;
                }
                if(!$orval)
                {
                    //未传数据 自动读取映射字段
                    $array_val = is_numeric($rval)?[$rval]:(is_array($rval)?$rval:json_decode($rval,true));
                }
            }
            
        }
        
        
        return [
            'search_val'=>$orval?:$rval,//处理过后搜索值
            'last_val'=>$lval,//分类的id值 数字类型
            'array_val'=>$array_val
        ];
    }

    public function defaultSearch($m)
    {
        //
        $title = request('title', '');
        if ($title) {
            $has_customer_search = collect($this->search_config)->first(function($item){
                return $item['name'] == 'title';
            });
            if(!$has_customer_search)
            {
                $m = $m->where([['title', 'like', '%' . urldecode($title) . '%']]);
            }
        }

        $parse_columns = $this->getParseColumns();

        foreach ($parse_columns as $col) {

            $cs = new CrudService([
                'col'=>$col,
            ]);

            $name = $col['name'];
            $type = $col['type'];
            $search_val = request($name, '');

            $check_search_val = $this->checkCategoryField($name);
            $search_val = $check_search_val['search_val'];

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
                        
                        if($search_val)
                        {
                            if(is_numeric($search_val))
                            {
                                $search_val = [$search_val];
                            }else
                            {
                                $search_val = is_string($search_val) ? json_decode($search_val,true):$search_val;
                                if($where_type == 'whereBetween' && is_array($search_val) && isset($search_val[1]))
                                {
                                    //检测是否是日期
                                    $d = DateTime::createFromFormat("Y-m-d",$search_val[1]);
                                    if($d && $d->format('Y-m-d') === $search_val[1])
                                    {
                                        //是日期 自动追加至当天最后一秒
                                        $search_val[1] .= ' 23:59:59';
                                    }
                                }
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

        [$m, $csearch] = $this->handleSearch($search);

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
                    if(!isset($data[$f]))
                    {
                        continue;
                    }
                    $where[$f] = $data[$f];
                }
            }else
            {
                if(!isset($data[$field]))
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
                $data['originData'] = $item;
                $this->parseData($data, 'encode', 'update');
                $check_uniue_result = $this->checkUnique($data,$id);
                if($check_uniue_result)
                {
                    return $this->fail([1,$check_uniue_result]);
                }
                $this->model->where(['id' => $id])->update($data);
            } else {
                $data['created_at'] = $data['created_at']??now();
                $this->parseData($data);
                $check_uniue_result = $this->checkUnique($data,$id);
                if($check_uniue_result)
                {
                    return $this->fail([1,$check_uniue_result]);
                }
                $id = $this->model->insertGetId($data);
            }
            $ret = null;
            
            //操作完数据后 读取可操作关联模型的数据处理
            $this->afterPostParseData($id,request('base'));

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

    public function parseWiths(&$data,$parse_columns = [])
    {
        $ret = [];
        foreach ($this->withs as $with) {
            $name = $with['name'] . 's';
            if(isset($with['class']))
            {
                $check_category_field = $this->checkCategoryField($with['name'],$with['cid']??0);
                $data[$name] = (new $with['class'])->formatHasTop($check_category_field['last_val']);
            }elseif(isset($with['data']))
            {
                $data[$name] = $with['data'];
            }
            $ret[] = $with['name'];
        }

        $parse_columns = $this->getParseColumns($parse_columns);

        $table_menu = [];

        foreach ($parse_columns as $with) {
            if(isset($with['with']))
            {
                $name = $with['name'] . 's';
                if(isset($with['class']))
                {
                    $_m = new $with['class'];
                    $no_category = Arr::get($with,'no_category',false);//不是分类模型
                    if($with['type'] == 'select_columns')
                    {
                        //这里只获取一层数据因为一般的模型都没有继承category模型 没有format方法
                        if($with['columns'])
                        {
                            $_m = $_m->select($with['columns']);
                        }
                        $data[$name] = $_m->orderBy('displayorder','desc')->get()->toArray();
                    }else
                    {
                        if($no_category)
                        {
                            if(isset($with['columns']))
                            {
                                $_m = $_m->select($with['columns']);
                            }
                            if(isset($with['where']))
                            {
                                $this_data = $this->setThis();
                                $with_where = [];
                                foreach($with['where'] as $ww)
                                {
                                    if(strpos($ww[2],'this.') !== false)
                                    {
                                        $data_key = str_replace('this.','',$ww[2]);
                                        if(isset($this_data[$data_key]))
                                        {
                                            $ww[2] = $this_data[$data_key];
                                        }
                                    }
                                    $with_where[] = $ww;
                                }
                                $_m = $_m->where($with_where);
                            }
                            
                            $data[$name] = $_m->orderBy('displayorder','desc')->get()->toArray();
                        }else
                        {
                            if(isset($with['post_all']) && $with['post_all'] && in_array($this->action_type,['edit','add']))
                            {
                                //设置post_all 时 不再读取cid筛选数据
                                $cid = 0;
                            }else
                            {
                                //检测是否有cid字段的参数传入
                                $check_category_field = $this->checkCategoryField($with['name'],$with['cid']??0);
                                $cid = $check_category_field['last_val'];
                            }
                            
                            if(isset($with['fields']))
                            {
                                $data[$name] = $_m->formatHasTop($cid,$with['fields']);
                            }else
                            {
                                $data[$name] = $_m->formatHasTop($cid);
                            }
                        }
                        
                        
                    }
                }elseif(isset($with['data']))
                {
                    $data[$name] = $with['data'];
                }
            }
            
            $ret[] = $with['name'];
            //检测是否有table_menu设置
            if(isset($with['table_menu']) && !isset($data['table_menu']))
            {
                $table_menu[$with['name']] = $with['data']??$data[$name];
            }
        }

        if(!empty($table_menu))
        {
            $data['table_menu'] = $table_menu;
        }

        return $ret;
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
        $unsetNames = [];

        $parse_columns = $this->getParseColumns($parse_columns);

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
            if(!$isset)
            {
                $check_category_field = $this->checkCategoryField($name,$col['default']);
                $val = $check_category_field['array_val'];
            }

            $config = [
                'data'=>$data,'col'=>$col,
            ];
            $cs = new CrudService($config);
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
                        if(!empty($cls_p_c) && $deep <= 3 && $isset)
                        {
                            //model类型只支持1级 多级的话 需要更深层次的with 这里暂时不实现了
                            //思路 需要在生成controller文件的 with配置中 继续读取关联模型的关联
                            $this->parseWiths($val,$cls_p_c);
                            $this->parseData($val,$in,$from,$cls_p_c,$deep+1);
                        }
                    }
                    
                    break;
                case 'aliyunVideo':
                    if(!$val && !$encode)
                    {
                        $val = '__unset';
                    }else
                    {
                        $val = HelperService::aliyunVideoParse($val ?? '', $encode ? true : false,);
                    }
                    break;
                case 'cascader':
                case 'cascaders':
                    $_name = '_' . $name;
                    if ($encode) {
                        if (!empty($val)) {
                            if(is_numeric($val))
                            {
                                //检测数据类型
                                $val = [$val];
                            }
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
                            $data[$_name] = '';
                        }
                    } else {
                        $val = isset($data[$_name]) && $data[$_name] ? json_decode($data[$_name], true) : '';
                    }
                    break;
                case 'select':
                case 'radioButton':
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
                        $val = is_array($val)?implode(',',$val):$val;
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
                case 'pca'://所有独立的类型 都在此集合
                case 'json':
                case 'image':
                case 'file':
                case 'tinyEditor':
                case 'price':
                case 'mprice':
                case 'mmprice':
                    $data = $cs->make($type,[
                        'encode'=>$encode,
                        'isset'=>$isset,
                    ]);
                    //之后 字段类型都 抽离后 下面两行可以删除
                    $val = '';
                    $need_set = false;
                    break;
                case 'latlng':
                case 'tmapInput':
                    if ($encode) {
                        if($isset)
                        {
                            $lat = Arr::get($val,0,'');
                            $lng = Arr::get($val,1,'');
                            $data['lat'] = is_null($lat)?'':$lat;
                            $data['lng'] = is_null($lng)?'':$lng;
                            
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
                    //20240323 前端组件改成了select labelinvalue设置后已不会再将整个数据放入到value中 所以这里要再检测下value字段
                    $id_name = $col['value']??'id';
                    if($encode)
                    {
                        if($isset && $val)
                        {
                            if(isset($val[$id_name]))
                            {
                                $val = $val[$id_name];
                            }elseif(isset($val['value']))
                            {
                                $val = $val['value'];
                            }
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
                            $val = HelperService::pwd($val);
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
                case 'date':
                case 'datetime':
                    if(!$val)
                    {
                        $val = '__unset';
                    }
                    if($encode)
                    {

                    }else
                    {
                        
                    }
                    break;
                case 'select_columns':
                    if($encode)
                    {
                        $val = '__unset';
                    }
                    break;
                case 'config':
                    if($encode)
                    {

                    }else
                    {
                        if($val)
                        {
                            $wms = new WebMenuService;
                            $val = $wms->getSpecs($val,true);
                        }
                    }
                    break;
                case 'modalSelect':
                    //表单类型为 搜索select时
                    $id_name = $col['value']??'id';
                    if($encode)
                    {
                        if($isset && $val)
                        {
                            if(isset($val[$id_name]))
                            {
                                $val = $val[$id_name];
                            }elseif(is_array($val))
                            {
                                //如果传输的数据是数组 这里暂时数据库中只存储逗号拼接的id值  如果之后需要关联模型处理再说
                                $_v = [];
                                foreach($val as $v)
                                {
                                    if(isset($v['data']) && isset($v['data'][$id_name]))
                                    {
                                        $_v[] = $v['data'][$id_name];
                                    }
                                }
                                if(!empty($_v))
                                {
                                    $val = implode(',',$_v);
                                }else
                                {
                                    $val = '';
                                }
                            }
                            
                        }
                    }else
                    {
                        //特定字段名称 label value
                        //前端数据暂时不用
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
        if(isset($data['originData']))
        {
            unset($data['originData']);
        }

        return $unsetNames;
    }

    /**
     * 数据更新或插入后的操作
     *
     * @param [type] $id
     * @param [type] $data
     * @return void
     */
    public function afterPostParseData($id,$data)
    {
        $parse_columns = $this->getParseColumns();
        foreach($parse_columns as $column)
        {
            $type = $column['type'];
            $name = $column['name'];
            
            switch ($type) {
                case 'model':
                    if(isset($data[$name]))
                    {
                        $idata = filterEmpty($data[$name]);
                        $foreign_key = $column['foreign_key'];//外键名称
                        if($foreign_key != 'id')
                        {
                            //只有外键名称不是id的时候才去更新 关联数据信息
                            $this->modelData($idata,$column['class'],[$foreign_key=>$id]);
                        }
                    }
                break;
            }
        }
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
    public function modelData($data,$class,$where)
    {
        $model = new $class;
        $item = $model->where($where)->first();

        $cls_p_c = $model->getParseColumns();
        if($item)
        {
            //更新
            $from = 'update';
        }else
        {
            //新增
            $from = 'insert';
        }
        if(!empty($cls_p_c))
        {
            $this->parseData($data,true,$from,$cls_p_c);
        }

        $data = array_merge($data,$where);

        if($from == 'update')
        {
            $model->where($where)->update($data);
        }else
        {
            $model->where($where)->insert($data);
        }
        return;
    }

    public function setThis()
    {
        return [];
    }

    public function export()
    {
        $model = HelperService::getDevModel($this->model->model_id);
        if(!$model)
        {
            return $this->fail([1,'model error']);
        }

        [$m,] = $this->handleSearch();

		$ids = request('ids');
		if($ids)
		{
			$m = $m->whereIn('id',$ids);
		}

		$m = $this->defaultSearch($m);

        $index = request('export_index',0);

        if(!$model['setting'])
        {
            return $this->fail([1,'setting error']);
        }

        $setting = json_decode($model['setting'],true);

        $export_config = Arr::get($setting,'export');

        if(!$export_config)
        {
            return $this->fail([1,'export setting error']);
        }

        if($index)
        {
            $config = collect($export_config)->first(function($item) use($index){
                return $item['value'] == $index;
            });
        }else
        {
            $config = $export_config[0];
        }
        //$ds->modelColumn2Export($model);
        //d(1);
        
		$es = new ExcelService($config['config']);

        $m = $this->handleSort($m);

        $data = $m->with($this->with_column)->get()->toArray();
		$ret = $es->export($data,method_exists($this, 'exportFormatData')?function($val){
            return $this->exportFormatData($val);
        }:false);
		return $this->success($ret);
    }
}
