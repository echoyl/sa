<?php

namespace Echoyl\Sa\Services\dev\Concerns;

use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\utils\Dev;
use Echoyl\Sa\Services\dev\utils\Utils;
use Illuminate\Support\Arr;

/**
 * DevService 拆分：GeneratesControllerFiles
 */
trait GeneratesControllerFiles
{


    public function createControllerFile($data, $customer_init = [])
    {
        // 新增 如果模型仅设置仅模型文件 这里不生成控制器文件了
        $setting = $data['setting'] ? json_decode($data['setting'], true) : [];

        $just_model_file = Arr::get($setting, 'justModelFile');
        $just_controller_file = Arr::get($setting, 'justControllerFile');

        $open_drag_sort = Arr::get($setting, 'openDragSort'); // 是否开启拖拽排序

        if ($just_model_file) {
            return;
        }

        $all = $this->allModel(true);

        $names = array_reverse(self::getPath($data, $all));

        $name = ucfirst(array_pop($names));

        $namespace = '';
        $use = '';
        $use_traits = $namespace_data = [];
        $traits = [
            'category' => ['TraitsCategory', 'use Echoyl\Sa\Traits\Category as TraitsCategory;'],
            'dragsort' => ['DragSort', 'use Echoyl\Sa\Traits\DragSort;'],
        ];

        if (! empty($names)) {
            $namespace = '\\'.implode('\\', $names);
            $model_file_path = implode('/', [app_path('Http/Controllers/admin'), implode('/', $names), $name.'Controller.php']);
        } else {
            $model_file_path = implode('/', [app_path('Http/Controllers/admin'), $name.'Controller.php']);
        }
        $tpl_name = 'controller';
        if ($data['leixing'] == 'category') {
            $tpl_name = 'controller2';
            $namespace_data[] = $traits['category'][1];
            $use_traits[] = $traits['category'][0];
        } elseif ($just_controller_file) {
            $tpl_name = 'justController';
        }

        $content = file_get_contents(implode('/', [$this->tpl_path, $tpl_name.'.txt']));

        // 读取控制器主模型的关联信息
        [$use_namespace, $crud_config, $parse_columns] = $this->createControllerRelation($data);

        $this->line('开始生成controller文件：');
        // HelperService::format_var_export($parse_columns,3);

        // 生成基础配置 with_column，search_config，with_sum,with_count
        // $use_namespace = $parse_columns = [];
        if ($open_drag_sort) {
            $use_traits[] = $traits['dragsort'][0];
            $namespace_data[] = $traits['dragsort'][1];
        }
        if (! empty($use_traits)) {
            $use = 'use '.implode(',', $use_traits).';';
        }

        // 检测文件是否已经存在 存在的话将自定义代码带入
        [$customer_code, $customer_construct, $customer_namespace, $customer_property] = $this->customerCode($model_file_path, $customer_init);
        $namespace_data[] = "use App\Models{$namespace}\\{$name};";
        $customer_namespace = Dev::sortNamespace($namespace_data, $customer_namespace);
        $replace_arr = [
            '/\$namespace\$/' => $namespace,
            '/\$modelname\$/' => $name,
            '/\$name\$/' => $name,
            '/\$crud_config\$/' => implode("\r\t\t", $crud_config),
            // '/\$use_traits\$[\r\n\t]+/'=> $use,
            // '/\$parse_columns\$/'=>HelperService::format_var_export($parse_columns,3),
            '/\$customer_code\$/' => $customer_code,
            '/\$customer_construct\$/' => $customer_construct,
            '/\$customer_property\$/' => $customer_property,
            '/\$customer_namespace\$/' => $customer_namespace,
            '/\$app_name\$/' => config('app.name'),
            // '/\$hasone\$/'=>implode("\r",$hasone_data),
            // '/\$hasmany\$/'=>$hasmany_data
        ];
        if ($use) {
            $replace_arr['/\$use_traits\$/'] = $use;
        } else {
            $replace_arr['/\$use_traits\$[\r\n\t]+/'] = $use;
        }

        $search = $replace = [];

        foreach ($replace_arr as $key => $val) {
            $search[] = $key;
            $replace[] = $val;
        }

        $content = preg_replace($search, $replace, $content);
        $this->createFile($model_file_path, $content, true);

        // 控制器文件的新增/变更会影响 class_exists 的判断结果，需要重建路由定义缓存
        self::flushRouteCache();

        return $model_file_path;
    }



