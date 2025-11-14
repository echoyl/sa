<?php

namespace Echoyl\Sa\Services\dev\utils\creator;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Creator;
use Illuminate\Support\Arr;

class Posts extends Creator
{
    public $schema;

    public $confJson;

    public function __construct()
    {
        $this->schema = [
            'posts' => '[{"title":"ID","name":"id","type":"int"},{"title":"标题","name":"title","type":"varchar"},{"title":"图片","name":"titlepic","type":"varchar","form_type":"image","setting":{"image_count":"1"}},{"title":"图集","name":"pics","type":"text","form_type":"image","setting":{"image_count":"9"}},{"title":"描述","name":"desc","type":"varchar","form_type":"textarea","empty":[1]},{"title":"作者来源","name":"author","type":"varchar","empty":[1]},{"title":"阅读数","name":"hits","type":"int","form_type":"digit"},{"title":"内容","name":"content","type":"text","form_type":"tinyEditor"},{"title":"其它属性","name":"specs","type":"text","form_type":"json"},{"title":"状态","name":"state","type":"int","form_type":"switch","default":1,"table_menu":true,"setting":{"open":"启用","close":"禁用"}},{"title":"外链","name":"link","type":"varchar","empty":[1]}]',
        ];

        $this->confJson = [
            'posts' => [
                'table_config' => '[{"key":"id","props":[]},{"key":"title","can_search":[1],"props":{"width":"300","copyable":true,"ellipsis":true}},{"key":"titlepic"},{"key":"created_at","sort":[1]},{"key":"displayorder"},{"key":"state","table_menu":[1]},{"key":"option"}]',
                'form_config' => '{"tabs":[{"tab":{"title":"基础信息"},"config":[{"columns":[{"key":"title","required":true},{"key":"created_at","props":{"width":"100%"}}]},{"columns":[{"key":"titlepic"},{"key":["pics"]}]},{"columns":[{"key":"displayorder"},{"key":["state"]}]}]},{"tab":{"title":"详情"},"config":[{"columns":[{"key":"desc"}]},{"columns":[{"key":"content","required":true}]}]},{"tab":{"title":"其它信息"},"config":[{"columns":[{"key":"author"},{"key":"hits"}]},{"id":"rqfxwd86o28","columns":[{"key":["link"],"props":{"tooltip":"设置后列表会跳转该外链"}}]}]}]}',
            ],
        ];
    }

    public function make()
    {
        $title = request('base.title');
        $name = request('base.name');

        $model_to_id = request('base.model_to_id', 0);
        $menu_to_id = request('base.menu_to_id', 0);

        $menu_name = '列表';

        // 如果有自定义的分类id 那么下面不会自动创建分类信息 而是直接关联选中的分类模型
        $customer_category_id = request('base.category_id', 0);
        $appname = DevService::appname();

        // 5 创建菜单
        // 5.1大菜单
        // 添加逻辑 如果选择了 menu_to_id 和 customer_category_id 即选了创建菜单到 和 指定分类 那么 表示直接创建列表 不要再创父级菜单

        if (! $menu_to_id || ! $customer_category_id) {
            $big_menu = [
                'title' => $title,
                'path' => $name,
                'parent_id' => $menu_to_id,
                'status' => 1,
                'state' => 1,
                'type' => $appname,
            ];
            $big_menu_id = $this->checkHasMenu($big_menu);
        } else {
            // 不创建大菜单 直接使用 传入的 菜单id值
            $big_menu_id = $menu_to_id;
            $menu_name = $title;
        }

        // 先创建模型 模型默认放到文件夹下

        // 2.1 先创建posts父级
        $model_parent = [
            'title' => $title,
            'name' => $name,
            'admin_type' => $appname,
            'type' => 0, // 文件夹
            'leixing' => 'normal',
            'parent_id' => $model_to_id,
        ];
        $model_to_id = $this->checkHas($model_parent);

        // 1.列表
        $model = new Model;

        $model_data = [
            'title' => $title,
            'name' => $name,
            'admin_type' => $appname,
            'type' => 1,
            'leixing' => 'normal',
            'parent_id' => $model_to_id,
        ];

        $model_id = $this->checkHas($model_data);
        $ds = new DevService;

        // 创建分类
        $category = $this->category($model_id, $big_menu_id, $model_to_id);
        // 创建省市区的关联
        // 更新字段
        $model->where(['id' => $model_id])->update([
            'columns' => $this->addColumns($this->schema['posts'], $category['columns']),
        ]);

        $model = $model->where(['id' => $model_id])->first();

        // 创建数据表

        $ds->createModelSchema($model);

        // 4.1生成model文件

        $ds->createModelFile($model);

        // 4.2及controller文件
        $ds->createControllerFile($model);

        // 5.2列表
        $form_config = json_decode($this->confJson['posts']['form_config'], true);
        $tabs = Arr::get($form_config, 'tabs', []);
        $tabs0 = json_encode(Arr::get($tabs, 0, []));
        $tabs0 = $this->addColumns($tabs0, $category['menu_form_columns'], 'config');
        Arr::set($tabs, 0, json_decode($tabs0, true));
        $form_config = json_encode(['tabs' => $tabs]);

        $menu = array_merge([
            'title' => $menu_name,
            'path' => $name,
            'parent_id' => $big_menu_id,
            'status' => 1,
            'state' => 1,
            'type' => $appname,
            'page_type' => 'table',
            'open_type' => 'drawer',
            'admin_model_id' => $model_id,
        ], [
            'table_config' => $this->addColumns($this->confJson['posts']['table_config'], $category['menu_columns']),
            'form_config' => $form_config,
        ]);

        $menu_id = $this->checkHasMenu($menu);
        // 5.4生成菜单的配置信息 desc
        // 所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);

    }
}
