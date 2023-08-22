<?php
namespace Echoyl\Sa\Http\Controllers\admin\wechat\offiaccount;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\offiaccount\Account;

//customer namespace start

//customer namespace end

class AccountController extends CrudController
{
    public function __construct()
	{
		parent::__construct();
		$this->with_column = [
		    'menus',
		];
		$this->model = new Account();
		//customer construct start
		
		//customer construct end
	}
	//customer code start
	
	//customer code end
}
