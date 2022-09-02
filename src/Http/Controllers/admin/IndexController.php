<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Menu;
use App\Services\AdminMenuService;
use App\Services\HelperService;
use Echoyl\Sa\Models\perm\PermUser;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\PermService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    //
    public $menus = [];

    public function index()
    {

        return ['code' => 0];
    }

    public function user()
    {
        if (request()->isMethod('post')) {
            $uinfo = AdminService::user();
            if ($uinfo['username'] == 'test') {
                return ['code' => 1, 'msg' => '体验账号暂时不支持修改密码'];
            }

            $base = request('base');

            $p1 = trim($base['password']??'');
            $p2 = trim($base['password2']??'');

            if ($p2 && strlen($p2) < 6) {
                return ['code' => 1, 'msg' => '密码长度至少为6位'];
            }

            $update = [];
            $msg = '';
            $pwd = false;

            $update = [
                'realname' => $base['realname']??'',
                'desc' => $base['desc']??'',
                'mobile' => $base['mobile']??'',
                'avatar' => HelperService::uploadParse($base['avatar']??''),
            ];

            if ($p2) {
                if (AdminService::pwd($p1) == $uinfo['password']) {
                    $update['password'] = AdminService::pwd($p2);
                    $pwd = true;
                } else {
                    $msg = '，密码未修改成功';
                }

            }

            $permUser = new PermUser();
            $permUser->where('id', '=', $uinfo['id'])->update($update);
            return ['code' => 0, 'msg' => '修改成功' . $msg,'data'=>['pwd' => $pwd]];

        }
        $uinfo = AdminService::user();
        $item['username'] = $uinfo['username'];
        return ['code' => 0, 'msg' => '', 'data' => [
            'username' => $uinfo['username'],
            'mobile' => $uinfo['mobile'],
            'desc' => $uinfo['desc'],
            'realname' => $uinfo['realname'],
            'avatar' => HelperService::uploadParse($uinfo['avatar'], false),
        ]];
    }

    public function logout(Request $request)
    {
        AdminService::log($request, '退出登录');
        AdminService::logout();
        return ['code' => 0, 'msg' => '退出成功'];
    }

    public function getMenus()
    {
        //根据权限获取相应的 界面目录 之前写在 menu.js中 现在放到后端判断返回显示
        $menus = $this->menus;

        $uinfo = AdminService::user(1);
        if ($uinfo['id'] != 1) {
            //非超级管理员需要检测有哪些权限
            $_menus = [];
            $user_perms = explode(',', $uinfo['perms2']);
            //echo '<pre>';
            //var_dump($user_perms);exit;
            $perm_obj = new PermService($uinfo['perms2']);
            $all_perms = $perm_obj->allPerms();
            $ups = [];
            foreach ($user_perms as $val) {
                $up = explode(".", $val);
                $ups[$up[0]][] = $up;
            }
            //var_dump($ups);exit;
            foreach ($menus as $menu) {
                if (!isset($all_perms[$menu['name']])) {
                    //权限中没有此类表示不需要权限 直接放出
                    $_menus[] = $menu;
                } else {
                    if (isset($ups[$menu['name']])) {
                        //存在此类 则放出来
                        $_menus[] = $menu;
                    }
                }

            }
            $menus = $_menus;
        }
        return ['code' => 0, 'msg' => '', 'data' => $menus];
    }

    public function currentUser()
    {
        $user = AdminService::user();
        $avatar = HelperService::uploadParse($user['avatar'], false);
        $avatar = !empty($avatar) ? tomedia($avatar[0]['url']) : '/antadmin/logo.png';
		$as = new AdminMenuService;
        $info = [
            'id' => $user['id'],
            'username' => $user['username'],
            'roleid' => $user['roleid'],
            'name' => $user['username'],
            'avatar' => $avatar,
            'permission' => $user['perms2'],
			'menuData'=>$as->get(),
        ];

        return ['code' => 0, 'msg' => '', 'data' => $info];

    }
    public function notice()
    {
        return ['code' => 0, 'success' => true, 'msg' => '', 'data' => []];
    }

}
