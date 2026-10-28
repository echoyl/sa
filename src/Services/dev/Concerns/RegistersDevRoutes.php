<?php

namespace Echoyl\Sa\Services\dev\Concerns;

use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * DevService 拆分：RegistersDevRoutes
 */
trait RegistersDevRoutes
{
    /**
     * 当前请求内的路由定义内存缓存，避免重复 include 缓存文件
     *
     * @var array
     */
    protected static $routeDefinitionsMemo = [];

    public static function aliasRoute()
    {
        self::registerRouteDefinitions(self::routeDefinitions('admin'));
    }



    public static function aliasRouteSystem()
    {
        self::registerRouteDefinitions(self::routeDefinitions('system'));
    }



    /**
     * 兼容旧调用：直接把菜单树注册为路由（不读取路由定义缓存）
     */
    public static function createRoute($menus, $prefix = [])
    {
        self::registerRouteDefinitions(self::buildRouteDefinitions($menus, $prefix));
    }



    /**
     * 获取某个类型的路由定义
     *
     * 这里只缓存 “uri => controller@action” 的纯数组，不缓存 Route 对象，
     * 所以即使项目执行了 php artisan route:cache 也不会因为闭包而失败。
     *
     * @param  string  $type  admin|system
     * @return array
     */
    public static function routeDefinitions($type = 'admin')
    {
        if (array_key_exists($type, self::$routeDefinitionsMemo)) {
            return self::$routeDefinitionsMemo[$type];
        }

        $cache_enabled = (bool) config('sa.route_cache', true);

        if (! $cache_enabled) {
            return self::$routeDefinitionsMemo[$type] = self::buildRouteDefinitions(self::getMenuByParentId(0, $type === 'admin' ? '' : $type));
        }

        $path = self::routeCachePath();
        $cache = self::readRouteCache($path);
        if ($cache !== null && array_key_exists($type, $cache)) {
            self::$routeDefinitionsMemo = $cache;

            return $cache[$type];
        }

        // 两种类型的路由一起生成并写入，避免第二次调用时再生成一次
        $definitions = [
            'admin' => self::buildRouteDefinitions(self::getMenuByParentId()),
            'system' => self::buildRouteDefinitions(self::getMenuByParentId(0, 'system')),
        ];

        // 菜单表未安装/还没有菜单时不写缓存，避免安装完数据后一直读到空路由
        if (! empty($definitions['admin']) || ! empty($definitions['system'])) {
            self::writeRouteCache($path, $definitions);
        }

        self::$routeDefinitionsMemo = $definitions;

        return $definitions[$type] ?? [];
    }



    /**
     * 把菜单树编译成可缓存的路由定义数组
     *
     * @param  array  $menus
     * @param  array  $prefix
     * @param  array  $definitions
     * @return array
     */
    public static function buildRouteDefinitions($menus, $prefix = [], &$definitions = null)
    {
        $definitions = $definitions ?? [];
        if (! $menus) {
            return $definitions;
        }

        $self = new self;
        $only_action_types = ['form', 'panel', 'panel2', 'justTable'];

        foreach ($menus as $menu) {
            $children = self::getMenuByParentId($menu['id']);
            if (! empty($children)) {
                self::buildRouteDefinitions($children, array_merge($prefix, [$menu['path']]), $definitions);
                continue;
            }

            if (empty($menu['admin_model'])) {
                continue;
            }

            $model = $menu['admin_model'];
            if (($model['type'] ?? null) != 1) {
                continue;
            }

            // 检测是否有namespace
            $model_path = array_reverse(self::getPath($model, $self->allModel()));
            $_prefix = array_merge($prefix, [$menu['path']]);

            // 20240419这里只检测指向模型属于系统还是项目，不再设置namespace
            $_model_path = $model_path;
            $name = array_pop($_model_path);
            $c = ! empty($_model_path) ? implode('\\', $_model_path).'\\' : '';
            if (class_exists('App\Http\Controllers\admin\\'.$c.ucfirst($name).'Controller')) {
                // 这样设置的话 系统路由中存在项目路由
                $controller_prefix = '\App\Http\Controllers\admin\\'.$c;
            } else {
                // 项目路由中存在指向系统控制器的路由
                $controller_prefix = '\Echoyl\Sa\Http\Controllers\admin\\'.$c;
            }

            $controller = $controller_prefix.ucfirst($name).'Controller';

            // 默认所有路由都走这里 因为都有一个项目namespace
            if (count($model_path) > 1) {
                // 读取菜单的其它权限设置 单独的action方法独立写路由
                $perms = HelperService::json_validate($menu['perms']);
                if ($perms && is_array($perms)) {
                    foreach ($perms as $key => $title) {
                        $definitions[] = [
                            'uri' => implode('/', array_merge($_prefix, [$key])),
                            'action' => $controller.'@'.$key,
                        ];
                    }
                }
            }

            if (in_array($menu['page_type'], $only_action_types)) {
                // 如果是form直接指向控制器方法
                $definitions[] = [
                    'uri' => implode('/', $_prefix),
                    'action' => $controller.'@'.$menu['path'],
                ];
            } else {
                $definitions[] = [
                    'uri' => implode('/', $_prefix),
                    'resource' => $controller,
                ];
            }
        }

        return $definitions;
    }



    /**
     * 把路由定义注册到 Router（顺序必须与 buildRouteDefinitions 一致）
     *
     * @param  array  $definitions
     * @return void
     */
    public static function registerRouteDefinitions($definitions)
    {
        foreach ($definitions as $definition) {
            $uri = $definition['uri'];
            if (isset($definition['resource'])) {
                Route::resource($uri, $definition['resource']);
            } elseif (isset($definition['methods'])) {
                Route::match($definition['methods'], $uri, $definition['action']);
            } else {
                Route::any($uri, $definition['action']);
            }
        }
    }



