<?php

namespace Echoyl\Sa\Services\dev\Concerns;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * DevService 拆分：HasDevData
 */
trait HasDevData
{


    public static function appname()
    {
        return config('app.name', '');
    }



    public static function getPath($val, $menus, $field = 'name')
    {
        $path = Utils::getPath($val, $menus, $field);
        // Log::channel('daily')->info('getPath:',['path'=>$path,'val'=>$val]);
        $pl = count($path);
        // 加一个判断如果最后一个不是项目名称就自动追加name 限于获取模型的路径
        $type = Arr::get($val, 'admin_type', '');

        if ($type && $type != 'system' && $pl > 0) {
            $path[] = $type;
            // $app_name = self::appname();
            // if($path[$pl-1] != $app_name)
            // {
            //     $path[] = $app_name;
            // }
        }

        return $path;
    }



    public function line($msg)
    {
        $this->msg[] = $msg;
    }



    /**
     * 获取所有模型
     *
     * @param  bool  $flush  如果为true表示重新获取最新数据
     * @return void
     */
    public function allModel($flush = false)
    {
        static $data = [];
        if (empty($data) || $flush) {
            $data = Cache::get('devAllModel');
            if (! $data || $flush) {
                $data = (new Model)->orderBy('parent_id', 'asc')->orderBy('id', 'asc')->get()->toArray();
                Cache::set('devAllModel', $data);
            }
        }

        return $data;
    }



    public function allMenu($flush = false)
    {
        static $data = null;
        if ($data !== null && ! $flush) {
            return $data;
        }

        // 先读缓存，命中时无需再执行 Schema::hasTable/information_schema 查询
        if (! $flush) {
            $cached = Cache::get('devAllMenu');
            if (is_array($cached)) {
                return $data = $cached;
            }
        }

        // 未安装 dev_menu 表时直接返回空数组（此时才做表检测，避免每次请求都查元数据）
        if (! Schema::hasTable('dev_menu')) {
            return $data = [];
        }

        $data = (new Menu)->with(['adminModel'])->orderBy('parent_id', 'asc')->orderBy('id', 'asc')->get()->toArray();
        Cache::set('devAllMenu', $data);

        return $data;
    }



    public static function getMenuByParentId($pid = 0, $type = '')
    {
        $s = new self;
        $type = $type ?: config('app.name');

        return collect($s->allMenu())->filter(function ($item) use ($pid, $type) {
            if ($pid) {
                // 有id的话 读取父级id 不在现在菜单类型
                return $item['parent_id'] === $pid;
            } else {
                return $item['parent_id'] === $pid && in_array($item['type'], [$type]) === true;
            }
        })->toArray();
    }



    public static function getModelByParentId($pid = 0)
    {
        return (new Model)->where(['parent_id' => $pid])->orderBy('type', 'asc')->get()->toArray();
    }



    /**
     * 获取所有模型数据处理相应的格式 前端使用
     *
     * @return void
     */
    public static function allModels()
    {
        $model = new Model;
        $data = [];
        $types = Utils::packageTypeArr();
        $list = $model->where(['type' => 1])->with(['relations' => function ($query) {
            $query->select(['id', 'title', 'model_id', 'name', 'foreign_model_id', 'type'])->whereIn('type', ['one', 'many']);
        }])->whereIn('admin_type', $types)->get()->toArray();
        foreach ($list as $val) {
            $data[] = static::modelItem($val);
        }

        return $data;
    }



    public static function modelItem($val)
    {
        $columns = Arr::get($val, 'columns', []);
        $setting = Arr::get($val, 'setting', []);
        $setting = HelperService::json_validate($setting);

        if (! empty($columns)) {
            $json = HelperService::json_validate($columns);
            $columns = $json !== false ? $json : $columns;
        }

        $search_columns = Arr::get($val, 'search_columns', []);

        if (! empty($search_columns)) {
            $json = HelperService::json_validate($search_columns);
            $search_columns = $json !== false ? $json : $search_columns;
        }

        return [
            'id' => $val['id'],
            'columns' => $columns ?: [],
            'search_columns' => $search_columns ?: [],
            'relations' => $val['relations'] ?: [],
        ];
    }
}
