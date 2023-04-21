<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\perm\Log;
use Echoyl\Sa\Models\perm\User;
use Echoyl\Sa\Services\dev\MenuService;
use Illuminate\Http\Request;

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

    public static function login(User $user)
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

        $now_router = self::getNowRouter();

        //$config_namespace = config('sa.namespace', []);

        if ($user['id'] != 1) {
            //id为1是超级管理员
            //读取用户权限信息 实时读取
            $permUser = new User();
            $perms = $permUser->where(['id' => $user['id']])->select(['perms2', 'roleid'])->with(['role' => function ($q) {
                $q->select(['id', 'perms2']);
            }])->first()->toArray();
            $as = new MenuService;
            
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
        $permUser = new User();
        if ($user['id'] == 1) {
            return $permUser->select('id', 'username')->where('id', '!=', 1)->get();
        } else {
            return $permUser->select('id', 'username')->where('id', '=', $user['id'])->get();
        }
    }

    public static function log(Request $request, $force_type = false, $data = [])
    {
        if (!empty($data)) {
            $admin = $data;
        } else {
            $admin = self::user();
        }
        if (!$admin) {
            return;
        }
        if ($request->isMethod('post') || $force_type) {
            //只记录post的日志
            //屏蔽敏感数据
            $rq = $request->all();
            if (isset($rq['post'])) {
                unset($rq['post']);
            }
            $log_data = self::logParse($rq);

            $data = [
                'user_id' => $admin['id'],
                'url' => $request->fullUrl(),
                'request' => json_encode($log_data),
                'ip' => $request->ip(),
                'created_at' => now(),
                'type' => $force_type ?: 'POST',
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
