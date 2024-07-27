<?php
namespace Echoyl\Sa\Services\dev\utils\creator;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Creator;

class Perm extends Creator
{
    var $schema;
    var $confJson;
    public function __construct()
    {
        $this->schema = [
            'user'=>[
                'columns'=>'[{"title":"id","name":"id","type":"int"},{"title":"用户名","name":"username","type":"vachar"},{"title":"描述","name":"desc","type":"vachar","form_type":"textarea","empty":[1]},{"title":"角色","name":"roleid","type":"int","form_type":"select","setting":{"label":"title","value":"id"}},{"name":"password","title":"密码","type":"vachar","form_type":"password"},{"title":"权限","name":"perms2","type":"text"},{"name":"avatar","title":"头像","type":"vachar","form_type":"image","setting":{"image_count":"1"}},{"title":"状态","name":"state","type":"int","form_type":"switch","table_menu":true,"setting":{"open":"启用","close":"禁用"}},{"name":"latest_login_at","title":"最后登录时间","type":"datetime"},{"title":"手机号码","name":"mobile","type":"varchar","length":"50"}]',
                'search_columns'=>'[{"name":"username","type":"like","columns":["username","mobile"]},{"name":"roleid","type":"=","columns":["roleid"]}]'
            ]
        ];

        $this->confJson = [
            'user'=>[
                'form_config'=>'{"tabs":[{"tab":{"title":"基础信息"},"config":[{"columns":[{"key":"username","required":true},{"key":["password"],"type":"password","props":{"tip":{"placeholder":"不填写不修改密码"},"rules":{"data":[{"min":6,"message":"密码最小长度6"}]}}}]},{"columns":[{"key":["roleid"],"readonly":false,"required":true}]},{"columns":[{"key":["perms2"],"type":"permGroup","props":{"dependencyOn":{"type":"show","condition":[{"name":["roleid"],"exp":"{{true}}"}]}}}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"state"}]}]}]}',
                'table_config'=>'[{"key":"id"},{"key":"username","can_search":[1]},{"key":["role","title"]},{"key":"desc"},{"key":["created_at"]},{"key":["latest_login_at"]},{"key":"state","left_menu":[],"table_menu":[1]},{"key":["option"]},{"key":["roleid"],"can_search":[1],"hide_in_table":[1]}]',
            ],
            'role'=>[
                'form_config'=>'{"tabs":[{"tab":{"title":"基础信息"},"config":[{"columns":[{"key":"title","required":true}]},{"columns":[{"key":["perms2"],"type":"permGroup"}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"state"},{"key":"id"},{"key":["sync_user"]}]}]}]}',
                'table_config'=>'[{"key":"id"},{"key":"title","can_search":[1]},{"key":"desc"},{"key":["created_at"]},{"key":"state","table_menu":[1]},{"key":["option"]}]',
            ],
            'log'=>[
                'form_config'=>'{"tabs":[{"tab":{"title":"基础信息"},"config":[{"columns":[{"key":"user","label":"username","readonly":true}]},{"columns":[{"key":"url","readonly":true}]},{"columns":[{"key":"request","readonly":true,"type":"jsonCode"}]},{"columns":[{"key":"ip","readonly":true}]},{"columns":[{"key":"type","readonly":true},{"key":"created_at","title":"请求时间","readonly":true}]}]}]}',
                'table_config'=>'[{"key":"id"},{"key":["user","username"],"props":{"width":"150"}},{"key":"url","props":{"width":"450"}},{"key":"ip","props":{"width":"180"}},{"key":"type"},{"key":["created_at"],"title":"操作时间"},{"key":["created_at"],"title":"操作时间","can_search":[1],"hide_in_table":[1],"type":"dateRange"},{"key":["option"]}]'
            ]
        ];
    }

    

    public function make()
    {
        $title = request('base.title');
        $name = request('base.name');

        $model_to_id = request('base.model_to_id',0);
        $menu_to_id = request('base.menu_to_id',0);


        $role_config = $this->confJson['role'];
        $user_config = $this->confJson['user'];
        $log_config = $this->confJson['log'];

        $appname = DevService::appname();
        //先创建模型
        //1.列表
        $model = new Model();
        $model_to = $model->where(['id'=>$model_to_id])->first();
        $model_to_id = $model_to?$model_to['id']:0; 

        $relation_type = 'one';
        $model_data = array_merge([
            'title'=>'后台用户',
            'name'=>$name,
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'auth',
            'parent_id'=>$model_to_id
        ],$this->schema['user']);
        
        $model_id = $this->checkHas($model_data);
        //2.2 category
        $model_category_id = 61;//角色模型id值
        $model_log_id = 62;
        $model = $model->where(['id'=>$model_id])->first();
        //创建数据表
        $ds = new DevService;
        $ds->createModelSchema($model);
        //3.关联模型
        //3.1关联角色模型
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

        //3.2关联登录记录模型
        $login_model_id = 303;
        $relation2 = [
            'model_id'=>$model_id,
            'title'=>'最后登录记录',
            'name'=>'log',
            'type'=>'one',
            'foreign_model_id'=>$login_model_id,
            'foreign_key'=>'tokenable_id',
            'local_key'=>'id',
            'created_at'=>now(),
            'updated_at'=>now(),
            'is_with'=>1,
            'select_columns'=>'id,last_used_at,name,tokenable_id',
            'filter'=>'[["name","=","admin"]]',
            'order_by'=>'[["last_used_at","desc"]]'
        ];
        $has_relation = (new Relation())->where(['model_id'=>$model_id,'foreign_model_id'=>$login_model_id])->first();
        if(!$has_relation)
        {
            (new Relation())->insert($relation2);
        }else
        {
            (new Relation())->where(['id'=>$has_relation['id']])->update($relation2);
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
            'open_type'=>'modal',
            'admin_model_id'=>$model_category_id,
        ],$role_config);
        //5.2列表
        $menu = array_merge([
            'title'=>'用户',
            'path'=>$name,
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'table',
            'open_type'=>'modal',
            'admin_model_id'=>$model_id
        ],$user_config);
        $log_menu = array_merge([
            'title'=>'日志',
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
}