    /**
     * 路由定义缓存文件路径
     *
     * @return string
     */
    public static function routeCachePath()
    {
        return app()->bootstrapPath('cache/'.self::ROUTE_CACHE_FILE);
    }



    /**
     * 当前缓存文件是否存在且签名有效
     *
     * 用于命令/自检：文件存在但签名过期（例如删除失败留下的旧文件）不会被当成有效缓存。
     *
     * @return bool
     */
    public static function routeCacheValid()
    {
        return self::readRouteCache(self::routeCachePath()) !== null;
    }



    /**
     * 缓存签名：项目名/路由前缀变化、或菜单变更（代次号 +1）时自动失效
     *
     * 代次号放在共享 Cache 里，这样即使缓存文件因为权限问题删不掉，
     * 签名也会不匹配从而强制重建，不会继续返回陈旧路由；多机部署时各节点也保持一致。
     *
     * @return string
     */
    public static function routeCacheSignature()
    {
        return md5(implode('|', [
            self::ROUTE_CACHE_VERSION,
            self::routeCacheGeneration(),
            self::appname(),
            config('sa.app_prefix', ''),
            config('sa.app_admin_prefix', ''),
        ]));
    }



    /**
     * 读取路由缓存代次号
     *
     * 缓存不可用时退回 0，最多导致多重建一次缓存，不影响正确性。
     *
     * @return int
     */
    public static function routeCacheGeneration()
    {
        try {
            return (int) Cache::get(self::ROUTE_CACHE_GENERATION_KEY, 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }



    /**
     * 递增路由缓存代次号，使所有已生成的缓存文件失效
     *
     * @return void
     */
    protected static function bumpRouteCacheGeneration()
    {
        try {
            Cache::forever(self::ROUTE_CACHE_GENERATION_KEY, self::routeCacheGeneration() + 1);
        } catch (\Throwable $e) {
            Log::warning('sa 路由缓存代次号更新失败：'.$e->getMessage());
        }
    }



    /**
     * 读取并校验路由定义缓存，无效时返回 null
     *
     * @param  string  $path
     * @return array|null
     */
    protected static function readRouteCache($path)
    {
        if (! is_file($path)) {
            return null;
        }

        try {
            // 并发删除/文件损坏时 include 只会产生 warning 并返回 false，抑制输出避免污染响应
            $data = @include $path;
        } catch (\Throwable $e) {
            return null;
        }

        if (! is_array($data)
            || ($data['signature'] ?? '') !== self::routeCacheSignature()
            || ! is_array($data['routes'] ?? null)) {
            return null;
        }

        return $data['routes'];
    }



    /**
     * 原子写入路由定义缓存
     *
     * @param  string  $path
     * @param  array  $definitions
     * @return bool
     */
    protected static function writeRouteCache($path, $definitions)
    {
        $data = [
            'version' => self::ROUTE_CACHE_VERSION,
            'signature' => self::routeCacheSignature(),
            'routes' => $definitions,
        ];

        $content = "<?php\n\n// 由 echoyl/sa 自动生成，请勿手动修改；重新生成：php artisan sa:route cache\nreturn ".var_export($data, true).";\n";

        try {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                Log::warning('sa 路由缓存目录不存在，已跳过写入：'.$dir);

                return false;
            }

            // is_writable 只做提前返回，真正的判据是后面的 file_put_contents/rename
            if (! is_writable($dir)) {
                Log::warning('sa 路由缓存目录不可写，已跳过写入（后台动态路由将每次请求重新构建）：'.$dir);

                return false;
            }

            $tmp = $path.'.'.getmypid().'.tmp';
            if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
                Log::warning('sa 路由缓存临时文件写入失败（磁盘满/权限/只读挂载？）：'.$tmp);

                return false;
            }

            // 同一分区下 rename 是原子操作，避免并发读到写了一半的文件
            if (! @rename($tmp, $path)) {
                // Windows 上目标文件已存在/被占用时 rename 可能失败，退化为直接覆盖写
                $ok = @file_put_contents($path, $content, LOCK_EX) !== false;
                @unlink($tmp);

                if (! $ok) {
                    Log::warning('sa 路由缓存写入失败，请检查目录权限：'.$path);

                    return false;
                }
            }

            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($path, true);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('sa 路由缓存写入失败：'.$e->getMessage());

            return false;
        }
    }



    /**
     * 清理动态路由缓存（菜单/模型变更后调用）
     *
     * @return void
     */
    public static function flushRouteCache()
    {
        self::$routeDefinitionsMemo = [];

        // 先递增代次号：即使下面的文件删除失败，旧文件的签名也会失效，
        // 不会再被 readRouteCache() 当成有效缓存继续使用
        self::bumpRouteCacheGeneration();

        $path = self::routeCachePath();

        if (is_file($path) && ! @unlink($path)) {
            Log::warning('sa 路由缓存文件删除失败，已通过代次号使其失效（请检查目录权限）：'.$path);
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }

        // 项目如果执行过 php artisan route:cache，动态菜单路由已被写进整站缓存文件，
        // 这里一并清掉，下一次请求会重新从菜单数据生成路由，避免新增菜单 404
        if (app()->routesAreCached()) {
            try {
                \Illuminate\Support\Facades\Artisan::call('route:clear');
            } catch (\Throwable $e) {
                Log::warning('sa 清理 route:cache 失败：'.$e->getMessage());
            }
        }
    }
}
