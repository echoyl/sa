<?php

namespace Echoyl\Sa\Services\dev\Concerns;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\admin\LocaleService;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\ExportColumn;
use Echoyl\Sa\Services\dev\utils\FormItem;
use Echoyl\Sa\Services\dev\utils\TableColumn;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

/**
 * DevService 拆分：BuildsDevTrees
 */
trait BuildsDevTrees
{


    public function getModelsTree($admin_type = '', $all_selectable = false)
    {
        if ($admin_type) {
            return $this->getModelsTreeData($admin_type, $all_selectable);
        } else {
            // 获取全部的话 将系统模型归类到一起
            $system = $this->getModelsTreeData(['system'], $all_selectable);
            $plugin = $this->getModelsTreeData(['plugin'], $all_selectable);
            $app = $this->getModelsTreeData([self::appname()], $all_selectable);

            return array_merge($app, [
                [
                    'id' => 0,
                    'title' => '系统相关',
                    'value' => 0,
                    'isLeaf' => false,
                    'selectable' => false,
                    'children' => $system,
                ],
                [
                    'id' => -1,
                    'title' => '插件',
                    'value' => -1,
                    'isLeaf' => false,
                    'selectable' => false,
                    'children' => $plugin,
                ],
            ]);
        }
    }



    /**
     * 获取模型返回树形格式
     *
     * @param  string  $admin_type
     * @param  bool  $all_selectable
     * @return array
     */
    public function getModelsTreeData($admin_type = '', $all_selectable = false)
    {
        $model = new Model;
        $types = $admin_type ?: Utils::packageTypeArr();
        $data = HelperService::getChildFromData($model->whereIn('admin_type', $types)->get()->toArray(), function ($item) use ($all_selectable) {

            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'value' => $item['id'],
                'isLeaf' => $item['type'] ? true : false,
                'selectable' => $item['type'] || $all_selectable ? true : false,
            ];
        }, [['type', 'asc'], ['id', 'asc']]);

        return $data;
    }



    public function getMenusTree($admin_type = '', $all_selectable = false)
    {
        $model = new Menu;
        $types = $admin_type ?: Utils::packageTypeArr();

        $data = HelperService::getChildFromData($model->whereIn('type', $types)->get()->toArray(), function ($item) {
            $item['title'] = LocaleService::get('title', $item);

            return [
                'id' => $item['id'],
                'label' => $item['title'],
                'title' => $item['title'],
                'value' => $item['id'],
                'isLeaf' => true,
                'selectable' => true,
            ];
        }, [['state', 'desc'], ['displayorder', 'desc'], ['id', 'desc']]);

        return $data;
    }



    public function getModelsFolderTree()
    {
        $types = Utils::packageTypes();
        $datas = [];
        foreach ($types as $key => $type) {
            $model = (new Model)->where('admin_type', $type)->where(['type' => 0]);
            $data = HelperService::getChildFromData($model->get()->toArray(), function ($item) {
                return [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'value' => $item['id'],
                    'isLeaf' => $item['type'] ? true : false,
                    // 'selectable'=>$item['type']?true:false,
                ];
            }, [['displayorder', 'desc'], ['type', 'asc'], ['id', 'desc']]);
            if ($type['value'] == DevService::appname()) {
                $datas = array_merge($data, $datas);
            } else {
                $datas[] = [
                    'id' => $key - 2,
                    'title' => $type['label'],
                    'value' => $key - 2,
                    'isLeaf' => false,
                    'selectable' => false,
                    'children' => $data,
                ];
            }
        }

        return $datas;
    }



    /**
     * 生成model的某个字段的json 前端配置
     *
     * @param [所选模型] $model
     * @param [每个group配置] $key
     * @return array
     */
    public function modelColumn2JsonForm($model, $keys)
    {
        $data = [];

        $menus = $this->allMenu();
        $models = $this->allModel();
        foreach ($keys as $row) {
            $formItem = new FormItem($row, $model, $menus, $models);
            if ($formItem->data) {
                $d = $formItem->data;
            } else {
                continue;
            }
            $data[] = $d;
        }
        // 获取非hidden元素的长度
        $key_len = collect($data)->filter(function ($item) {
            return ! Arr::get($item, 'formItemProps.hidden');
        })->count();
        // $key_len = count($data);
        if ($key_len < 1) {
            return $data;
        }
        foreach ($data as $key => $val) {
            $hidden = Arr::get($val, 'formItemProps.hidden');
            if (is_string($val) || $hidden) {
                continue;
            }
            $span = floor(24 / $key_len);
            if (! isset($val['colProps'])) {
                $data[$key]['colProps'] = ['span' => $span];
            }
        }

        return $data;
    }



    /**
     * table 列表字段的解析
     *
     * @param [所选的模型] $model
     * @param [列配置] $col
     * @return void
     */
    public function modelColumn2JsonTable($model, $col, $setting = [])
    {
        $menus = $this->allMenu();
        $models = $this->allModel();
        $tableColumn = new TableColumn($col, $model, $menus, $models);
        $data = $tableColumn->data;
        $x = Arr::get($setting, 'table.scroll.x');
        if (! isset($data['width']) && $x) {
            // 设置了横向滚动的情况下 如果没有设置width 则默认100
            $data['width'] = 100;
        }

        return $data;
    }



    public function modelColumn2Export($model)
    {
        $setting = $model['setting'];
        if (! $setting) {
            return;
        }

        $setting = json_decode($model['setting'], true);
        $exports = Arr::get($setting, 'export');
        if (! $exports) {
            return;
        }

        $menus = $this->allMenu();
        $models = $this->allModel();
        foreach ($exports as $key => $export) {
            // 多模板需要执行
            $columns = HelperService::getFromObject($export, ['config', 'head', 'columns']);
            if (! $columns) {
                continue;
            }
            foreach ($columns as $k => $col) {
                $exportColumn = new ExportColumn($col, $model, $menus, $models);
                $col['title'] = $exportColumn->data['title'];
                $col['width'] = $col['width'] ?? 15;
                $columns[$k] = $col;
            }
            // d($columns);
            Arr::set($export, 'config.head.columns', array_values($columns));
            $exports[$key] = $export;
        }

        $setting['export'] = $exports;
        $_model = new Model;
        $_model->where(['id' => $model['id']])->update(['setting' => json_encode($setting)]);

    }
}
