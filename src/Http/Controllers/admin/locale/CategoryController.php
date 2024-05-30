<?php
namespace Echoyl\Sa\Http\Controllers\admin\locale;
use Echoyl\Sa\Http\Controllers\admin\CategoryBaseController;
use Echoyl\Sa\Models\locale\Category;


//customer namespace start

//customer namespace end

class CategoryController extends CategoryBaseController
{
	public $cid = 0;
	//customer property start
	
	//customer property end
    public function __construct()
	{
		
		$this->model = new Category();
		$post_parent_id = request('parent_id', 0);
        $this->default_post = [
            'parent_id' => $post_parent_id ?: $this->cid,
            'displayorder' => 0,
        ];
	}

	//customer code start
	
	//customer code end
	
}
