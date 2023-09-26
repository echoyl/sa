<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\perm\Log;
use Echoyl\Sa\Models\perm\User;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\MenuService;
use Echoyl\Sa\Services\dev\utils\Utils;
use Illuminate\Support\Arr;

class AdminService
{
    public static function checkToken()
    {
        $user = self::user();
        if ($user) {
            //读取到用户 检测用户权限
            return true;
        } else {
            return false;
        }
    }

    public static function isSuper($user = false)
    {
        $user = $user?:self::user();
        return $user && $user['id'] == 1;
    }

    public static function user()
    {
        $user = request()->user();
        if ($user && $user->currentAccessToken()->name == 'admin') {
            return $user;
        } else {
            return false;
        }

    }

    public static function pwd($str)
    {
        return md5($str);
    }

    public static function login($user)
    {
        //session(['user'=>$user]);
        $token = $user->createToken('admin');

        return $token->plainTextToken;
    }

    /**
     * 获取后台操作用户模型， 可以设置自定义用户模型，即 用户模型继承现有的模型
     *
     * @return Echoyl\Sa\Models\perm\User
     */
    public static function getUserModel()
    {
        $model = config('sa.userModel');
        if($model)
        {
            return new $model;
        }else
        {
            return new User;
        }
    }

    public static function doLogin($user)
    {
        $model = self::getUserModel();
        $token = self::login($user);
        $info = [
            'userinfo'=>self::parseUser($user),
            'access_token'=>$token,
            'setting'=>self::setting(AdminService::isSuper($user)),
        ];
        self::log('登录',['id'=>$user['id']]);
        //更新最后登录时间
        $model->where(['id'=>$user['id']])->update(['latest_login_at'=>now()]);
        return $info;
    }

    public static function doLoginByMobile($mobile)
    {
        $model = self::getUserModel();
        $user = $model->where(['mobile'=>$mobile])->first();
        if($user)
        {
            return self::doLogin($user);
        }
        return false;
    }

    public static function doLoginByUsername($username,$pwd)
    {
        $model = self::getUserModel();
        $user = $model->where(['username'=>$username])->first();
        if($user && $user['password'] == self::pwd($pwd))
        {
            return self::doLogin($user);
        }
        return false;
    }

    public static function setting($is_super = false)
    {
        $ss = new SetsService();
        $setting = $ss->getSet('setting');

        if(isset($setting['theme']))
        {
            $theme = $setting['theme'];
            unset($setting['theme']);
            $setting = array_merge($theme,$setting);
        }

        HelperService::deImagesOne($setting,['logo','favicons','loginBgImgage']);
        $setting['title'] = Arr::get($setting,'title','Deadmin');
        $setting['tech'] = Arr::get($setting,'tech','Deadmin 技术支持');
        $setting['subtitle'] = Arr::get($setting,'subtitle','后台管理系统');
        $setting['baseurl'] = Arr::get($setting,'baseurl','/antadmin/');
        $setting['logo'] = $setting['logo']['url']?:false;
        $setting['loginBgImgage'] = $setting['loginBgImgage']['url']?:false;
        $setting['loginTypeDefault'] = Arr::get($setting,'loginTypeDefault','password');
        $login_type = Arr::get($setting,'loginType',[]);

        if(in_array($setting['loginTypeDefault'],$login_type))
        {
            foreach($login_type as $key=>$val)
            {
                if($val == $setting['loginTypeDefault'])
                {
                    unset($login_type[$key]);
                }
            }
        }
        array_unshift($login_type,$setting['loginTypeDefault']);

        $setting['loginType'] = $login_type;

        $setting['favicons'] = [$setting['favicons']['url']];
        $dev = Arr::get($setting,'dev',true);
        $is_super = $is_super?:AdminService::isSuper();
        $setting['dev'] = $dev && $is_super;
        $setting['lang'] = Arr::get($setting,'lang',true);
        $setting['splitMenus'] = Arr::get($setting,'splitMenus',false);
        return $setting;
    }

    public static function parseUser($user)
    {
        $avatar = HelperService::uploadParse($user['avatar'], false);
        $avatar = !empty($avatar) ? tomedia($avatar[0]['url']) : '';
		$as = new MenuService;
        $rolename = $user['role']?$user['role']['title']:'超级管理员';
        $is_super = self::isSuper($user);
        $auth_ids = [];
        if(!$is_super)
        {
            $perms2 = explode(',',$user['perms2']);
            foreach($perms2 as $perm)
            {
                [$id] = explode('.',$perm);
                $parent_ids = $as->getParentId($id);
                if($parent_ids)
                {
                    $auth_ids = array_merge($parent_ids,$auth_ids);
                }
                
            }
            $auth_ids = array_unique($auth_ids);
        }
        $info = [
            'id' => $user['id'],
            'username' => $user['username'],
            'realname'=>$user['realname'],
            'roleid' => $user['roleid'],
            'rolename'=>$rolename,
            'name' => $user['username'],
            'avatar' => $avatar,
            'permission' => $user['perms2'],
			'menuData'=>$as->get(0,$is_super?false:$auth_ids),
        ];
        return $info;
    }

