<?php
namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Http\Controllers\admin\dev\MenuController;
use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;

class Creator
{
    public static $default_category_columns = '[{"title":"id","name":"id","type":"int"},{"title":"名称","name":"title","type":"varchar"},{"title":"描述","name":"desc","type":"varchar","form_type":"textarea"},{"title":"icon","name":"icon","type":"varchar"},{"title":"父级Id","name":"parent_id","type":"int","desc":null},{"title":"图片","name":"titlepic","type":"varchar","form_type":"image","form_data":1},{"title":"状态","name":"state","type":"int","form_type":"switch","form_data":"禁用,启用","default":"1"}]';

    public static $menu_posts = [    
        'form_config'=>'{"tabs":[{"title":"基础信息","config":[{"columns":[{"key":"id","readonly":true}]},{"columns":[{"key":"title","required":true}]},{"columns":[{"key":"category_id","required":true},{"key":"created_at"}]},{"columns":[{"key":"author"},{"key":"hits"}]},{"columns":[{"key":"titlepic"}]},{"columns":[{"key":"pics"}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"content","required":true}]},{"columns":[{"key":"specs","type":"jsonForm"}]},{"columns":[{"key":"state"},{"key":"displayorder"}]}]}]}',
        'table_config'=>'[{"key":"id","props":[]},{"key":"title","can_search":[1],"props":{"width":"300","copyable":true,"ellipsis":true}},{"key":"category_id"},{"key":"titlepic"},{"key":"created_at","sort":[1]},{"key":"displayorder"},{"key":"state","table_menu":[1]},{"key":"option"}]',
    ];

