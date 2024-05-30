<?php
namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Services\HelperService;

class CategoryBaseController extends CrudController
{
    //
	var $model;
	var $cid = 0;
    public function __construct(Category $model)
	{
		$this->model = $model;
		$post_parent_id = request('parent_id',0);
		$cid = request('cid',0);
		$this->cid = $cid?:$this->cid;
		$this->default_post = [
			'parent_id'=>$post_parent_id?:$this->cid
		];

	}

	public function beforePost(&$data, $id = 0, $item = [])
	{
		$cid = request('cid',$this->cid);

		if(!isset($data['parent_id']) || !$data['parent_id'])
		{
			//未设置或者无父级id
			if($cid)
			{
				//菜单设置了父级id 使用菜单的父级id
				$data['parent_id'] = $cid;
			}
		}
	}

    public function index()
    {
		//修改获取分类模式 直接递归 查询数据库获取数据

		//return ['code'=>0,'msg'=>'','data'=>$this->model->getChild($this->cid)];	
		$search = [];
        $this->parseWiths($search);
		//由于先执行构造函数再执行中间件检测权限导致 无法在构造函数中获取中间件中设置的参数信息，导致这里要手动设置
		$cid = request('cid',0);
		$this->cid = $cid?:$this->cid;
		$displayorder = [];
		$sort_type = ['descend' => 'desc', 'ascend' => 'asc'];
        if (request('sort')) {
            //添加排序检测
            $sort = json_decode(request('sort'), true);
            if (!empty($sort)) {
                foreach ($sort as $skey => $sval) {
					$displayorder[] = [$skey,$sort_type[$sval] ?? 'desc'];
                }
            }
        }
		if(!empty($this->select_columns))
		{
			$this->model = $this->model->select($this->select_columns);
		}

		$m = $this->defaultSearch($this->model);

		$data = HelperService::getChildFromData($m->get()->toArray(),function($item){
            $this->parseData($item,'decode','list');
            return $item;
        },$displayorder,$this->cid);

		return $this->list($data,count($data),$search);
	}

	

}
