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
		$this->default_post = [
			'parent_id'=>$post_parent_id?:$this->cid
		];

	}

    public function index()
    {
		//修改获取分类模式 直接递归 查询数据库获取数据

		//return ['code'=>0,'msg'=>'','data'=>$this->model->getChild($this->cid)];	
		$search = [];
        $this->parseWiths($search);
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

		$data = HelperService::getChildFromData($this->model->get()->toArray(),function($item){
            $this->parseData($item,'decode','list');
            return $item;
        },$displayorder);

		return $this->list($data,count($data),$search);
	}

	

}
