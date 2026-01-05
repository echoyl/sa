<?php

namespace Echoyl\Sa\Services\dev\crud;

use Echoyl\Sa\Services\dev\utils\Schema;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

/**
 * @property \Echoyl\Sa\Services\AdminAppService $adminService
 */
class ParseData
{
    public $model_class; // 当前模型class

    // 由于这些参数设置在了控制器，导致无法获取。暂时不用。先只适用于relation处理
    // 之后需要将控制器的部分属性转移至模型
    public $params = [];

    public $adminService;

    public function __construct($class, $params = [])
    {
        $this->model_class = $class;
        $this->params = $params;
        $this->adminService = HelperService::getAdminService();
    }

    public function getParam($name, $default = [])
    {
        return Arr::get($this->params, $name, $default);
    }

    public function getParseColumns()
    {
        $model = new $this->model_class;
        $parse_columns = [];
        if (method_exists($model, 'getParseColumns')) {
            $parse_columns = $model->getParseColumns();
        }

        return $parse_columns;
    }

    public function make(&$data, $in = 'encode', $from = 'detail', $deep = 1)
    {
        // $max_deep = $from == 'list'?1:3;
        $action_type = $this->getParam('action_type', 'list'); // list add edit
        $max_deep = 3;

        $parse_columns = $this->getParseColumns();

        $can_be_null_columns = $this->getParam('can_be_null_columns'); // 这个属性 后面需要设置在model中

        $encode = $in == 'encode' ? true : false;

        if (! is_array($data)) {
            $data = $data->toArray();
        }

        foreach ($parse_columns as $col) {
            $name = $col['name'];
            $type = $col['type'];

            $isset = array_key_exists($name, $data) ? true : false;
            if (! $isset && $from == 'update') {
                // 更新数据时 不写入默认值
                // d($this->parse_columns,$this->can_be_null_columns);
                if (! in_array($name, $can_be_null_columns)) {
                    continue;
                }
            }
            $col['default'] = $col['default'] ?? '';

            $val = $isset ? $data[$name] : $col['default'];
            if (! $isset) {
                $check_category_field = $this->checkCategoryField($name, $col['default']);
                // $val = $check_category_field['array_val'];
                if ($check_category_field['array_val'] && $check_category_field['array_val'] != '__unset') {
                    $data[$name] = $check_category_field['array_val'];
                    $val = $check_category_field['array_val'];
                }
            }
            if ($type == 'model' || $type == 'models') {
                if ($encode) {
                    // 提交数据时 不需要处理 将数据删除
                    $val = '__unset';
                    if ($isset) {
                        unset($data[$name]);
                    }
                } else {
                    if ($deep <= $max_deep && $isset && $val) {
                        $cls = new $col['class'];
                        // d($name,$val,$max_deep);
                        // model类型只支持1级 多级的话 需要更深层次的with 这里暂时不实现了
                        // 思路 需要在生成controller文件的 with配置中 继续读取关联模型的关联
                        // 20240930 更深一层的 parseWiths 暂时取消掉
                        // $this->parseWiths($val,$cls_p_c);
                        $ps = new ParseData($cls);
                        if ($type == 'models') {
                            foreach ($val as $k => $v) {
                                // 1对多不获取withs的内容了
                                $ps->make($v, $in, $from, $deep + 1);
                                $val[$k] = $v;
                            }
                        } else {
                            if (in_array($action_type, ['edit', 'add'])) {
                                $ps->parseWiths($val);
                            }

                            $ps->make($val, $in, $from, $deep + 1);
                        }
                        $data[$name] = $val;
                    }
                }
            } else {
                $config = [
                    'data' => $data, 'col' => $col,
                ];
                $cs = new CrudService($config);
                $data = $cs->make($type, [
                    'encode' => $encode,
                    'isset' => $isset,
                    'from' => $from,
                    'deep' => $deep,
                ]);
            }
        }
        if ($encode) {
            $data = HelperService::filterNotExistColumns($data, $this->model_class);
        } else {
            if (isset($data['originData'])) {
                unset($data['originData']);
            }
        }

    }

    public function checkCategoryField($name, $default = '')
    {
        $category_fields = $this->getParam('category_fields');

        $field = collect($category_fields)->first(function ($q) use ($name) {
            return $q['field_name'] == $name;
        });
        $orval = request($name); // 原始请求值
        $rval = $lval = $array_val = $default;

        if ($field) {
            $rval = request($field['request_name'], $default); // 预设请求值
            if ($rval) {
                if (is_array($rval)) {
                    $len = count($rval);
                    $lval = $rval[$len - 1];
                } else {
                    $lval = $rval;
                }
                if (! $orval) {
                    // 未传数据 自动读取映射字段
                    $array_val = is_numeric($rval) ? [$rval] : (is_array($rval) ? $rval : json_decode($rval, true));
                }
            }
        }

        return [
            'search_val' => $orval ?: $rval, // 处理过后搜索值
            'last_val' => $lval, // 分类的id值 数字类型
            'array_val' => $array_val,
        ];
    }