    public static function logout()
    {
        request()->user()->currentAccessToken()->delete();
        return;
    }

    public static function updateUserInfo($id,$info)
    {
        $model = self::getUserModel();
        $model->where(['id'=>$id])->update($info);
        return;
    }

    public static function checkAuth()
    {
        $user = self::user();

        $now_router = self::getNowRouter();

        //$config_namespace = config('sa.namespace', []);
        if (!self::isSuper()) {
            //id为1是超级管理员
            //读取用户权限信息 实时读取
            $permUser = self::getUserModel();
            $perms = $permUser->where(['id' => $user['id']])->select(['perms2', 'roleid'])->with(['role' => function ($q) {
                $q->select(['id', 'perms2']);
            }])->first()->toArray();
            $as = new MenuService;
            //d($now_router,$perms['perms2'],$perms['role']['perms2']);
            $has_perm = $as->checkPerm($now_router,$perms['perms2'],$perms['role']['perms2']);
            if (!$has_perm) {
                return false;
            }
        }

        //检测菜单是否有默认请求参数
        $ms = new MenuService;
        [$name,$menu] = $ms->getMenuByRouter($now_router);
        //d($menu,$now_router);
        if($menu && $menu['id'] && $menu['other_config'])
        {
            $other_config = json_decode($menu['other_config'],true);
            if($other_config && isset($other_config['defaultData']))
            {
                //d($other_config);
                foreach($other_config['defaultData'] as $key=>$val)
                {
                    $has_request_val = request($key);
                    //d($has_request_val,333);
                    if($has_request_val)
                    {
                        if(is_array($val))
                        {
                            if(is_array($has_request_val))
                            {
                                $val = array_intersect($val,$has_request_val);
                            }else
                            {
                                if(in_array($has_request_val,$val))
                                {
                                    $val = [$has_request_val];
                                }
                            }
                        }
                        //d($val);
                    }
                    request()->offsetSet($key,$val);
                }
            }
        }

        return true;
    }

    public static function getNowRouter()
    {
        $action = request()->route()->action;
        $uri = request()->route()->uri();
        $prefix = env('APP_PREFIX', '') . env('APP_ADMIN_PREFIX','');
        $now_router = trim(str_replace($prefix,'',$uri),'/');
        $now_routers = explode('/',$now_router);
        $old_a = array_pop($now_routers);

        if(isset($action['as']))
        {
            [$c,$a] = explode('.',$action['as']);
            if(strpos($old_a,'{') === false)
            {
                $now_routers[] = $old_a;
            }
        }else
        {
            $a = $old_a;
        }
        $id = request('id', request('base.id',0));
        if ($a == 'store') {
            //检测是 编辑还是 新增
            if ($id) {
                $a = 'edit';
            } else {
                $a = 'add';
            }
        }elseif($a == 'show') {
            //检测是 编辑还是 新增
            if (!$id) {
                $a = 'add';
            }
        }
        $now_routers[] = $a;

        $now_router = implode('/', $now_routers);

        return $now_router;
    }

    public static function getUsers()
    {
        $user = self::user();
        $permUser = self::getUserModel();
        if ($user['id'] == 1) {
            return $permUser->select('id', 'username')->where('id', '!=', 1)->get();
        } else {
            return $permUser->select('id', 'username')->where('id', '=', $user['id'])->get();
        }
    }

    public static function getRouterName()
    {
        $now_router = self::getNowRouter();
        $ms = new MenuService();
        [$name,$menu] = $ms->getMenuByRouter($now_router);
        if(!$menu['id'])
        {
            return '';
        }
        $ds = new DevService;
        $path = array_reverse(Utils::getPath($menu,$ds->allMenu(),'title'));
        
        $name = $ms->basePerms[$name]??'';
        $path[] = $name;

        return implode(' - ',$path);

    }

    public static function log($force_type = false, $data = [])
    {
        if (!empty($data)) {
            $admin = $data;
        } else {
            $admin = self::user();
        }
        if (!$admin) {
            return;
        }

        $method = request()->method();
        if (in_array($method,['POST','DELETE']) || $force_type) {
            //只记录post的日志
            //屏蔽敏感数据
            $rq = request()->all();
            if (isset($rq['post'])) {
                unset($rq['post']);
            }
            $log_data = self::logParse($rq);

            if($force_type)
            {
                $type = $force_type;
            }else
            {
                $type = self::getRouterName();
            }

            $data = [
                'user_id' => $admin['id'],
                'url' => implode(':',[$method,request()->fullUrl()]),
                'request' => json_encode($log_data),
                'ip' => request()->ip(),
                'created_at' => now(),
                'type' => $type ?: '',
            ];
            Log::insert($data);
        }
        return;
    }

    public static function logParse($data)
    {
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $data[$key] = self::logParse($val);
            } else {
                if (strpos($key, 'password') !== false) {
                    $data[$key] = '******';
                }
            }
        }
        return $data;
    }
}
