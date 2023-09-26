<?php
namespace Echoyl\Sa\Services\dev\utils\creator;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Creator;

class Posts extends Creator
{
    var $schema;
    var $confJson;
    public function __construct()
    {
        $this->schema = [
            'posts'=>'[{"title":"ID","name":"id","type":"int"},{"title":"标题","name":"title","type":"varchar"},{"title":":category_title","name":":category_name","type":"varchar","form_type":":category_id","setting":{"label":"title","value":"id"}},{"title":"_分类","name":":_category_name","type":"varchar","length":500},{"title":"图片","name":"titlepic","type":"varchar","form_type":"image","setting":{"image_count":"1"}},{"title":"图集","name":"pics","type":"text","form_type":"image","setting":{"image_count":"9"}},{"title":"描述","name":"desc","type":"varchar","form_type":"textarea"},{"title":"作者来源","name":"author","type":"varchar"},{"title":"阅读数","name":"hits","type":"int","form_type":"digit"},{"title":"内容","name":"content","type":"text","form_type":"tinyEditor"},{"title":"其它属性","name":"specs","type":"text","form_type":"json"},{"title":"状态","name":"state","type":"int","form_type":"switch","default":1,"table_menu":true,"setting":{"open":"启用","close":"禁用"}},{"title":"外链","name":"link","type":"varchar"}]',
            'category'=>'[{"title":"id","name":"id","type":"int"},{"title":"名称","name":"title","type":"varchar"},{"title":"描述","name":"desc","type":"varchar","form_type":"textarea"},{"title":"icon","name":"icon","type":"varchar"},{"title":"父级Id","name":"parent_id","type":"int"},{"title":"图片","name":"titlepic","type":"varchar","form_type":"image","setting":{"image_count":"1"}},{"title":"状态","name":"state","type":"int","form_type":"switch","default":"1","setting":{"open":"启用","close":"禁用"}}]'
        ];

        $this->confJson = [
            'posts'=>[
                'table_config'=>'[{"key":"id","props":[]},{"key":"title","can_search":[1],"props":{"width":"300","copyable":true,"ellipsis":true}},{"key":":category_name"},{"key":"titlepic"},{"key":"created_at","sort":[1]},{"key":"displayorder"},{"key":"state","table_menu":[1]},{"key":"option"}]',
                'form_config'=>'{"tabs":[{"tab":[{"title":"基础信息"}],"config":[{"columns":[{"key":"id","readonly":true}]},{"columns":[{"key":"title","required":true}]},{"columns":[{"key":":category_name","required":true},{"key":"created_at"}]},{"columns":[{"key":"author"},{"key":"hits"}]},{"columns":[{"key":"titlepic"}]},{"columns":[{"key":"pics"}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"content","required":true}]},{"id":"rqfxwd86o28","columns":[{"key":["link"],"props":{"tooltip":"设置后列表会跳转该外链"}}]},{"columns":[{"key":"specs","type":"jsonForm"}]},{"columns":[{"key":"state"},{"key":"displayorder"}]}]}]}'
            ],
            'category'=>[
                'form_config'=>'[{"columns":[{"key":"id","readonly":true}]},{"columns":[{"key":"title"}]},{"id":"ocqx6wedk0u","columns":[{"key":["displayorder"]},{"key":["icon"]}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"titlepic"}]},{"columns":[{"key":["state"]}]}]',
                'table_config'=>'[{"key":"id"},{"key":"title"},{"key":"titlepic"},{"key":"displayorder"},{"key":"state"},{"key":"coption"}]',
                'other_config'=>'{"level"::level}'
            ]
        ];
    }

    public function getConfig($data,$search,$replace)
    {
        if(is_string($data))
        {
            return str_replace($search,$replace,$data);
        }
        foreach($data as $key=>$val)
        {
            $data[$key] = str_replace($search,$replace,$val);
        }
        return $data;
    }

