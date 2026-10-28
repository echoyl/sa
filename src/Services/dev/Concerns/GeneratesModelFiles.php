<?php

namespace Echoyl\Sa\Services\dev\Concerns;

use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\utils\Dev;
use Echoyl\Sa\Services\dev\utils\Schema as UtilsSchema;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

/**
 * DevService 拆分：GeneratesModelFiles
 */
trait GeneratesModelFiles
{


    /**
     * 创建数据表
     *
     * @param [type] $data 模型数据
     * @return string 不带前缀的表名
     */
    public function createModelSchema($data, $drop_columns = true)
    {
        return (new UtilsSchema)->createModelSchema($data, $drop_columns);
    }



    public function tabel2SchemaJson($name, $parent_id)
    {
        return (new UtilsSchema)->tabel2SchemaJson($name, $parent_id);
    }



    /**
     * 生成模型关系的use namespace 和 代码定义部分
     *
     * @param [type] $model
     * @param [array] $has_model 使用过的模型数据
     * @return void
     */
    public function createModelRelation($model, $useModelArr = [])
    {
        $model_id = $model['id'];
        $relations = (new Relation)->where(['model_id' => $model_id])->with(['foreignModel'])->get()->toArray();
        $namespace_data = [];
        $hasone_data = [];

        $setting = $model['setting'] ? json_decode($model['setting'], true) : [];

        $use_items = [];
        $use = '';
        $traits = [
            'soft_delete' => ['SoftDeletes', 'use Illuminate\Database\Eloquent\SoftDeletes;'],
            'with_system_admin_id' => ['InsertAdminId', 'use Echoyl\Sa\Traits\InsertAdminId;'],
            'with_platform_id' => ['WithPlatformId', 'use Echoyl\Sa\Traits\WithPlatformId;'],
            'global_data_search' => ['AdminDataSearch', 'use Echoyl\Sa\Traits\AdminDataSearch;'],
            'global_post_check' => ['AdminPostCheck', 'use Echoyl\Sa\Traits\AdminPostCheck;'],
            'has_uuids' => ['HasUuids', 'use Illuminate\Database\Eloquent\Concerns\HasUuids;'],
        ];
        foreach ($traits as $tkey => $tval) {
            if (Arr::get($setting, $tkey)) {
                $namespace_data[] = $tval[1];
                $use_items[] = $tval[0];
            }
        }

        if (! empty($use_items)) {
            $use = 'use '.implode(',', $use_items).';';
        }

        if (empty($relations)) {
            return [$namespace_data, $hasone_data, $use];
        }

        $hasone_tpl = "
    public function _name()
    {
        return \$this->has_RelationType(_modelName::class, '_foreignKey', '_localKey')_filterWhere_orderBy_withDefault;
    }";

        $has_model = [ucfirst($model['name'])];
        // $useModelArr = [];//使用过的模型数据
        foreach ($relations as $val) {
            if (! in_array($val['type'], ['one', 'many'])) {
                continue;
            }
            $foreign_model = $val['foreign_model'];

            // $foreign_model_name = ucfirst(array_pop($foreign_model_names));

            $foreign_model_name = Arr::get($useModelArr, $foreign_model['id'], ucfirst($foreign_model['name']));

            if ($model['parent_id'] != $foreign_model['parent_id']) {
                // 如果单个模型一张表多个字段对应同一个模型 则跳过、、 比如一张表中含有 省市区三个字段对应 pca表中的同一个字段
                if (isset($useModelArr[$foreign_model['id']])) {
                    $foreign_model_name = $useModelArr[$foreign_model['id']];
                } else {
                    // 同一个文件夹下面的模型不需要添加namespace

                    [$namespace, $f_model_name] = $this->getNamespace($foreign_model, $has_model);
                    $useModelArr[$foreign_model['id']] = $f_model_name;
                    $has_model[] = $f_model_name;
                    $foreign_model_name = $f_model_name;
                    $namespace_data[] = $namespace.';';
                }
            }

            // 读取with_default
            $with_default = '';
            if ($val['with_default']) {
                $with_default = json_decode($val['with_default'], true);
                if (! empty($with_default)) {
                    $with_default = '->withDefault('.Dev::export($with_default, 2).')';
                }
            }
            // 加入筛选条件 应该只有1对多的时候才加入 1对一也需要加入
            // 20241217 当时1对1是可能存在类型字段，类型不同时hasone就需要筛选
            $filter_where = '';
            if ($val['filter'] && ($val['type'] == 'many' || $val['type'] == 'one')) {
                $filter = json_decode($val['filter'], true);
                // 关联模型不支持变量参数过滤掉
                $_filter = [];
                foreach ($filter as $f) {
                    if (isset($f[1]) && ($f[1] == 'in' || $f[1] == 'between') && is_array($f[2])) {
                        $filter_where .= '->'.($f[1] == 'in' ? 'whereIn' : 'whereBetween').'("'.$f[0].'",'.json_encode($f[2]).')';

                        continue;
                    }
                    if (isset($f[2]) && is_string($f[2]) && strpos($f[2], 'this.') !== false) {
                        continue;
                    }
                    $_filter[] = $f;
                }
                if (! empty($_filter)) {
                    $filter_where .= '->where('.json_encode($_filter).')';
                }
            }
            // 加入排序条件
            $order_by = '';
            if ($val['order_by']) {
                $_order_by = json_decode($val['order_by'], true);
                foreach ($_order_by as $ob) {
                    $order_by .= '->orderBy("'.$ob[0].'","'.$ob[1].'")';
                }
            }

            $hasone_data[] = str_replace([
                '_name',
                '_modelName',
                '_foreignKey',
                '_localKey',
                '_RelationType',
                '_withDefault',
                '_filterWhere',
                '_orderBy',
            ], [
                $val['name'],
                $foreign_model_name,
                $val['foreign_key'],
                $val['local_key'],
                ucfirst($val['type']),
                $with_default,
                $filter_where,
                $order_by,
            ], $hasone_tpl);
        }

        return [$namespace_data, $hasone_data, $use];
    }