    /**
     * 全局筛选数据条件
     *
     * @param [type] $m
     * @param  bool  $origin_model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function globalDataSearch($m, $origin_model = false)
    {
        if (! $origin_model) {
            return $m;
        }
        if (property_exists($origin_model, 'admin_data_search')) {
            $m = $this->adminService->dataSearch($m, $origin_model);
        }

        return $m;
    }

    public function parseWiths(&$data)
    {
        $parse_columns = $this->getParseColumns();

        $table_menu = [];

        $action_type = $this->getParam('action_type', 'list'); // list add edit

        $set_this = $this->getParam('set_this');

        foreach ($parse_columns as $with) {
            if (isset($with['with'])) {
                $name = $with['name'].'s';
                if (isset($with['class'])) {
                    $_m = new $with['class'];
                    $table_name = (new $with['class'])->getTable();
                    $has_displayorder_columns = Schema::hasColumn($table_name, 'displayorder');
                    $_m = $this->globalDataSearch($_m, $_m);
                    $no_category = Arr::get($with, 'no_category', false); // 不是分类模型
                    if ($with['type'] == 'select_columns') {
                        // 这里只获取一层数据因为一般的模型都没有继承category模型 没有format方法
                        if ($with['columns']) {
                            $_m = $_m->select($with['columns']);
                        }
                        if ($has_displayorder_columns) {
                            $data[$name] = $_m->orderBy('displayorder', 'desc');
                        }
                        $data[$name] = $_m->get()->toArray();
                    } else {
                        if ($no_category) {
                            if (isset($with['columns'])) {
                                $_m = $_m->select($with['columns']);
                            }
                            $filter_empty = false;
                            if (isset($with['where'])) {
                                $with_where = [];
                                foreach ($with['where'] as $ww) {
                                    $use_this = strpos($ww[2], 'this.') !== false; // this. 从控制器setThis函数返回的数据中读取条件
                                    $use_item = strpos($ww[2], 'item.') !== false; // item.代表从当前数据读取条件
                                    $data_key = str_replace(['this.', 'item.'], '', $ww[2]);

                                    $filter_val = $ww[2];
                                    $filter_data = $use_this ? $set_this : ($use_item ? $data : false);

                                    if ($filter_data !== false) {
                                        $filter_val = $filter_data[$data_key] ?? false;
                                        if ($filter_val === false) {
                                            // 如果设置了读取数据中的条件，但数据没有这个字段，则不返回数据了
                                            $filter_empty = true;
                                            break;
                                        }
                                    }

                                    if (in_array($ww[1], ['in', 'between'])) {
                                        $_m = HelperService::searchWhereBetweenIn($_m, [$ww[0]], $filter_val, $ww[1] == 'in' ? 'whereIn' : 'whereBetween');
                                    } else {
                                        $with_where[] = [$ww[0], $ww[1], $filter_val];
                                    }

                                }
                                $_m = $_m->where($with_where);
                            }
                            if ($filter_empty) {
                                $data[$name] = [];

                                continue;
                            }
                            if ($has_displayorder_columns) {
                                $data[$name] = $_m->orderBy('displayorder', 'desc');
                            }
                            $data[$name] = $_m->get()->toArray();
                        } else {
                            if (isset($with['post_all']) && $with['post_all'] && in_array($action_type, ['edit', 'add'])) {
                                // 设置post_all 时 不再读取cid筛选数据
                                $cid = 0;
                            } else {
                                // 检测是否有cid字段的参数传入
                                $check_category_field = $this->checkCategoryField($with['name'], $with['cid'] ?? 0);
                                $cid = $check_category_field['last_val'];
                            }

                            if (isset($with['fields'])) {
                                $data[$name] = $_m->formatHasTop($cid, $with['fields']);
                            } else {
                                $data[$name] = $_m->formatHasTop($cid);
                            }
                        }
                    }
                } elseif (isset($with['data'])) {
                    $data[$name] = $with['data'];
                }
            }

            // 检测是否有table_menu设置
            if (isset($with['table_menu']) && ! isset($data['table_menu'])) {
                // 已经默认 在select 中 columns 加入了label和value字段 不再处理数据
                // $table_menu[$with['name']] = $with['data']??$data[$name];
                // 这里处理下数据 之后将移除
                $table_menu_data = $with['data'] ?? $data[$name];
                $table_menu[$with['name']] = collect($table_menu_data)->map(function ($v) {
                    if (isset($v['id'])) {
                        $v['value'] = $v['id'];
                    }
                    if (isset($v['title'])) {
                        $v['label'] = $v['title'];
                    }

                    return $v;
                });
            }
        }

        if (! empty($table_menu)) {
            $data['table_menu'] = $table_menu;
        }

    }
}
