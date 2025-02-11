<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\perm\Log;
use Echoyl\Sa\Models\perm\User;
use Echoyl\Sa\Services\admin\LocaleService;
use Echoyl\Sa\Services\admin\SocketService;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\MenuService;
use Echoyl\Sa\Services\dev\utils\Utils;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

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

    public static function isDevUser($user = false)
    {
        $user = $user?:self::user();
        return $user && $user['desc'] == 'dev';
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
            'setting'=>self::setting($user),
            'user'=>$user,
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
        $user = $model->where(['username'=>$username])->orWhere(['mobile'=>$username])->first();
        if($user && $user['password'] == self::pwd($pwd))
        {
            return self::doLogin($user);
        }
        return false;
    }

    public static function setting($user = false)
    {
        $ds = new DevService;
        $ds->allMenu(true);
        $ds->allModel(true);
        $ss = new SetsService();
        $setting = $ss->getSet('setting');

        if(isset($setting['theme']))
        {
            $theme = $setting['theme'];
            unset($setting['theme']);
            $setting = array_merge($theme,$setting);
        }

        HelperService::deImagesOne($setting,['logo','favicons','loginBgImage']);
        $setting['title'] = Arr::get($setting,'title','DeAdmin');
        $setting['tech'] = Arr::get($setting,'tech','DeAdmin 技术支持');
        $setting['subtitle'] = Arr::get($setting,'subtitle','后台管理系统');
        $setting['baseurl'] = Arr::get($setting,'baseurl','/antadmin/');
        $setting['logo'] = $setting['logo']['url']?:false;
        $setting['loginBgImage'] = $setting['loginBgImage']['url']?:false;
        $setting['loginBgCardColor'] = Arr::get($setting,'loginBgCardColor','none');
        $setting['loginTypeDefault'] = Arr::get($setting,'loginTypeDefault','password');
        $setting['fileImagePrefix'] = HelperService::getFileImagePrefix();//图片文件路径前缀，是前端支持字符串显示图片
        //$setting['colorPrimary'] = Arr::get($setting,'colorPrimary','#006eff');
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
        $is_super = AdminService::isSuper($user);
        $is_dev = AdminService::isDevUser($user);
        //新增 体验权限也能看到 开发菜单
        $setting['dev'] = $dev && ($is_super || $is_dev);
        $setting['lang'] = Arr::get($setting,'lang',true);
        //$setting['splitMenus'] = Arr::get($setting,'splitMenus',false);//默认不再开启自动分割菜单
        $setting['locales'] = LocaleService::getSetting($user);
        //加入开发环境时 全局数据
        if($setting['dev'])
        {
            $ds = new DevService;
            $setting['dev'] = [
                'allMenus' => $ds->getMenusTree(),
                'allModels'=> DevService::allModels(),
                'allModelsTree'=>$ds->getModelsTree(),
                'folderModelsTree'=>$ds->getModelsFolderTree()
            ];
        }

        return $setting;
    }

    public static function menuData($user = false)
    {
        if(!$user)
        {
            $user = self::user();
        }
        if(!$user)
        {
            return [];
        }
        $is_super = self::isSuper($user);
        $auth_ids = [];
        $ms = new MenuService;
        if(!$is_super)
        {
            $perms2 = explode(',',$user['perms2']);
            foreach($perms2 as $perm)
            {
                [$id,$action_name] = explode('.',$perm);
                if($action_name && $action_name == 'dataList')
                {
                    //将不需要菜单的action 过滤掉
                    continue;
                }
                $parent_ids = $ms->getParentId($id);
                if($parent_ids)
                {
                    $auth_ids = array_merge($parent_ids,$auth_ids);
                }
                
            }
            $auth_ids = array_unique($auth_ids);
        }
        return $ms->get(0,$is_super?false:$auth_ids);
    }

    public static function parseUser($user)
    {
        $ss = new SetsService();
        $setting = $ss->getSet('setting');
        HelperService::deImagesOne($setting,['logo']);

        HelperService::deImagesOne($user,['avatar']);
        $avatar = $user['avatar']['url']?:($setting['logo']['url']?:'');

		$as = new MenuService;
        $rolename = $user['role']?$user['role']['title']:'超级管理员';
        $info = [
            'id' => $user['id'],
            'username' => $user['username'],
            'realname'=>$user['realname'],
            'roleid' => $user['roleid'],
            'rolename'=>$rolename,
            'name' => $user['username'],
            'avatar' => $avatar,
            'permission' => $user['perms2'],
			'menuData'=>self::menuData($user),
        ];
        return $info;
    }
    public static function deving()
    {
        $ss = new SetsService();
        $setting = $ss->getSet('setting');
        $dev = Arr::get($setting,'dev',true);
        return $dev;
    }


    public static function logout()
    {
        SocketService::logoutByToken(request()->user()->currentAccessToken()->id);
        request()->user()->currentAccessToken()->delete();
        return;
    }

    public static function updateUserInfo($id,$info)
    {
        $model = self::getUserModel();
        $model->where(['id'=>$id])->update($info);
        return;
    }

    public static function checkDevAuth($user,$router)
    {
        $is_dev = self::isDevUser($user);
        if(!$is_dev)
        {
            return false;
        }
        $router = $router?explode('/',$router):[];
        if(empty($router) || $router[0] != 'dev')
        {
            return false;
        }
        if(request()->isMethod('GET'))
        {
            //只在get请求有权限
            return true;
        }else
        {
            return false;
        }
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
            //注入一段 体验账号的权限检测
            if(self::checkDevAuth($user,$now_router))
            {

            }else
            {
                //d($now_router,$perms['perms2'],$perms['role']['perms2']);
                $has_perm = $as->checkPerm($now_router,$perms['perms2'],$perms['role']?$perms['role']['perms2']:'');
                if (!$has_perm) {
                    return false;
                }
            }
            
        }

        //检测菜单是否有默认请求参数
        $ms = new MenuService;
        [$name,$menu] = $ms->getMenuByRouter($now_router);
        request()->merge(['dev_menu'=>$menu]);
        //d($menu,$now_router);
        $other_config = Arr::get($menu,'other_config');
        $_category_id = Arr::get($menu,'_category_id');

        $defaultData = [];

        if($other_config)
        {
            $other_config = json_decode($other_config,true);
            $defaultData = $other_config['defaultData']??[];
        }

        if($_category_id)
        {
            $defaultData['cid'] = json_decode($_category_id,true);
        }
        
        foreach($defaultData as $key=>$val)
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
            request()->merge([$key=>$val]);
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
            //检测是 编辑还是 新增 0.4版本就加了这个，现在去掉，现在点击新建按钮也能展开表单，不提示无权限
            // if (!$id) {
            //     $a = 'add';
            // }
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
        if($name)
        {
            $path[] = $name;
        }
        

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

    public function loginCheck()
    {
        $ss = new SetsService();
        $setting = $ss->getSet('setting');
        $times = Arr::get($setting,'login_error_times',3);
        $error_times = $this->getLoginErrorTimes();
        if($error_times >= $times)
        {
            //超过登录失败次数
            return false;
        }else
        {
            return true;
        }
    }

    public function getLoginErrorTimes()
    {
        $key = $this->getLoginErrorKey();
        $error_times = Cache::get($key);
        return $error_times?:0;
    }

    public function getLoginErrorKey()
    {
        $ip = request()->ip();
        return implode('_',['login_error',date("Ymd"),$ip]);
    }

    public function loginErrorLog($type = 'add')
    {
        $key = $this->getLoginErrorKey();
        $times = $this->getLoginErrorTimes();
        Cache::put($key,$type == 'add'? $times + 1 : 0,now()->addDays(1));
        return;
    }
}