    public function make()
    {
        $title = request('base.title');
        $name = request('base.name');

        $model_to_id = request('base.model_to_id',0);
        $menu_to_id = request('base.menu_to_id',0);

        $menu_name = '列表';

        //如果有自定义的分类id 那么下面不会自动创建分类信息 而是直接关联选中的分类模型
        $customer_category_id = request('base.category_id',0);

        $appname = DevService::appname();
        //先创建模型
        //1.列表
        $model = new Model();
        $model_to = $model->where(['id'=>$model_to_id])->first();
        $model_to_id = $model_to?$model_to['id']:0; 

        $category_level = request('base.category_level',1);
        $category_type = request('base.category_type','single');
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

        $category_model = $model->where(['id'=>$customer_category_id])->first();
        $search = [
            ':category_title',':category_name',':category_id',':_category_name'
        ];
        $replace = ['分类','category_id',$type,'_category_id'];
        if($category_model)
        {
            //自定义分类模型 需要更换name title
            $replace = [$category_model['title'],implode('_',[$category_model['name'],'id']),$type,implode('_',['',$category_model['name'],'id'])];
            $model_category_id = $category_model['id'];
        }

        $model_data = [
            'title'=>$title,
            'name'=>$name,
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'normal',
            'columns'=>$this->getConfig($this->schema['posts'],$search,$replace),
            'parent_id'=>$model_to_id
        ];
        
        $model_id = $this->checkHas($model_data);
        $ds = new DevService;
        if(!$customer_category_id)
        {
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
                'columns'=>$this->schema['category'],
                'parent_id'=>$model_f_id
            ];
            $model_category_id = $this->checkHas($model_category_data);
            $category_model = $model->where(['id'=>$model_category_id])->first();
            $ds->createModelSchema($category_model);
            $ds->createModelFile($category_model);
            $ds->createControllerFile($category_model);
        }
        
        $model = $model->where(['id'=>$model_id])->first();
        
        //创建数据表
        
        $ds->createModelSchema($model);
        
        //3.关联模型
        $relation = [
            'model_id'=>$model_id,
            'title'=>$category_model['title'],
            'name'=>$category_model['name'],
            'type'=>$relation_type,
            'foreign_model_id'=>$model_category_id,
            'foreign_key'=>'id',
            'local_key'=>implode('_',[$category_model['name'],'id']),
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
        
        //4.2及controller文件
        $ds->createControllerFile($model);
        

        //5 创建菜单
        //5.1大菜单
        //添加逻辑 如果选择了 menu_to_id 和 customer_category_id 即选了创建菜单到 和 指定分类 那么 表示直接创建列表 不要再创父级菜单

        if(!$menu_to_id || !$customer_category_id)
        {
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
        }else
        {
            //不创建大菜单 直接使用 传入的 菜单id值
            $big_menu_id = $menu_to_id;
            $menu_name = $title;
        }
        
        //5.3分类菜单
        if(!$customer_category_id)
        {
            //未指定分类模型 生成分类模型菜单
            $category_menu = array_merge([
                'title'=>'分类',
                'path'=>'category',
                'parent_id'=>$big_menu_id,
                'status'=>1,
                'state'=>1,
                'type'=>$appname,
                'page_type'=>'category',
                'open_type'=>'modal',
                'admin_model_id'=>$model_category_id,
            ],$this->getConfig($this->confJson['category'],[':level'],[$category_level]));
            $menu_category_id = $this->checkHasMenu($category_menu);
            $this->updateMenuDesc($menu_category_id);
        }
        
        //5.2列表
        $menu = array_merge([
            'title'=>$menu_name,
            'path'=>$name,
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'table',
            'open_type'=>'drawer',
            'admin_model_id'=>$model_id
        ],$this->getConfig($this->confJson['posts'],$search,$replace));
        
        $menu_id = $this->checkHasMenu($menu);
        //5.4生成菜单的配置信息 desc
        //所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);
        
        return;
    }
}