<?php
namespace Echoyl\Sa\Services\dev\utils;

class Creator
{
    public static $default_category_columns = '[{"title":"id","name":"id","type":"int"},{"title":"名称","name":"title","type":"varchar"},{"title":"描述","name":"desc","type":"varchar","form_type":"textarea"},{"title":"icon","name":"icon","type":"varchar"},{"title":"父级Id","name":"parent_id","type":"int","desc":null},{"title":"图片","name":"titlepic","type":"varchar","form_type":"image","form_data":1},{"title":"状态","name":"state","type":"int","form_type":"switch","form_data":"禁用,启用","default":"1"}]';

    public static $menu_posts = [    
        'form_config'=>'[{"columns":[{"key":"id","readonly":true}]},{"columns":[{"key":"title","required":true}]},{"columns":[{"key":"category_id","required":true},{"key":"created_at"}]},{"columns":[{"key":"author"},{"key":"hits"}]},{"columns":[{"key":"titlepic"}]},{"columns":[{"key":"pics"}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"content","required":true}]},{"columns":[{"key":"specs","type":"jsonForm"}]},{"columns":[{"key":"state"},{"key":"displayorder"}]}]',
        'table_config'=>'[{"key":"id","props":[]},{"key":"title","can_search":[1],"props":{"width":"300","copyable":true,"ellipsis":true}},{"key":"category_id"},{"key":"titlepic"},{"key":"created_at","sort":[1]},{"key":"displayorder"},{"key":"state","table_menu":[1]},{"key":"option"}]',
    ];

    public static function postsColumns($type = 'cascaders')
    {
        $default_post_columns = '[{"title":"ID","name":"id","type":"int"},{"title":"标题","name":"title","type":"varchar"},{"title":"分类","name":"category_id","type":"varchar","form_type":"'.$type.'"},{"title":"_分类","name":"_category_id","type":"varchar","length":500},{"title":"图片","name":"titlepic","type":"varchar","form_type":"image","form_data":1},{"title":"图集","name":"pics","type":"text","form_type":"image","form_data":9},{"title":"描述","name":"desc","type":"varchar","form_type":"textarea"},{"title":"作者来源","name":"author","type":"varchar"},{"title":"阅读数","name":"hits","type":"int","form_type":"digit"},{"title":"内容","name":"content","type":"text","form_type":"tinyEditor"},{"title":"其它属性","name":"specs","type":"text","form_type":"json"},{"title":"状态","name":"state","type":"int","form_type":"switch","default":1,"form_data":"禁用,启用","table_menu":true}]';
        return $default_post_columns;
    }

    public static function menuCategory($level = 2)
    {
        return [
            'form_config'=>'[{"columns":[{"key":"id","readonly":true}]},{"columns":[{"key":"title"},{"key":"displayorder"}]},{"columns":[{"key":"desc"},{"key":"icon"}]},{"columns":[{"key":"titlepic"}]},{"columns":[{"key":"state"}]}]',
            'table_config'=>'[{"key":"id"},{"key":"title"},{"key":"titlepic"},{"key":"displayorder"},{"key":"state"},{"key":"coption"}]',
            'other_config'=>'{"level":'.$level.'}'
        ];
    }
}