<?php

namespace Echoyl\Sa\Services\admin;

use Echoyl\Sa\Services\AdminService;
use Illuminate\Support\Arr;

class PlatformService
{
    /**
     * 关联的platform_id字段名称
     *
     * @var string
     */
    public static $platform_key = 'platform_id';

    /**
     * 平台表中的title字段名称
     *
     * @var string
     */
    public static $platform_title = 'title';

    /**
     * @var array 可使用 0 和 当前平台的数据
     */
    public static $no_table_names = [
        'perm_role',
    ];

    /**
     * @var array 表中使用的是platform_id
     */
    public static $platform_table_names = ['perm_user'];

    /**
     * @var array 平台表 除了super 全部ban掉
     */
    public static $deny_table_names = ['platform'];

    /**
     * @var string 平台表名 配置platformClass后自动使用该对象的表名
     */
    public static $platform_table = 'platform';

    // 自动插入platform_id 的表
    public static $set_platform_table = [];

    /**
     * @var array 使用user_id为当前用户id字段名称的表
     */
    public static $user_id_tabel = ['perm_log'];

    /**
     * 获取平台表名 如果有自定义的platformClass 则使用该对象的表名，否则使用platform_table
     *
     * @return void
     */
    public static function getPlatformTable()
    {
        $class = static::getPlatformClass();
        if ($class) {
            return (new $class)->getTable();
        } else {
            return static::$platform_table;
        }
    }

    public static function getPlatformClass()
    {
        return config('sa.platformClass');
    }

    public static function search($m, $model)
    {
        $platform_key = static::$platform_key;
        $user_platform_id = static::getUserPlatformId();

        $table_name = $model->getTable();

        // 是否可以使用0 和当前平台的数据
        $can_use_public_data = in_array($table_name, static::$no_table_names);

        if (in_array($table_name, static::$platform_table_names) || $model->with_platform_id) {
            if (AdminService::isSuper()) {
                // 增加平台切换后要读取header中的platform_id
                $request_id = request($platform_key);
                if ($request_id || ! $user_platform_id) {
                    // 如果有请求参数,则使用请求参数,没有获取到user_platform_id也不做数据过滤
                    return $m;
                }
            }
            if ($can_use_public_data) {
                $m = $m->whereIn($platform_key, [$user_platform_id, 0]);
            } else {
                $m = $m->where($platform_key, $user_platform_id);
            }

            return $m;
        }

        if (AdminService::isSuper()) {
            // 超级管理员不需要搜索过滤
            return $m;
        }

        if (static::isDeny($table_name)) {
            return $m->where(['id' => -1]);
        }

        if ($table_name == static::getPlatformTable()) {
            $m = $m->where('id', $user_platform_id);
        } else {
            $sys_admin_ids = static::adminIds($user_platform_id, $can_use_public_data);
            if (in_array($table_name, static::$user_id_tabel)) {
                $m = $m->whereIn('user_id', $sys_admin_ids);
            } else {
                $m = $m->whereIn('sys_admin_id', $sys_admin_ids);
            }
        }

        return $m;
    }

    public static function isDeny($table)
    {
        return in_array($table, static::$deny_table_names);
    }

