<?php

namespace Echoyl\Sa\Services\admin;

use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class LocaleService
{
    /**
     * 获取系统开启的多语言类型
     *
     * @return array
     */
    public static function list()
    {
        static $languages = [];
        if (! empty($languages)) {
            return $languages;
        }
        $languages = config('sa.locales', []);

        return $languages;
    }

    public static function getSetting($user = false)
    {
        $languages = self::list();

        foreach ($languages as $lang) {
            $ret[] = [
                'name' => $lang['name'],
                'title' => $lang['title'],
                'configs' => static::getLocaleTranslations($lang['name']),
            ];
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

    /**
     * 渲染多语言字段是否需要转化
     *
     * @param [type] $locale_columns 多语言字段列表
     * @param [type] $parse_columns 需要解析的字段列表
     * @return array
     */
    public static function parseColumns($locale_columns, $parse_columns)
    {
        if (empty($locale_columns)) {
            return $parse_columns;
        }
        $languages = static::list();
        // 读取parse_columns中的字段类型，进行转换
        foreach ($locale_columns as $lcolumn) {
            $in_parse_columns = collect($parse_columns)->first(fn ($value) => $value['name'] == $lcolumn);
            if ($in_parse_columns) {
                // 需要转化
                foreach ($languages as $lang) {
                    $parse_columns[] = array_merge($in_parse_columns, ['name' => implode('_', [$lcolumn, $lang['name']])]);
                }
            }
        }

        return $parse_columns;
    }

    /**
     * 将多语言数据合并到一个字段存储在表中
     *
     * @param [type] $locale_columns 多语言字段列表
     * @param [type] $data 存储数据
     * @return array
     */
    public static function encode($locale_columns, $data)
    {
        if (empty($locale_columns)) {
            return $data;
        }
        $languages = static::list();
        foreach ($locale_columns as $lcolumn) {
            $locale_data = [];
            $data_key = implode('_', [$lcolumn, 'locale']);
            foreach ($languages as $lang) {
                $key = implode('_', [$lcolumn, $lang['name']]);
                $lang_data = Arr::get($data, $key);
                if ($lang_data !== null) {
                    $locale_data[$key] = $lang_data;
                }
            }
            $data[$data_key] = json_encode($locale_data);

        }

        return $data;
    }

    /**
     * 将多语言数据从表中读取出来
     *
     * @param [type] $locale_columns 多语言字段列表
     * @param [type] $data 数据
     * @return array
     */
    public static function decode($locale_columns, $data)
    {
        if (empty($locale_columns)) {
            return $data;
        }
        foreach ($locale_columns as $lcolumn) {
            $data_key = implode('_', [$lcolumn, 'locale']);
            $locale_data = HelperService::json_validate(Arr::get($data, $data_key));
            if ($locale_data) {
                $data = array_merge($data, $locale_data);
            }
            Arr::forget($data, $data_key);
        }

        return $data;
    }

    public static function getLang($name = 'Locale')
    {
        $language = request()->header($name, env('APP_LOCALE', 'zh-CN')); // 默认简中

        return $language;
    }

    public static function setLang($name = 'Locale')
    {
        App::setLocale(static::getLang($name));
    }

    public static function get($key, $data)
    {
        $default = Arr::get($data, $key);
        $data_key = implode('_', [$key, 'locale']);
        $locale_data = HelperService::json_validate(Arr::get($data, $data_key));
        if (! $locale_data) {
            return $default;
        }
        $language = static::getLang();
        $locale_key = implode('_', [$key, $language]);

        return Arr::get($locale_data, $locale_key, $default);
    }

    /**
     * 将lang目录下的多语言文件配置返回dot格式
     *
     * @param [type] $locale
     */
    public static function getLocaleTranslations($locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        $loader = app('translation.loader'); // 通常是 Illuminate\Translation\FileLoader
        $files = app(\Illuminate\Filesystem\Filesystem::class);

        $result = [];

        // 1) resources/lang/{locale} 下的 PHP 文件（每个文件为一个 group）
        $langPath = base_path("lang/{$locale}");
        if (is_dir($langPath)) {
            foreach ($files->files($langPath) as $file) {
                $group = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                // load($locale, $group, $namespace = null)
                $result[$group] = $loader->load($locale, $group);
            }
        }

        // 2) resources/lang/{locale}.json (JSON 翻译)
        $jsonPath = resource_path("lang/{$locale}.json");
        if (file_exists($jsonPath)) {
            $result = array_merge($result, json_decode(file_get_contents($jsonPath), true) ?? []);
        }

        return Arr::dot($result);
    }
}
