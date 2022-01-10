<?php
namespace App\Http\Controllers\admin$namespace$;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use App\Models$modelnamespace$\$modelname$;

class $controller_name$ extends CrudController
{
	var $with_column = $with_column$;

    public function __construct()
	{
		$this->model = new $modelname$();
		$post_parent_id = request('parent_id',0);
		$this->default_post = [
			'parent_id'=>$post_parent_id?:$this->cid,
			'displayorder'=>0
		];
	}
	
	public function index()
    {
		$search['status'] = [
			['title'=>'启用','id'=>1],['title'=>'禁用','id'=>0],
		];
		return ['code'=>0,'msg'=>'','data'=>$this->model->getChild($this->cid),'search'=>$search];	

	}

}
