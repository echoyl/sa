<?php

namespace Echoyl\Sa\Services\dev;

use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

class ModelService
{
    /**
     * 提交前处理模型的columns，包括检测是否有platform_id字段，排序等
     *
     * @param [type] $data 提交的数据信息 提交信息可能只包含部分字段，所有需要从模型信息中获取额外信息
     * @param [type] $item 模型信息
     * @return void
     */
    public function moreColumns($data, $item)
    {
        $setting = Arr::get($data, 'setting', Arr::get($item, 'setting', []));
        $setting = HelperService::getJson($setting);
        $columns = Arr::get($data, 'columns', []);
        if (Arr::get($setting, 'with_platform_id')) {
            $platform_service = config('sa.platformService');
            if ($platform_service && $platform_service::$platform_key) {
                $name = $platform_service::$platform_key;
                // 如果columns中没有$platform_key字段，则添加
                if (! in_array($name, array_column($data['columns'], 'name'))) {
                    $columns[] = ['name' => $name, 'type' => 'int', 'form_type' => 'select', 'title' => '平台ID'];
                }
            }
        }

        $columns = array_values(collect($columns)->sortBy('name')->toArray());

        return $columns;
    }

    /**
     * 提交前处理模型额外的relations，
     *
     * @param [type] $model 模型数据
     * @return void
     */
    public function moreRelations($model)
    {
        $setting = Arr::get($model, 'setting', []);
        $setting = HelperService::getJson($setting);
        if (Arr::get($setting, 'with_platform_id')) {
            $platform_service = config('sa.platformService');
            $platform_class = config('sa.platformClass');
            if ($platform_service && $platform_class && $platform_service::$platform_key) {
                $name = $platform_service::$platform_key;
                $platform = new $platform_class;
                // 检测关联是否存在
                $relation = [
                    'model_id' => $model['id'],
                    'foreign_model_id' => $platform->model_id,
                    'type' => 'one',
                ];
                $has = Relation::where($relation)->first();
                $data = array_merge($relation, [
                    'title' => '平台',
                    'name' => Arr::get(explode('_', $name), '0', 'platform'),
                    'foreign_key' => 'id',
                    'local_key' => $name,
                ]);
                if ($has) {
                    // 更新关联
                    Relation::where('id', $has->id)->update($data);
                } else {
                    // 添加关联
                    $data['created_at'] = now();
                    Relation::insert($data);
                }
            }
        }

    }
}