    public static function postsColumns($type = 'cascaders')
    {
        $default_post_columns = '[{"title":"ID","name":"id","type":"int"},{"title":"标题","name":"title","type":"varchar"},{"title":"分类","name":"category_id","type":"varchar","form_type":"select","setting":{"label":"title","value":"id"}},{"title":"_分类","name":"_category_id","type":"varchar","length":500},{"title":"图片","name":"titlepic","type":"varchar","form_type":"image","form_data":1,"setting":{"image_count":"1"}},{"title":"图集","name":"pics","type":"text","form_type":"image","form_data":9,"setting":{"image_count":"9"}},{"title":"描述","name":"desc","type":"varchar","form_type":"textarea"},{"title":"作者来源","name":"author","type":"varchar"},{"title":"阅读数","name":"hits","type":"int","form_type":"digit"},{"title":"内容","name":"content","type":"text","form_type":"tinyEditor"},{"title":"其它属性","name":"specs","type":"text","form_type":"json"},{"title":"状态","name":"state","type":"int","form_type":"switch","default":1,"form_data":"禁用,启用","table_menu":true,"setting":{"open":"启用","close":"禁用"}}]';
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


    public function postsContent()
    {
        $title = request('title');
        $name = request('name');

        $model_to_id = request('model_to_id',0);
        $menu_to_id = request('menu_to_id',0);
        $appname = DevService::appname();
        //先创建模型
        //1.列表
        $model = new Model();
        $model_to = $model->where(['id'=>$model_to_id])->first();
        $model_to_id = $model_to?$model_to['id']:0; 

        $category_level = request('category_level',1);
        $category_type = request('category_type','single');
        $type = '';
        $relation_type = 'cascaders';
        if($category_level > 1)
        {
            $type = $category_type == 'single'?'cascader':'cascaders';
            $relation_type = $category_type == 'single'?'cascader':'cascaders';
        }else
        {
            $type = $category_type == 'single'?'select':'selects';
            $relation_type = $category_type == 'single'?'one':'cascaders';
        }

        $model_data = [
            'title'=>$title,
            'name'=>$name,
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'normal',
            'columns'=>Creator::postsColumns($type),
            'parent_id'=>$model_to_id
        ];
        
        $model_id = $this->checkHas($model_data);
        //2.分类表
        //2.1 先创建posts父级
        $model_category_f = [
            'title'=>$title,
            'name'=>$name,
            'admin_type'=>$appname,
            'type'=>0,//文件夹
            'leixing'=>'normal',
            'parent_id'=>$model_to_id
        ];
        $model_f_id = $this->checkHas($model_category_f);
        //2.2 category
        $model_category_data = [
            'title'=>'分类',
            'name'=>'category',
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'category',
            'columns'=>Creator::$default_category_columns,
            'parent_id'=>$model_f_id
        ];
        $model_category_id = $this->checkHas($model_category_data);
        $model = $model->where(['id'=>$model_id])->first();
        $category_model = $model->where(['id'=>$model_category_id])->first();
        //创建数据表
        $ds = new DevService;
        $ds->createModelSchema($model);
        $ds->createModelSchema($category_model);
        //3.关联模型
        $relation = [
            'model_id'=>$model_id,
            'title'=>'分类',
            'name'=>'category',
            'type'=>$relation_type,
            'foreign_model_id'=>$model_category_id,
            'foreign_key'=>'id',
            'local_key'=>'category_id',
            'created_at'=>now(),
            'updated_at'=>now(),
            'is_with'=>1
        ];
        $has_relation = (new Relation())->where(['model_id'=>$model_id,'foreign_model_id'=>$model_category_id])->first();
        if(!$has_relation)
        {
            (new Relation())->insert($relation);
        }else
        {
            (new Relation())->where(['id'=>$has_relation['id']])->update($relation);
        }
        //4.1生成model文件
        
        $ds->createModelFile($model);
        $ds->createModelFile($category_model);
        //4.2及controller文件
        $ds->createControllerFile($model);
        $ds->createControllerFile($category_model);

        //5 创建菜单
        //5.1大菜单
        $big_menu = [
            'title'=>$title,
            'path'=>$name,
            'parent_id'=>$menu_to_id,
            'status'=>1,
            'icon'=>'table',
            'state'=>1,
            'type'=>$appname
        ];
        $big_menu_id = $this->checkHasMenu($big_menu);
        //5.3分类菜单
        $category_menu = array_merge([
            'title'=>'分类',
            'path'=>'category',
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'category',
            'open_type'=>'drawer',
            'admin_model_id'=>$model_category_id,
        ],Creator::menuCategory($category_level));
        //5.2列表
        $menu = array_merge([
            'title'=>'列表',
            'path'=>$name,
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'table',
            'open_type'=>'drawer',
            'admin_model_id'=>$model_id
        ],Creator::$menu_posts);
        $menu_category_id = $this->checkHasMenu($category_menu);
        $menu_id = $this->checkHasMenu($menu);
        
        //5.4生成菜单的配置信息 desc
        //所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);
        $this->updateMenuDesc($menu_category_id);
        return;
    }

    public function permContent()
    {
        $user_columns = '[{"title":"id","name":"id","type":"int"},{"title":"用户名","name":"username","type":"vachar"},{"title":"描述","name":"desc","type":"vachar","form_type":"textarea"},{"title":"角色","name":"roleid","type":"int","form_type":"select","form_data":"title,id"},{"name":"password","title":"密码","type":"vachar","form_type":"password"},{"title":"权限","name":"perms2","type":"text"},{"name":"avatar","title":"头像","type":"vachar","form_type":"image","form_data":1},{"title":"状态","name":"state","type":"int","form_type":"switch","form_data":"禁用,启用","table_menu":true},{"name":"latest_login_at","title":"最后登录时间","type":"datetime"}]';

        $role_config = [
            'form_config'=>'[{"columns":[{"key":"title"}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"state"},{"key":"id"}]},{"columns":[{"key":"perms2","type":"permGroup"}]}]',
            'table_config'=>'[{"key":"id"},{"key":"title","can_search":[1]},{"key":"desc"},{"key":["created_at"]},{"key":"state","table_menu":[1]},{"key":["option"]}]',
        ];
        $user_config = [
            'form_config'=>'[{"columns":[{"key":"id","readonly":true}]},{"columns":[{"key":"username"}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"password","placeholder":"请输入密码，为空时表示不修改密码"}]},{"columns":[{"key":"state"}]}]',
            'table_config'=>'[{"key":"id"},{"key":"username"},{"key":["role","title"]},{"key":"desc"},{"key":["created_at"]},{"key":["latest_login_at"]},{"key":"state","left_menu":[],"table_menu":[1]},{"key":["option"]},{"key":["roleid"],"can_search":[1],"hide_in_table":[1]},{"key":["id"],"type":"userPerm"}]',
        ];

        $log_config = [
            'form_config'=>'[{"columns":[{"key":"id","readonly":true}]},{"columns":[{"key":"user","label":"username","readonly":true}]},{"columns":[{"key":"url","readonly":true}]},{"columns":[{"key":"request","readonly":true,"type":"jsonCode"}]},{"columns":[{"key":"ip","readonly":true}]},{"columns":[{"key":"type","readonly":true},{"key":"created_at","title":"请求时间","readonly":true}]}]',
            'table_config'=>'[{"key":"id"},{"key":["user","username"]},{"key":"url"},{"key":"ip"},{"key":"type"},{"key":["created_at"],"title":"操作时间"},{"key":["created_at"],"title":"操作时间","can_search":[1],"hide_in_table":[1],"type":"dateRange"},{"key":["option"]}]'
        ];

        $title = request('title');
        $name = request('name');

        $model_to_id = request('model_to_id',0);
        $menu_to_id = request('menu_to_id',0);
        $appname = DevService::appname();
        //先创建模型
        //1.列表
        $model = new Model();
        $model_to = $model->where(['id'=>$model_to_id])->first();
        $model_to_id = $model_to?$model_to['id']:0; 

        $relation_type = 'one';
        $model_data = [
            'title'=>'用户信息',
            'name'=>'user',
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'auth',
            'columns'=>$user_columns,
            'parent_id'=>$model_to_id
        ];
        
        $model_id = $this->checkHas($model_data);
        //2.角色表
        // //2.1 先创建user父级
        // $model_category_f = [
        //     'title'=>$title,
        //     'name'=>$name,
        //     'admin_type'=>$appname,
        //     'type'=>0,//文件夹
        //     'leixing'=>'normal',
        //     'parent_id'=>$model_to_id
        // ];
        // $model_f_id = $this->checkHas($model_category_f);
        //2.2 category
        $model_category_id = 61;//角色模型id值
        $model_log_id = 62;
        $model = $model->where(['id'=>$model_id])->first();
        $category_model = $model->where(['id'=>$model_category_id])->first();
        //创建数据表
        $ds = new DevService;
        $ds->createModelSchema($model);
        //$ds->createModelSchema($category_model);
        //3.关联模型
        $relation = [
            'model_id'=>$model_id,
            'title'=>'角色',
            'name'=>'role',
            'type'=>$relation_type,
            'foreign_model_id'=>$model_category_id,
            'foreign_key'=>'id',
            'local_key'=>'roleid',
            'created_at'=>now(),
            'updated_at'=>now(),
            'is_with'=>1
        ];
        $has_relation = (new Relation())->where(['model_id'=>$model_id,'foreign_model_id'=>$model_category_id])->first();
        if(!$has_relation)
        {
            (new Relation())->insert($relation);
        }else
        {
            (new Relation())->where(['id'=>$has_relation['id']])->update($relation);
        }
        //4.1生成model文件
        
        $ds->createModelFile($model);
        //$ds->createModelFile($category_model);
        //4.2及controller文件
        $ds->createControllerFile($model);
        //$ds->createControllerFile($category_model);

        //5 创建菜单
        //5.1大菜单
        $big_menu = [
            'title'=>$title,
            'path'=>$name,
            'parent_id'=>$menu_to_id,
            'status'=>1,
            'icon'=>'lock',
            'state'=>1,
            'type'=>$appname
        ];
        $big_menu_id = $this->checkHasMenu($big_menu);
        //5.3分类菜单
        $category_menu = array_merge([
            'title'=>'角色',
            'path'=>'role',
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'table',
            'open_type'=>'drawer',
            'admin_model_id'=>$model_category_id,
        ],$role_config);
        //5.2列表
        $menu = array_merge([
            'title'=>'用户',
            'path'=>'user',
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'table',
            'open_type'=>'drawer',
            'admin_model_id'=>$model_id
        ],$user_config);
        $log_menu = array_merge([
            'title'=>'操作记录',
            'path'=>'log',
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'table',
            'open_type'=>'drawer',
            'admin_model_id'=>$model_log_id,
            'addable'=>0,
            'editable'=>0
        ],$log_config);
        $menu_category_id = $this->checkHasMenu($category_menu);
        $menu_id = $this->checkHasMenu($menu);
        $menu_log_id = $this->checkHasMenu($log_menu);
        //5.4生成菜单的配置信息 desc
        //所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);
        $this->updateMenuDesc($menu_category_id);
        $this->updateMenuDesc($menu_log_id);
        return;
    }

    public function updateMenuDesc($menu_id)
    {
        $ds = new DevService;
        $ds->allMenu(true);
        $ds->allModel(true);
        $mc = new MenuController(new Menu());
        $mc->tableConfig($menu_id);
        $mc->formConfig($menu_id);
        $mc->otherConfig($menu_id);
        return;
    }

    public function checkHas($model_data)
    {
        $model = new Model();
        $model_has = $model->where(['name'=>$model_data['name'],'parent_id'=>$model_data['parent_id'],'type'=>$model_data['type'],'admin_type'=>$model_data['admin_type']])->first();
        if(!$model_has)
        {
            $model_id = $model->insertGetId($model_data);
        }else
        {
            //更新
            $model->where(['id'=>$model_has['id']])->update($model_data);
            $model_id = $model_has['id'];
        }
        return $model_id;
    }
    public function checkHasMenu($model_data)
    {
        $model = new Menu();
        $model_has = $model->where(['path'=>$model_data['path'],'parent_id'=>$model_data['parent_id'],'type'=>$model_data['type']])->first();
        if(!$model_has)
        {
            $model_id = $model->insertGetId($model_data);
        }else
        {
            //更新
            $model->where(['id'=>$model_has['id']])->update($model_data);
            $model_id = $model_has['id'];
        }
        return $model_id;
    }
}