    public function getFileTpl($name)
    {
        $file = implode('/', [$this->tpl_path, $name.'.txt']);
        if (file_exists($file)) {
            return file_get_contents($file);
        }

        return '';
    }



    /**
     * 生成模型文件
     *
     * @param [object] $data 模型的数据信息
     * @return void
     */
    public function createModelFile($data)
    {
        // 新增 仅生成控制器文件
        $setting = $data['setting'] ? json_decode($data['setting'], true) : [];

        $just_controller_file = Arr::get($setting, 'justControllerFile');

        if ($just_controller_file) {
            return;
        }

        $all = $this->allModel(true);

        $names = array_reverse(self::getPath($data, $all));

        $table_name = implode('_', $names);
        $name = ucfirst(array_pop($names));

        $namespace = '';
        if (! empty($names)) {
            $namespace = '\\'.implode('\\', $names);
            $model_file_path = implode('/', [app_path('Models'), implode('/', $names), $name.'.php']);
        } else {
            $model_file_path = implode('/', [app_path('Models'), $name.'.php']);
        }

        $tpl_names = ['category' => 'model2', 'auth' => 'modelAuth'];
        $tpl_name = Arr::get($tpl_names, $data['leixing'], 'model');
        $content = implode("\r", [$this->getFileTpl($tpl_name), $this->getFileTpl('modelContent')]);

        $this->line('开始生成model文件：');

        [$use_namespace, $crud_config, $parse_columns, $useModelArr, $locale_columns,$model_config] = $this->createControllerRelation($data);
        [$use_namespace2, $tpl, $use] = $this->createModelRelation($data, $useModelArr);

        $use_namespace = array_unique(array_merge($use_namespace, $use_namespace2));

        // d($use_namespace,$tpl);

        $columns_function = 'public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = $parse_columns$;
        }
        return $data;
    }';

        if (! empty($parse_columns)) {
            // $parse_columns = preg_replace(['/\$parse_columns\$/'],HelperService::format_var_export($parse_columns,4),$columns_function);
            $parse_columns = preg_replace(['/\$parse_columns\$/'], Dev::export($parse_columns, 3), $columns_function);
        } else {
            $parse_columns = '';
        }

        // 添加唯一字段检测
        if ($data['unique_fields']) {
            $_unique_fields = json_decode($data['unique_fields'], true);
            $model_config[] = 'public $uniqueFields = '.(Dev::export($_unique_fields, 1)).';';
        }

        // 检测文件是否已经存在 存在的话将自定义代码带入
        [$customer_code, $customer_construct, $customer_namespace] = $this->customerCode($model_file_path);

        $replace_arr = [
            '/\$use_namespace\$/' => implode("\r", $use_namespace),
            '/\$use\$/' => $use,
            '/\$namespace\$/' => $namespace,
            '/\$table_name\$/' => $table_name,
            '/\$name\$/' => $name,
            '/\$model_id\$/' => $data['id'],
            '/\$locale_columns\$/' => json_encode($locale_columns),
            '/\$relationship\$/' => implode("\r", $tpl),
            '/\$parse_columns\$/' => $parse_columns,
            '/\$customer_code\$/' => $customer_code,
            '/\$customer_construct\$/' => $customer_construct,
            '/\$customer_namespace\$/' => $customer_namespace,
            // '/\$hasone\$/'=>implode("\r",$hasone_data),
            // '/\$hasmany\$/'=>$hasmany_data
        ];

        // 是否开启uuid
        $setting = $data['setting'] ? json_decode($data['setting'], true) : [];
        if (Arr::get($setting, 'has_uuids')) {
            $uuid_name_default = 'sys_admin_uuid';
            $uuid_name = Arr::get($setting, 'has_uuids_name');
            $uuid_name = $uuid_name ?: $uuid_name_default;
            $content = preg_replace('/\s+\$sys_admin_uuid\$/', "\r".$this->getFileTpl($uuid_name == $uuid_name_default ? 'uuid' : 'uuid_customer'), $content);
            $replace_arr['/\$has_uuid_name\$/'] = $uuid_name;
        } else {
            $replace_arr['/\s+\$sys_admin_uuid\$/'] = "\r";
        }

        if (! empty($model_config)) {
            $replace_arr['/\$crud_config\$/'] = "\r\t".implode("\r\t\t", $model_config)."\r";
        } else {
            $replace_arr['/\s+\$crud_config\$/'] = "\r";
        }

        $search = $replace = [];

        foreach ($replace_arr as $key => $val) {
            $search[] = $key;
            $replace[] = $val;
        }

        $content = preg_replace($search, $replace, $content);
        $this->createFile($model_file_path, $content, true);

        return $model_file_path;
    }



    /**
     * 获取文件的自定义代码信息
     *
     * @param  string  $file_path  文件路径
     * @param  array  $customer_init  初始化自定义代码设置 只在创建文件时检测
     * @return void
     */
    public function customerCode($file_path, $customer_init = [])
    {
        $replace = [
            ['/customer code start(.*)\/\/customer code end/s', '/customer code start(.*)\/\/ customer code end/s'], // 自定义代码
            ['/customer construct start(.*)\/\/customer construct end/s', '/customer construct start(.*)\/\/ customer construct end/s'], // 自定义构造函数代码
            ['/customer namespace start(.*)\/\/customer namespace end/s', '/customer namespace start(.*)\/\/ customer namespace end/s'], // 自定义namespace
            ['/customer property start(.*)\/\/customer property end/s', '/customer property start(.*)\/\/ customer property end/s'], // 自定义属性
        ];
        $ret = [];
        if (file_exists($file_path)) {
            $old_content = file_get_contents($file_path);
            foreach ($replace as $val) {
                $code = '';
                foreach ($val as $item) {
                    if ($code) {
                        continue;
                    }
                    $match = [];
                    preg_match($item, $old_content, $match);
                    if (! empty($match)) {
                        // 已存在的文件该段不做覆盖
                        $code = trim($match[1]);
                    }
                }

                $ret[] = $code;
            }
        } else {
            if (! empty($customer_init)) {
                $ret = $customer_init;
            } else {
                $ret = ['', '', '', ''];
            }
        }

        return $ret;
    }



    public function createFile($file, $content, $force = false)
    {
        // 检测文件夹是否存在
        $pathinfo = pathinfo($file);
        $path = $pathinfo['dirname'];
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
            $this->line($path.' 文件夹创建成功');
        } else {
            $this->line($path.' 文件夹已存在');
        }

        if (file_exists($file) && ! $force) {
            $this->line($file.' 文件已存在');
        } else {
            $fopen = fopen($file, 'w');
            fwrite($fopen, $content);
            fclose($fopen);
            $this->line($file.' 创建成功');
        }
    }



    /**
     * 格式化代码
     * 异步执行url 也需要最小1秒返回时间，一般要生成模型和控制器文件，所以放到一起执行
     *
     * @param  array  $files  文件路径数组
     * @return void
     */
    public function formatFile($files = [])
    {
        // 格式化代码 - windows需要将php路径加入到环境变量中
        $url = url(config('sa.app_prefix').config('sa.app_admin_prefix').'/dev/formatFile');
        // Log::channel('daily')->info('createFile start:', ['url' => $url, 'file' => implode(',', $files)]);
        HelperService::asynUrl($url, ['file_path' => $files]);
        // $command = base_path('vendor/bin/pint ').$file;
        // Process::run($command);
        // Log::channel('daily')->info('createFile end:', ['url' => $url, 'file' => $files]);
    }



    /**
     * 获取模型的的namespace
     *
     * @param [type] $foreign_model
     * @param  array  $exist_names
     * @return void
     */
    public function getNamespace($foreign_model, $exist_names = [])
    {
        $foreign_model_names = array_reverse(self::getPath($foreign_model, $this->allModel()));

        $foreign_model_name = ucfirst(array_pop($foreign_model_names));

        $namespace_prefix = 'App\Models';

        if (! empty($foreign_model_names)) {
            $namespace_prefix .= '\\'.implode('\\', $foreign_model_names);
        }
        $namespace_prefix .= '\\'.$foreign_model_name;

        if ($foreign_model['admin_type'] == 'system') {
            // 系统模型需要获取单独的namespace 前缀
            // 先检测下如果项目中重写了系统的模型 那就直接引入项目中复写的模型
            if (! class_exists($namespace_prefix)) {
                $namespace_prefix = str_replace('App\Models', 'Echoyl\Sa\Models', $namespace_prefix);
            }
        }

        // 如果模型名字一样的话 需要添加一个别名

        if (in_array($foreign_model_name, $exist_names)) {
            if (! empty($foreign_model_names)) {
                $foreign_model_as_name = ucfirst($foreign_model_names[count($foreign_model_names) - 1]).$foreign_model_name;
            } else {
                $foreign_model_as_name = 'Models'.$foreign_model_name;
            }
            $foreign_model_name = $foreign_model_as_name;
            $namespace_prefix .= ' as '.$foreign_model_as_name;
        }

        return ['use '.$namespace_prefix, $foreign_model_name, $namespace_prefix];
    }
}
