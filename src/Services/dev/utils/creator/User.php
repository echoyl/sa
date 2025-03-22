<?php
namespace Echoyl\Sa\Services\dev\utils\creator;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Creator;

class User extends Creator
{
    var $schema;
    var $confJson;
    public function __construct()
    {
        $this->schema = [
            'user'=>'[{"title":"头像","name":"avatar","type":"text","form_type":"image"},{"title":"描述","name":"desc","type":"vachar","form_type":"textarea"},{"title":"性别","name":"gender","type":"enum","form_type":"radioButton","setting":{"json":[{"id":"male","title":"男","icon":"ManOutlined","color":"blue"},{"id":"female","title":"女","icon":"WomanOutlined","color":"red"},{"id":"unknown","title":"未知性别","icon":"QuestionCircleOutlined","color":"gray"}]},"default":"female","table_menu":true},{"title":"id","name":"id","type":"int"},{"title":"手机号码","name":"mobile","type":"vachar","setting":[]},{"title":"密码","name":"password","type":"varchar","form_type":"password","table_menu":false},{"title":"状态","name":"state","type":"int","form_type":"switch","default":1,"setting":{"open":"启用","close":"禁用"},"table_menu":true},{"title":"名称","name":"username","type":"vachar"},{"title":"余额","name":"yue","type":"int","form_type":"price"}]',
        ];

        $this->confJson = [
            'user'=>[
                'table_config'=>'[{"key":"option","uid":"7XkWHJRj2d"},{"uid":"MRfX0gZSBH","key":["username"]},{"uid":"cGeeHZ0Nsq","can_search":[1],"hide_in_table":[1],"props":{"title":"检索","dataIndex":"keyword","tip":{"placeholder":"请输入会员姓名或手机号码"}}},{"key":"avatar","uid":"CYipTV052O"},{"uid":"wA0horVFlR","key":["mobile"]},{"uid":"Qtekc4u3ID","key":["desc"],"props":{"ellipsis":true}},{"uid":"l2mhI2uCRI","key":["gender"],"type":"dropdownAction","props":{"fieldProps":{"fieldNames":"id,title","showType":"string"},"items":[{"uid":"9yawcf3judx","domtype":"tag","bordered":false}]},"can_search":[],"table_menu":[1]},{"uid":"lH6uxgslBd","key":["state"],"table_menu":[]},{"key":"displayorder","uid":"32x4uvVYrJ"}]',
                'form_config'=>'{"tabs":[{"tab":{"title":"基础信息"},"config":[{"columns":[{"uid":"us1WD3HQYf","key":["username"],"required":true},{"uid":"I9xpwQzc7I","key":["mobile"],"required":true}],"uid":"uxWZWpo6SS"},{"columns":[{"uid":"ejfivtFX6H","key":["password"],"props":{"tip":{"placeholder":"如果需要修改请填写新密码否则为空"}}},{"key":"yue","uid":"50hXgHm0Z3"}],"uid":"mio1NaKgFs"},{"columns":[{"uid":"O0gEHnE36m","key":["desc"]},{"uid":"L5ndVg0uag","key":["avatar"]}],"uid":"JpOajItX3p"},{"columns":[{"uid":"xd9ZGhsZ52","key":["gender"]},{"uid":"gs4UNE9my2","key":["state"]}],"uid":"HZ3pzwEVpE"}],"uid":"dDvoRUDUpc"}]}'
            ]
        ];
    }

    public function make()
    {
        $title = request('base.title');
        $name = request('base.name');

        $model_to_id = request('base.model_to_id',0);
        $menu_to_id = request('base.menu_to_id',0);

        $menu_name = '列表';

        $appname = DevService::appname();

        //5 创建菜单
        //5.1大菜单
        //添加逻辑 如果选择了 menu_to_id 即选了创建菜单到 那么 表示直接创建列表 不要再创父级菜单

        if(!$menu_to_id)
        {
            $big_menu = [
                'title'=>$title,
                'path'=>$name,
                'parent_id'=>$menu_to_id,
                'status'=>1,
                'state'=>1,
                'type'=>$appname,
                'icon'=>'UserOutlined'
            ];
            $big_menu_id = $this->checkHasMenu($big_menu);
        }else
        {
            //不创建大菜单 直接使用 传入的 菜单id值
            $big_menu_id = $menu_to_id;
            $menu_name = $title;
        }

        
        //先创建模型 模型默认放到文件夹下

        //2.1 先创建模型上级
        $model_parent = [
            'title'=>$title,
            'name'=>$name,
            'admin_type'=>$appname,
            'type'=>0,//文件夹
            'leixing'=>'normal',
            'parent_id'=>$model_to_id
        ];
        $model_to_id = $this->checkHas($model_parent);

        //1.列表
        $model = new Model();

        $model_data = [
            'title'=>$title,
            'name'=>$name,
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'normal',
            'parent_id'=>$model_to_id,
            'columns'=>$this->schema['user'],
            'search_columns'=>'[{"name":"keyword","type":"like","columns":["username","mobile"]},{"name":"gender","type":"=","columns":["gender"]}]'
        ];
        
        $model_id = $this->checkHas($model_data);
        $ds = new DevService;

        $model = $model->where(['id'=>$model_id])->first();
        
        //创建数据表
        
        $ds->createModelSchema($model);

        //4.1生成model文件
        
        $ds->createModelFile($model);
        
        //4.2及controller文件
        $ds->createControllerFile($model);
        
        
        
        //5.2列表
        $form_config = $this->confJson['user']['form_config'];

        $menu = array_merge([
            'title'=>$menu_name,
            'path'=>$name,
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'table',
            'open_type'=>'modal',
            'admin_model_id'=>$model_id,
            'icon'=>'UserOutlined'
        ],[
            'table_config'=>$this->confJson['user']['table_config'],
            'form_config'=>$form_config
        ]);
        
        $menu_id = $this->checkHasMenu($menu);
        //5.4生成菜单的配置信息 desc
        //所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);
        
        return;
    }
}