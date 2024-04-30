<?php

namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\model\Relation;
use Illuminate\Support\Arr;


class ExportColumn
{
    var $data = false;
    var $config;
    var $schema;
    var $relation;
    var $readonly = false;
    var $menus;
    var $form_type;
    var $models;
    var $key;
    var $model;
    var $props; //config中的props; 属性集中到这个字段中

    public function __construct($config, $model, $menus, $models)
    {
        $this->config = $config;
        $this->menus = $menus;
        $this->models = $models;
        $this->model = $model;

        $props = [];
        $key = $dataIndex = $config['key'] ?? '';

        if (is_array($key)) {
            if (count($key) == 1) {
                $dataIndex = $key[0];
            }
            $key = $key[0];
        }


        $this->key = $key;


        $columns = json_decode($model['columns'], true);
        $schema = Utils::arrGet($columns, 'name', $key);
        $this->schema = $schema;

        //如果本地字段 需要转化下驼峰格式？ 如果本地没有字段 通过关联的name获取相关关联
        $relation = Utils::arrGet($model['relations'], $schema ? 'local_key' : 'name', $schema ? Utils::uncamelize($key) : $key);
        // if($key == 'hexiaoUser')
        // {
        //     d($relation,Utils::uncamelize($key));
        // }
        $this->relation = $relation;


        $p_title = $props['title'] ?? '';
        $title = $config['ctitle'] ?? '';
        $title = $p_title ?: $title;

        $relation_title = '';
        if ($relation) {
            $_relation_title = [$relation['title']];
            if (is_array($dataIndex)) {
                $foreign_model_columns = json_decode($relation['foreign_model']['columns'], true);
                $field = Utils::arrGet($foreign_model_columns, 'name', $dataIndex[1]);
                if ($field && $field['title']) {
                    $_relation_title[] = $field['title'];
                }
            }
            
            $relation_title = implode(' - ', $_relation_title);
        }
        //d($relation_title,$title,$title ?: ($schema ? $schema['title'] : ($relation_title ?: Utils::$title_arr[$key] ?? '')));
        $this->data = [
            'dataIndex' => $dataIndex, 
            'title' => $title ?: ($schema ? $schema['title'] : ($relation_title ?: Utils::$title_arr[$key] ?? ''))
        ];


        return;
    }
}
