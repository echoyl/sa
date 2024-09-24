<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Services\HelperService;
use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\NoticeService;

/**
 * @property \Echoyl\Sa\Services\AdminAppService $service
 */
class IndexController extends ApiBaseController
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
                return $this->fail([1,'体验账号暂时不支持修改密码']);
            }

            $base = request('base');

            $old_password = trim($base['old_password']??'');
            $new_password = trim($base['new_password']??'');

            if ($new_password && strlen($new_password) < 6) {
                return $this->fail([1,'密码长度至少为6位']);
            }

            $update = [];
            $msg = '';
            $pwd = false;

            $base['originData'] = $uinfo;

            $avatar_data = HelperService::enImages($base,['avatar']);

            $update = [
                'realname' => $base['realname']??'',
                'desc' => $base['desc']??'',
                'mobile' => $base['mobile']??'',
                'avatar' => $avatar_data['avatar'],
            ];

            if ($new_password && $old_password) {
                if (AdminService::pwd($old_password) == $uinfo['password']) {
                    $update['password'] = AdminService::pwd($new_password);
                    $pwd = true;
                } else {
                    $msg = '，密码未修改成功';
                }

            }
            AdminService::updateUserInfo($uinfo['id'],$update);
            return $this->success(['pwd' => $pwd,'logout'=>$pwd],[0,'修改成功' . $msg]);

        }
        $uinfo = AdminService::user();
        $item['username'] = $uinfo['username'];
        $ret = [
            'username' => $uinfo['username'],
            'mobile' => $uinfo['mobile'],
            'desc' => $uinfo['desc'],
            'realname' => $uinfo['realname'],
            'avatar' => HelperService::uploadParse($uinfo['avatar'], false),
        ];

        return $this->success($ret);
    }

    public function logout()
    {
        AdminService::log('退出登录');
        AdminService::logout();
        return $this->success('退出成功');
        //return ['code' => 0, 'msg' => '退出成功'];
    }

    public function currentUser()
    {
        $user = AdminService::user();

        $userinfo = AdminService::parseUser($user);
        
        $userinfo = $this->service->parseUserInfo($userinfo,$user);

        return $this->success($userinfo);
    }

    public function setting()
    {
        return $this->success(AdminService::setting());
    }

    public function notice()
    {
        $data = NoticeService::get();
        return $this->success($data);
    }

    public function clearNotice()
    {
        $id = request('id');
        $type = request('type');
        NoticeService::clear($id,$type);
        return $this->success('操作成功');
    }

    public function workplace()
    {
        return $this->success($this->service->panel2());
    }

    public function lockscreen()
    {
        $password = request('base.password');
        $admin = AdminService::user();

        if(!$password || AdminService::pwd($password) != $admin['password'])
        {
            return $this->fail([1,'密码输入错误']);
        }
        return $this->success(null,[0,'解锁成功']);
    }
}
