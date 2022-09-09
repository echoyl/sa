<?php
namespace Echoyl\Sa\Services;

use App\Services\AdminMenuService;
use Echoyl\Sa\Models\perm\PermLog;
use Echoyl\Sa\Models\perm\PermUser;
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

        //$config_namespace = config('sa.namespace', []);

        if ($user['id'] != 1) {
            //id为1是超级管理员
            //读取用户权限信息 实时读取
            $permUser = new PermUser();
            $perms = $permUser->where(['id' => $user['id']])->select(['perms2', 'roleid'])->with(['role' => function ($q) {
                $q->select(['id', 'perms2']);
            }])->first()->toArray();
            //默认的命名空间
            // $default_namespace = [
            //     '\\Echoyl\\Sa\\Http\\Controllers\\admin',
            //     'Echoyl\\Sa\\Http\\Controllers\\admin',
            //     'App\\Http\\Controllers\\admin',
            // ];
            // if (!empty($config_namespace)) {
            //     foreach ($config_namespace as $ns) {
            //         $default_namespace[] = 'App\\Http\\Controllers\\' . $ns;
            //     }
            // }
            //解析route
            $action = request()->route()->action;
            $uri = request()->route()->uri();

            //d($uri,$action);
            
            
            //$namespace = $action['namespace'];
            //$controller = str_replace($namespace . '\\', '', $action['controller']);
            //d($controller,$namespace,$action);
            //处理命名空间
            // foreach ($default_namespace as $val) {
            //     $namespace = str_replace($val, '', $namespace);
            // }
            //d($namespace);
            //list($c, $a) = explode('Controller@', $controller);

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
            // if ($namespace) {
            //     $now_router = implode('/', [$namespace, $now_router]);
            // }
            // $now_router = trim(strtolower($now_router), '\\');
            //d($perms);
            // $perm_obj = new PermService($perms['perms2'], $perms['role']['perms2']);
            // $perm = $perm_obj->check_perm($now_router);
            // d($now_router,$perms['perms2'],$perm,$action);
            $as = new AdminMenuService;
            $has_perm = $as->checkPerm($now_router,$perms['perms2'],$perms['role']['perms2']);
            if (!$has_perm) {
                return false;
            }
        }
        return true;
    }

    public static function getUsers()
    {
        $user = self::user();
        $permUser = new PermUser();
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
            PermLog::insert($data);
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
