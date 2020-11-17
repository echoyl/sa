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
            $perms = $permUser->find($user['id'],['perms2']);
            //解析route
            $controller = str_replace("App\\Http\\Controllers\\admin\\",'',request()->route()->action['controller']);
            //检测是否有命名空间
            if(strpos($controller,'\\') !== false)
            {
                list($namespace,$controller) = explode('\\',$controller);           
            }else
            {
                $namespace = '';
                
            }
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
            if($namespace)
            {
                $now_router = implode('.',[$namespace,$c,$a]);
            }else
            {
                $now_router = implode('.',[$c,$a]);
            }
            $perm_obj = new PermService($perms['perms2']);
            $perm = $perm_obj->check_perm(strtolower($now_router));
            //d($now_router,$perms['perms2'],$perm);
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
