<?php
namespace Echoyl\Sa\Services\admin;

use Echoyl\Sa\Services\AdminService;
use Illuminate\Support\Arr;

class PlatformService
{

    /**
     * @var array 可使用 0 和 当前平台的数据
     */
    static $no_table_names = [
        'perm_role',
    ];

    /**
     * @var array 表中使用的是platform_id
     */
    static $platform_table_names = ['perm_user'];

    /**
     * @var array 平台表 除了super 全部ban掉
     */
    static $deny_table_names = ['platform'];

    public static function search($m,$model)
    {
        if(AdminService::isSuper())
        {
            //超级管理员不需要搜索过滤
            return $m;
        }
        
        $table_name = $model->getTable();


        if(self::isDeny($table_name))
        {
            return $m->where(['id'=>-1]);
        }

        $user = AdminService::user();
        if(in_array($table_name,self::$platform_table_names))
        {
            $m = $m->where('platform_id',$user['platform_id']);
        }else
        {
            $sys_admin_ids = self::adminIds($user['platform_id'],in_array($table_name,self::$no_table_names));
            $m = $m->whereIn('sys_admin_id',$sys_admin_ids);
        }
        
        return $m;
    }

    public static function isDeny($table)
    {
        return in_array($table,self::$deny_table_names);
    }

    public static function validateData(&$data,$item,$model)
    {       

        if(AdminService::isSuper())
        {
            //超级管理员不需要搜索过滤
            return true;
        }

        $table_name = $model->getTable();

        if(self::isDeny($table_name))
        {
            return false;
        }
        
        $id = Arr::get($item,'id');

        
        $key_name = 'sys_admin_id';

        if(in_array($table_name,self::$platform_table_names))
        {
            $key_name = 'platform_id';
        }

        $sys_admin_id = Arr::get($item,$key_name);

        //d($sys_admin_id);
        if($id)
        {
            //edit
            if(Arr::exists($data,$key_name))
            {
                unset($data[$key_name]);//不支持修改后台用户id
            }

            if(!Arr::exists($item,$key_name))
            {
                return true;
            }

        }else
        {
            //add 添加数据系统默认会插入 sys_admin_id
            return true;
        }

        if(!$sys_admin_id)
        {
            return false;
        }

        $user = AdminService::user();

        if($key_name == 'platform_id')
        {
            return $item[$key_name] == $user[$key_name];
        }else
        {
            $model = AdminService::getUserModel();
            $sys_admin = $model->where(['id'=>$sys_admin_id])->first();
            if(!$sys_admin)
            {
                return false;
            }
    
            $sys_admin_ids = self::adminIds($sys_admin['platform_id']);
            
            return in_array($user['id'],$sys_admin_ids);
        }
    }


    public static function adminIds($platform_id = 0,$with_no_platform_id = false)
    {
        
        $default_ids = $with_no_platform_id ? [0]:[];
        if(!$platform_id)
        {
            //默认之前的账号未设置平台 
            return $default_ids;
        }
        $model = AdminService::getUserModel();
        $admin_ids = $model->where(['platform_id'=>$platform_id])->pluck('id')->toArray();

        return array_merge($admin_ids,$default_ids);
    }


}