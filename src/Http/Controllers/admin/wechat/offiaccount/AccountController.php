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
		$this->model = new Account();

		$this->parse_columns = [];

	}
	//customer code start
	
	//customer code end
}
