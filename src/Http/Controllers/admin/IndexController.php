<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Echoyl\Sa\Models\perm\PermUser;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\PermService;

class IndexController extends Controller
{
	//
	var $menus = [];

    public function index()
    {
		
        return ['code'=>0];
    }

	public function user(Request $request)
	{
		if ($request->isMethod('post')) {
			$uinfo = AdminService::user();
			if($uinfo['username'] == 'test')
			{
				return ['code'=>1,'msg'=>'体验账号暂时不支持修改密码'];
			}
			$p1 = trim($request->input('password',''));
			$p2 = trim($request->input('password2',''));
			if(strlen($p2) > 5 && AdminService::pwd($p1) == $uinfo['password']) 
			{
				$permUser = new PermUser();
				$data = ['password'=>AdminService::pwd($p2)];
				$permUser->where('id','=',$uinfo['id'])->update($data);
			}else
			{
				return ['code'=>1,'msg'=>'密码长度至少为6位或旧密码错误'];
			}
			return ['code'=>0,'msg'=>'success'];
			
		}
		$uinfo = AdminService::user();
		$item['username'] = $uinfo['username'];
		return ['code'=>0,'msg'=>'','data'=>$item];
	}

	public function logout(Request $request)
    {
		AdminService::log($request,'退出登录');
        AdminService::logout();
        return['code'=>0,'msg'=>'退出成功'];
    }

    public function getMenus()
	{
		//根据权限获取相应的 界面目录 之前写在 menu.js中 现在放到后端判断返回显示
		$menus = $this->menus?:[
			[
				"name" => "setting"
				, "title" => "设置"
				, "icon" => "icow icow-homeL"
				, "list" => [
					// [
					// 	"name" => "banner"
					// 	, "title" => "轮播图"
					// ],
					[
						"name" => "wxconfig"
						, "title" => "公众号设置"
						, "single" => 1
					],
					[
						"name" => "wxappconfig"
						, "title" => "小程序设置"
						, "single" => 1
					],
					[
						"name" => "base"
						, "title" => "基础设置"
						, "single" => 1
					]
				]
			]
			,[
				"name" => "account"
				, "title" => "用户"
				, "icon" => "icow icow-yongxinyonghu"
				, "list" => [
					[
						"name" => "user"
						, "title" => "用户管理"
					],
					[
						"name" => "platform"
						, "title" => "平台用户"
					]
				]
			]
			, [
				"name" => "news"
				, "title" => "内容"
				, "icon" => "icow icow-page"
				, "list" => [
					// [
					// 	"name" => "sets"
					// 	, "title" => "设置"
					// 	, "single" => 1
					// ],
					[
						"name" => "category"
						, "title" => "分类"
					], [
						"name" => "news"
						, "title" => "列表"
					]
					, [
						"name" => "comment"
						, "title" => "评论"
					]
				]
			]
	 
			, [
				"name" => "perm"
				, "title" => "权限"
				, "icon" => "icow icow-quanxian1"
				, "list" => [
						[
							"name" => "user"
							, "title" => "用户"
						], 
						[
							"name" => "role"
							, "title" => "角色"
						]
					]
			]
		];
		
		$uinfo = AdminService::user(1);
		if($uinfo['id'] != 1)
		{
			//非超级管理员需要检测有哪些权限
			$_menus = [];
			$user_perms = explode(',',$uinfo['perms2']);
			//echo '<pre>';
			//var_dump($user_perms);exit;
			$perm_obj = new PermService($uinfo['perms2']);
			$all_perms = $perm_obj->allPerms();
			$ups = [];
			foreach($user_perms as $val)
			{
				$up = explode(".",$val);
				$ups[$up[0]][] = $up;
			}
			//var_dump($ups);exit;
			foreach($menus as $menu)
			{
				if(!isset($all_perms[$menu['name']]))
				{
					//权限中没有此类表示不需要权限 直接放出
					$_menus[] = $menu;
				}else
				{
					if(isset($ups[$menu['name']]))
					{
						//存在此类 则放出来
						$_menus[] = $menu;
					}
				}
				
			}
			$menus = $_menus;
		}
		return ['code'=>0,'msg'=>'','data'=>$menus];	
	}

}
