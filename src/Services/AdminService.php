<?php
namespace Echoyl\Sa\Services;


use Echoyl\Sa\Models\PermUser;

class AdminService
{
    public static function checkToken()
    {
        $user = self::user();
        if($user)
        {
            //读取到用户 检测用户权限
            return true;
        }else
        {
            return false;
        }
    }

    public static function user()
    {
        $user = request()->user();
        if($user && $user->currentAccessToken()->name == 'admin')
        {
            return $user;
        }else
        {
            return false;
        }
        
        
    }

    public static function pwd($str)
    {
        return md5($str);
    }

    public static function login(PermUser $user)
    {
        //session(['user'=>$user]);
        $token = $user->createToken('admin');

        return $token->plainTextToken;
    }


    public static function logout()
    {
        request()->user()->currentAccessToken()->delete();
        return;
    }

    public static function checkAuth()
    {
        $user = self::user();
        if ($user['id'] != 1) {
            //id为1是超级管理员
            //读取用户权限信息 实时读取
            $permUser = new PermUser();
            $perms = $permUser->where(['id'=>$user['id']])->select(['perms2','roleid'])->with(['role'=>function($q){
                $q->select(['id','perms2']);
            }])->first()->toArray();
            //默认的命名空间
            $default_namespace = [
                '\\Echoyl\\Sa\\Http\\Controllers\\admin',
                'Echoyl\\Sa\\Http\\Controllers\\admin',
                'App\\Http\\Controllers\\admin'
            ];
            //解析route
            $action = request()->route()->action;
            
            $namespace = $action['namespace'];
            $controller = str_replace($namespace.'\\','',$action['controller']);
            
            //处理命名空间
            foreach($default_namespace as $val)
            {
                $namespace = str_replace($val,'',$namespace);
            }
            //d($namespace);
            list($c,$a) = explode('Controller@',$controller);
            if($a == 'store')
            {
                //检测是 编辑还是 新增
                if(request('id',0))
                {
                    $a = 'edit';
                }else
                {
                    $a = 'add';
                }
            }
            $now_router = implode('.',[$c,$a]);
            if($namespace)
            {
                $now_router = implode('.',[$namespace,$now_router]);
            }
            $now_router = trim(strtolower($now_router),'\\');
            //d($perms);
            $perm_obj = new PermService($perms['perms2'],$perms['role']['perms2']);
            $perm = $perm_obj->check_perm($now_router);
            //d($now_router,$perms['perms2'],$perm,$action);
            if (!$perm) {
                return false;
            }
        }
        return true;
    }

    public static function getUsers()
    {
        $user = self::user();
        $permUser = new PermUser();
        if($user['id'] == 1)
        {
            return $permUser->select('id','username')->where('id','!=',1)->get();
        }else
        {
            return $permUser->select('id','username')->where('id','=',$user['id'])->get();
        }
    }

}