    public function createControllerRelation($model)
    {
        $model_id = $model['id'];
        $namespace_data = [];
        $parse_columns = [];
        $crud_config = [];
        $with_sum = $with_count = $with_columns = $search_config = $can_be_null_columns = $locale_columns = $model_config = []; // 配置处理好后 直接返回代码了

        $relations = (new Relation)->where(['model_id' => $model_id])->with(['foreignModel.relations'])->get()->toArray();

        // 循环检测列中支持搜索的字段
        $columns = $model['search_columns'] ? json_decode($model['search_columns'], true) : [];
        foreach ($columns as $val) {
            $search_config[] = ['name' => $val['name'], 'columns' => $val['columns'], 'where_type' => $val['type']];
        }

        $has_model = [ucfirst($model['name'])];
        $all_models = [$model['name'] => $model['name']];
        $all_relations = [];

        $with_trees = [];
        $useModelArr = []; // 使用过的模型数据
        if (! empty($relations)) {
            foreach ($relations as $key => $val) {

                if ($val['can_search']) {
                    // 这里的关联名称需要转化小驼峰->下划线模式 //这里加入withdefault 的空逻辑
                    $default_with_value = '';
                    if ($val['with_default']) {
                        $with_default = json_decode($val['with_default'], true);
                        foreach ($with_default as $wd) {
                            if ($wd) {
                                // 获取第一个有值的数据
                                $default_with_value = $wd;
                                break;
                            }
                        }
                    }
                    $search_config[] = [
                        'name' => $val['name'],
                        'columns' => explode(',', $val['search_columns']),
                        'type' => 'has',
                        'default' => $default_with_value,
                    ];
                }

                $foreign_model = $val['foreign_model'];

                if (! $foreign_model) {
                    d($val);
                }

                if (isset($useModelArr[$foreign_model['id']])) {
                    $f_model_name = $useModelArr[$foreign_model['id']];
                } else {
                    [$namespace, $f_model_name] = $this->getNamespace($foreign_model, $has_model);
                    $useModelArr[$foreign_model['id']] = $f_model_name;
                    $namespace_data[] = $namespace.';';
                }

                $has_model[] = $f_model_name;
                $all_models[$val['local_key']] = $f_model_name;
                if (! isset($all_relations[$val['local_key']])) {
                    $all_relations[$val['local_key']] = $val;
                }

                if ($val['type'] == 'many') {
                    // hasMany 检测是否需要with_count 和 with_sum 的字段
                    if ($val['with_count']) {
                        $with_count[] = $val['name'];
                        // 计算数据数量时检测是否有链接
                        // $menu = (new Menu())->where(['admin_model_id'=>$val['foreign_model_id']])->first();
                        // if($menu)
                        // {
                        //     $path = self::getPath($menu,(new Menu)->get(),'path');
                        //     $parse_columns[] = [
                        //         'name'=>$val['name'].'_count',
                        //         'type'=>'link',
                        //         'path'=>'/'.implode('/',array_reverse($path)),
                        //         'uri'=>[[$val['foreign_key'],$val['local_key']]]
                        //     ];
                        // }
                    }
                    if ($val['with_sum']) {
                        $_with_sum = explode(',', $val['with_sum']);
                        foreach ($_with_sum as $with_sum_name) {
                            $with_sum[] = [$val['name'], $with_sum_name];
                        }
                    }
                    $parse_column = [
                        'name' => Utils::uncamelize($val['name']),
                        'type' => 'models',
                        'class' => '@php'.$f_model_name.'::class@endphp',
                        'foreign_key' => $val['foreign_key'],
                        // 'setting'=>isset($val['setting']) && $val['setting']?json_decode($val['setting'],true):[]
                    ];
                    if (isset($val['setting']) && $val['setting']) {
                        $parse_column['setting'] = json_decode($val['setting'], true);
                    }
                    $parse_columns[] = $parse_column;
                }

                if ($val['is_with']) {
                    if (in_array($val['type'], ['one', 'many'])) {
                        // 关联模型的字段
                        // 检测是否填写了需要关联模型的字段
                        if ($val['select_columns']) {
                            // 解析字段
                            $fields = collect(explode(',', $val['select_columns']))->map(function ($v) {
                                return explode('-', $v);
                            })->sort(function ($a, $b) {
                                return count($a) < count($b);
                            })->values()->toArray();
                            $with_tree = Utils::toTree($fields, $val['name']);
                        } else {
                            $with_tree = [];
                        }
                        // 将需要处理的with数据先存入数组中
                        $with_trees[$val['name']] = $with_tree;
                    }

                    if ($val['type'] == 'one') {
                        // 1对1时候 还需要关联对接 转换关联数据
                        // name需要驼峰转下划线
                        $parse_column = [
                            'name' => Utils::uncamelize($val['name']),
                            'type' => 'model',
                            'class' => '@php'.$f_model_name.'::class@endphp',
                            'foreign_key' => $val['foreign_key'],
                        ];
                        if (isset($val['setting']) && $val['setting']) {
                            $parse_column['setting'] = json_decode($val['setting'], true);
                        }
                        $parse_columns[] = $parse_column;
                    }
                }

                if ($val['is_with_in_page']) {
                    // 1对1时候 还需要关联对接 转换关联数据
                    $parse_columns[] = [
                        'name' => $val['name'],
                        'type' => 'select_columns',
                        'class' => '@php'.$f_model_name.'::class@endphp',
                        'with' => true,
                        'columns' => $val['in_page_select_columns'] ? explode(',', $val['in_page_select_columns']) : [],
                    ];
                }
            }
            [$with_columns] = Utils::withTree($with_trees, 2, $model_id);
        }

        if (! empty($with_columns)) {
            $crud_config[] = '$this->with_column = '.$with_columns.';';
            // $crud_config[] = str_replace($with_columns_search,$with_columns_replace,'$this->with_column = '.(Dev::export($with_columns,2)).';');
        }
        if (! empty($search_config)) {
            $crud_config[] = '$this->search_config = '.(Dev::export($search_config, 2)).';';
        }
        if (! empty($with_sum)) {
            // d(json_encode($with_sum));
            $crud_config[] = '$this->with_sum = '.(Dev::export($with_sum, 2)).';';
        }
        if (! empty($with_count)) {
            $crud_config[] = '$this->with_count = '.(json_encode($with_count)).';';
        }

        // 生成parse_columns 数组
        if ($model['columns']) {
            $columns = json_decode($model['columns'], true);
            foreach ($columns as $column) {
                // 字段是否可为空
                $empty = Arr::get($column, 'empty', 0);
                $name = $column['name'];
                $setting = $column['setting'] ?? [];
                if ($empty) {
                    $can_be_null_columns[] = $name;
                }
                // 字段是否多语言
                $is_locale = Arr::get($setting, 'locale');
                if ($is_locale) {
                    $locale_columns[] = $name;
                }
                if (! isset($column['form_type']) || in_array($column['form_type'], ['textarea', 'dateTime', 'datetime', 'digit'])) {
                    // 不存在type 或者一些不需要parse的字段 直接过滤
                    continue;
                }
                // $form_data = $column['form_data']??'';

                $form_type = $column['form_type'];
                $default_value = $column['default'] ?? '';

                $int_value_form_types = ['price']; // 需要直接转化为int类型
                $int_value_form_types_need_relation = ['select', 'search_select', 'radioButton', 'radioSegmented', 'searchSelect'];

                if (in_array($form_type, $int_value_form_types) || (in_array($form_type, $int_value_form_types_need_relation) && isset($all_models[$name]))) {
                    if ($default_value) {
                        // 默认值支持非数字格式,如果是数字格式就转化为int
                        $default_value = is_numeric($default_value) ? intval($default_value) : $default_value;
                    } else {
                        $default_value = 0;
                    }
                }

                // ['name' => 'shop_id', 'type' => 'search_select', 'default' => '0','data_name'=>'shop','label'=>'name'],
                $d = [
                    'name' => $name,
                    'type' => $form_type,
                    'default' => $default_value,
                ];

                $table_menu = isset($column['table_menu']) && $column['table_menu'];
                $label = $setting['label'] ?? '';
                $value = $setting['value'] ?? '';
                $children = $setting['children'] ?? '';

                $relation = $all_relations[$name] ?? false; // 当前字段有的关联

                $_columns = [$label ?: 'title', $value ?: 'id'];
                if ($relation) {
                    if ($relation['filter']) {
                        $d['where'] = json_decode($relation['filter'], true);
                    }

                    if ($relation['in_page_select_columns']) {
                        $_columns = explode(',', $relation['in_page_select_columns']);
                    } elseif ($relation['select_columns']) {
                        // 关联的字段 呈现 name- 格式,需要过滤掉这种格式
                        $_select_columns = collect(explode(',', $relation['select_columns']))->filter(fn ($v) => strpos($v, '-') === false)->values()->toArray();
                        $_columns = array_values(array_unique(array_merge($_columns, $_select_columns)));
                    }
                }

                // 需要设置class的字段类型
                $need_set_class_form_types = ['select', 'selects', 'radioButton', 'radioSegmented', 'checkbox', 'searchSelects', 'cascaders', 'cascader', 'modalSelects'];
                if (in_array($form_type, $need_set_class_form_types) && isset($all_models[$name])) {
                    $d['class'] = '@php'.$all_models[$name].'::class@endphp';
                }
                // 需要设置data_name的字段类型
                $need_set_data_name_form_types = ['search_select', 'searchSelects', 'searchSelect', 'modalSelects'];
                if (in_array($form_type, $need_set_data_name_form_types) && isset($all_relations[$name])) {
                    $d['data_name'] = Utils::uncamelize($all_relations[$name]['name']);
                }

                if (in_array($form_type, ['select', 'selects', 'radioButton', 'radioSegmented', 'checkbox'])) {
                    if (isset($all_models[$name])) {
                        // 如果是select 且设置了tabel menu，那么form_data设置的label 和value
                        // 新增数据筛选配置
                        // $filter = $clo
                        if ($table_menu && $label && $value) {
                            // $d['columns'] = ["{$label} as label","{$value} as value",$label,$value];
                            $_columns = array_merge($_columns, ["{$label} as label", "{$value} as value"]);
                        }
                        $d['no_category'] = true;
                        $d['columns'] = array_values($_columns);
                    } else {
                        if (isset($setting['json']) && $setting['json']) {
                            // d(json_decode($column['form_data'],true));
                            if (is_string($setting['json'])) {
                                $json_data = json_decode($setting['json'], true);
                            } else {
                                $json_data = $setting['json'];
                            }
                            $d['data'] = collect($json_data)->map(function ($v) {
                                if (isset($v['id']) && is_numeric($v['id'])) {
                                    $v['id'] = intval($v['id']);
                                }
                                if (isset($v['value']) && is_numeric($v['value'])) {
                                    $v['value'] = intval($v['value']);
                                }

                                return $v;
                            })->toArray();
                            // d($d['data']);
                        } else {
                            $d['data'] = []; // 这里如果是空应该是数组格式 而不是字符串'[]'
                        }
                    }
                    $d['with'] = true;
                    // select的类型会可以检测是否需要table_menu 设置
                    if ($table_menu) {
                        $d['table_menu'] = true;
                    }
                }
                if ($form_type == 'switch') {
                    // switch 也支持 table_menu
                    // 这里写入默认的close和open名称
                    $valueEnum = [
                        ['label' => $setting['close'] ?? Utils::$label_map['close'], 'value' => 0],
                        ['label' => $setting['open'] ?? Utils::$label_map['open'], 'value' => 1],
                    ];

                    $d['default'] = $default_value ? 1 : 0;
                    if ($table_menu) {

                        $d['table_menu'] = true;
                    }
                    $d['with'] = true;
                    $d['data'] = $valueEnum;
                }
                if (($form_type == 'search_select' || $form_type == 'searchSelect' || $form_type == 'searchSelects') && isset($all_relations[$name])) {
                    if ($label) {
                        $d['label'] = $label;
                    }
                    if ($value) {
                        $d['value'] = $value;
                    }
                }
                if ($form_type == 'cascaders' || $form_type == 'cascader') {
                    if (isset($all_models[$name])) {
                        $d['with'] = true;
                    }

                    if ($label && $value && $children) {
                        $d['fields'] = ['id' => $value, 'title' => $label, 'children' => $children];
                    }
                }
                // 省市区选择器
                if ($form_type == 'pca') {
                    $d['default'] = '__unset';
                    $d['level'] = $setting['pca_level'] ?? 3; // 默认设置为3层
                    $d['topCode'] = Arr::get($setting, 'pca_topCode', '');
                }
                // modalSelect
                if ($form_type == 'modalSelect' || $form_type == 'modalSelects') {
                    if ($relation) {
                        $d['value'] = $relation['foreign_key'];
                    }
                }
                // 将setting记录
                if (! empty($setting)) {
                    // 只记录需要记录的key lat lng
                    $set_setting = collect($setting)->only(['lat', 'lng'])->toArray();
                    if (! empty($set_setting)) {
                        $d['setting'] = $set_setting;
                    }
                }
                // 富文本
                $parse_columns[] = $d;
            }
        }

        if (! empty($can_be_null_columns)) {
            $crud_config[] = '$this->can_be_null_columns = '.(json_encode($can_be_null_columns)).';';
            $model_config[] = 'public $can_be_null_columns = '.(json_encode($can_be_null_columns)).';';
        }

        return [$namespace_data, $crud_config, $parse_columns, $useModelArr, $locale_columns, $model_config];
    }
}