    public static function validateData(&$data, $item, $model)
    {

        if (AdminService::isSuper()) {
            // 超级管理员不需要搜索过滤
            return true;
        }

        $table_name = $model->getTable();

        if (static::isDeny($table_name)) {
            return false;
        }

        $id = Arr::get($item, 'id');

        $key_name = 'sys_admin_id';
        $platform_key = static::$platform_key;

        if (in_array($table_name, static::$platform_table_names) || $model->with_platform_id) {
            $key_name = $platform_key;
        }

        if (in_array($table_name, static::$user_id_tabel)) {
            $key_name = 'user_id';
        }

        $sys_admin_id = Arr::get($item, $key_name);

        $user = AdminService::user();
        // d($sys_admin_id);
        if ($id) {
            // edit
            if (Arr::exists($data, $key_name)) {
                unset($data[$key_name]); // 不支持修改后台用户id
            }

            // 如果是set_platform_table中的表 需要插入平台id
            if (in_array($table_name, static::$set_platform_table) && Arr::exists($data, $platform_key)) {
                unset($data[$platform_key]); // 不支持修改平台id
            }

            if (! Arr::exists($item, $key_name)) {
                return true;
            }

        } else {
            // add 添加数据系统默认会插入 sys_admin_id
            // 如果是set_platform_table中的表 需要插入平台id
            if (in_array($table_name, static::$set_platform_table) || $model->with_platform_id) {
                $data[$platform_key] = $user[$platform_key];
            }

            return true;
        }

        if (! $sys_admin_id) {
            return false;
        }

        $user = AdminService::user();

        if ($key_name == $platform_key) {
            return $item[$key_name] == $user[$key_name];
        } else {
            $model = AdminService::getUserModel();
            $sys_admin = $model->where(['id' => $sys_admin_id])->first();
            if (! $sys_admin) {
                return false;
            }

            $sys_admin_ids = static::adminIds($sys_admin[$platform_key]);

            return in_array($user['id'], $sys_admin_ids);
        }
    }

    public static function adminIds($platform_id = 0, $with_no_platform_id = false)
    {

        $default_ids = $with_no_platform_id ? [0] : [];
        if (! $platform_id) {
            // 默认之前的账号未设置平台
            return $default_ids;
        }
        $model = AdminService::getUserModel();
        $admin_ids = $model->where([static::$platform_key => $platform_id])->pluck('id')->toArray();

        return array_merge($admin_ids, $default_ids);
    }

    /**
     * 平台查询，独立使用
     *
     * @param [type] $query
     * @param  bool  $force
     * @return void
     */
    public static function platformQuery($query, $force = false)
    {
        if (AdminService::isSuper()) {
            // 超级管理员不需要搜索过滤
            return $query;
        }
        $user = AdminService::user();
        $platform_key = static::$platform_key;
        $user_platform_id = Arr::get($user, $platform_key);
        if ($user_platform_id) {
            $query = $query->where(function ($q) use ($user_platform_id, $force, $platform_key) {
                if ($force) {
                    $q->where([$platform_key => $user_platform_id]);
                } else {
                    $q->where([$platform_key => 0])->orWhere([$platform_key => $user_platform_id]);
                }
            });
        }

        return $query;
    }

    /**
     * 数据通过user关联查询用户的平台id
     *
     * @param [type] $query
     * @param  bool  $force
     * @return void
     */
    public static function userQuery($query, $force = false)
    {
        if (AdminService::isSuper()) {
            // 超级管理员不需要搜索过滤
            return $query;
        }

        $query = $query->whereHas('user', function ($q) use ($force) {
            $q = static::platformQuery($q, $force);
        });

        return $query;

    }

    /**
     * 读取当前用户的平台列表 superadmin获取全部，非superadmin获取当前用户平台
     *
     * @return array
     */
    public static function getList($user = false)
    {
        $user = $user ?: AdminService::user();
        $platform_key = static::$platform_key;
        $platform_title = static::$platform_title;
        $platform_id = Arr::get($user, $platform_key);
        $platformClass = static::getPlatformClass();
        if (! $platformClass) {
            return [];
        }
        $select = ['id as key', $platform_title.' as label'];
        $platforms = $platformClass::select($select);
        if (! AdminService::isSuper()) {
            $platforms = $platforms->where(['id' => $platform_id]);
        }

        return $platforms->get()->toArray();
    }

    /**
     * 获取用户平台id 这里会检测前端请求header中sa-platform
     *
     * @param  bool  $user
     * @return int
     */
    public static function getUserPlatformId($user = false)
    {
        $user = $user ?: AdminService::user();
        $key = static::$platform_key;
        $admin_platform_id = 0;
        if (AdminService::isSuper($user)) {
            $admin_platform_id = request()->header('sa-platform');
        } else {
            $admin_platform_id = Arr::get($user, $key);
        }

        return $admin_platform_id;
    }
}
