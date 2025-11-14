<?php

namespace Echoyl\Sa\Services\admin;

use Echoyl\Sa\Models\locale\Category;
use Echoyl\Sa\Models\locale\Config;
use Echoyl\Sa\Services\AdminService;
use Illuminate\Support\Facades\Schema;

class LocaleService
{
    public static function getModel()
    {
        if (Schema::hasTable('locale_category')) {
            return new Category;
        }

        return false;
    }

    public static function enable()
    {
        if (self::getModel()) {
            return true;
        } else {
            return false;
        }
    }

    public static function list()
    {
        $model = self::getModel();
        if (! $model) {
            return [];
        }
        $languages = $model->select(['id', 'title', 'name'])->where(['state' => 1])->get()->toArray();

        return $languages;
    }

    public static function getMenu($data, $prefix = false)
    {
        $ret = [];
        $_prefix = $prefix ? $prefix : ['menu'];
        foreach ($data as $val) {
            $now_prefix = array_merge($_prefix, [$val['path']]);
            $key = implode('.', $now_prefix);
            $ret[$key] = $val['name'];
            if (! empty($val['routes'])) {
                $ret = array_merge($ret, self::getMenu($val['routes'], $now_prefix));
            }
        }

        return $ret;
    }

    public static function getSetting($user = false)
    {
        // 读取菜单的多语言，当开启tab时需要设置 不管是否开启了多语言设置
        $menu_data = AdminService::menuData($user);
        $user_menu = self::getMenu($menu_data);

        if (! self::enable()) {
            return [
                ['name' => 'zh-CN', 'title' => '中文简体', 'configs' => $user_menu],
            ];
        }

        $all_configs = (new Config)->get()->toArray();
        $languages = self::list();

        foreach ($languages as $lang) {
            $ret[] = [
                'name' => $lang['name'],
                'title' => $lang['title'],
                'configs' => array_merge($user_menu, self::formatData($all_configs, $lang['id'])),
            ];
        }

        return $ret;
    }

    public static function formatData($all_data, $category_id, $pid = 0, $pre = [])
    {
        $list = collect($all_data);
        $list = $list->where('category_id', $category_id)->where('parent_id', $pid)->sortBy([['displayorder', 'desc'], ['id', 'asc']])->toArray();
        $ret = [];
        foreach ($list as $val) {
            $child_pre = array_merge($pre, [$val['key']]);
            $children = self::formatData($all_data, $category_id, $val['id'], $child_pre);
            // 拼接name 前缀
            $_key = implode('.', $child_pre);
            $ret[$_key] = $val['message'];
            if (! empty($children)) {
                $ret = array_merge($ret, $children);
            }
        }

        return $ret;
    }

    public static function getClassLocaleColumns($class)
    {
        static $columns = [];
        if (empty($columns)) {
            $model = new $class;
            $columns = $model->locale_columns;
        }

        return $columns;
    }

    /**
     * 获取单个数据的某个语言的信息
     *
     * @param [type] $class
     * @param [type] $data
     * @param [type] $locale
     * @return void
     */
    public static function getData($class, $data, $locale)
    {
        $locale_columns = static::getClassLocaleColumns($class);

        foreach ($locale_columns as $column) {
            $name = implode('_', [$column, $locale]);
            if (isset($data[$name]) && $data[$name]) {
                // 仅当字段存在且非空的时候才读取该语言字段信息
                $data[$column] = $data[$name];
            }
        }

        return $data;
    }

    /**
     * 获取列表数据的多语言信息
     *
     * @param [type] $class
     * @param [type] $list
     * @param [type] $locale
     * @return array
     */
    public static function getListData($class, $list, $locale)
    {
        foreach ($list as $key => $val) {
            $list[$key] = static::getData($class, $val, $locale);
        }

        return $list;
    }

    public static function search($query, $item, $model, $index = 0)
    {
        $name = $item[0];

        if (! $model || ! $model->locale_columns || ! in_array($name, $model->locale_columns)) {
            return $index == 0 ? $query->where([$item]) : $query->orWhere([$item]);
        }
        $locales = self::list();

        $fn = function ($q) use ($locales, $item, $name) {
            $q->where([$item]);
            foreach ($locales as $lang) {
                $new_item = $item;
                $new_item[0] = implode('_', [$name, $lang['name']]);
                $q->orWhere([$new_item]);
            }
        };

        return $index == 0 ? $query->where($fn) : $query->orWhere($fn);
    }
}
