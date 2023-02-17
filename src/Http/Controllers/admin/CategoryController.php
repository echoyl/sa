<?php
/*
 * @Author: echoyl yliang_1987@126.com
 * @Date: 2023-02-03 09:55:16
 * @LastEditors: echoyl yliang_1987@126.com
 * @LastEditTime: 2023-02-14 14:11:35
 * @FilePath: \zhihuanpingtai\vendor\echoyl\sa\src\Http\Controllers\admin\CategoryController.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Category;

class CategoryController extends CrudController
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

		return ['code'=>0,'msg'=>'','data'=>$this->model->getChild($this->cid)];	

	}

	

}
