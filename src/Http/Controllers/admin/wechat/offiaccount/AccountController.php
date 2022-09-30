<?php
namespace Echoyl\Sa\Http\Controllers\admin\wechat\offiaccount;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\offiaccount\Account;

class AccountController extends CrudController
{
	//var $with_column = ['category'];

    public function __construct()
	{
		$this->model = new Account();

        $this->parse_columns = [
            ['name' => 'qrcode', 'type' => 'image', 'default' => ''],
            ['name' => 'state', 'type' => 'state', 'default' => 'enable'],
        ];

	}

	public function postData(&$item)
	{

		return;
	}

	/**
	 * 编辑数据时 检测数据合法性
	 *
	 * @param [type] $item
	 * @return void
	 */
	public function checkPost($item)
	{
		if(false)
		{
			return ['code'=>1,'msg'=>'信息错误，请重试'];
		}
        return;
	}

	/**
	 * 提交数据时检测数据合法性
	 *
	 * @param [type] $data
	 * @param [type] $id
	 * @return void
	 */
	public function beforePost($data,$id = 0)
	{
		if(false)
		{
			return ['code'=>1,'msg'=>'数据错误，请重试'];
		}
        return;
	}
}
