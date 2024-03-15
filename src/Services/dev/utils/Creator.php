<?php
namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Http\Controllers\admin\dev\MenuController;
use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\creator\Goods;
use Echoyl\Sa\Services\dev\utils\creator\Perm;
use Echoyl\Sa\Services\dev\utils\creator\Posts;
use Echoyl\Sa\Services\dev\utils\creator\Shop;
use Illuminate\Support\Arr;

class Creator
{

    /**
     * 根据类型生成
     *
     * @param [type] $type
     * @return void
     */
    public function create(?string $type = '')
    {
        if(method_exists($this,$type))
        {
            $this->$type();
            return [0,'success'];
        }else
        {
            return [1,'developing']; 
        }
    }

    public function posts()
    {
        $c = new Posts;
        $c->make();
        return;
    }  

    public function perm()
    {
        $c = new Perm;
        $c->make();
        return;
    }

    public function shop()
    {
        $c = new Shop;
        $c->make();
        return;
    }

    public function goods()
    {
        $c = new Goods;
        $c->make();
        return;
    }

    public function getConfig($data,$search,$relpace)
    {
        if(is_string($data))
        {
            return str_replace($search,$relpace,$data);
        }
        foreach($data as $key=>$val)
        {
            $data[$key] = str_replace($search,$relpace,$val);
        }
        return $data;
    }

    public function addColumns($json,$add,$key = false)
    {
        $json = json_decode($json,'true');
        if($key && isset($json[$key]))
        {
            $json[$key] = array_merge($add,$json[$key]);
        }else
        {
            $json = array_merge($add,$json);
        }
        return json_encode($json);
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

    public function category($model_id,$big_menu_id,$model_to_id = 0)
    {
        $schema = '[{"title":"id","name":"id","type":"int"},{"title":"名称","name":"title","type":"varchar"},{"title":"描述","name":"desc","type":"varchar","form_type":"textarea"},{"title":"父级Id","name":"parent_id","type":"int"},{"title":"图片","name":"titlepic","type":"varchar","form_type":"image","setting":{"image_count":"1"}},{"title":"状态","name":"state","type":"int","form_type":"switch","default":"1","setting":{"open":"启用","close":"禁用"},"table_menu":true}]';
        
        $confJson = [
            'form_config'=>'{"tabs":[{"tab":{"title":"基础信息"},"config":[{"id":"notvg3ox047","columns":[{"key":["title"],"required":true},{"key":["displayorder"]}]},{"id":"s6hsnxta2yc","columns":[{"key":["desc"]}]},{"id":"ovjiqj6t6oh","columns":[{"key":["state"]}]}]}]}',
            'table_config'=>'[{"key":"id"},{"key":"title"},{"key":"displayorder"},{"key":"state","table_menu":[1]},{"key":["option"]}]',
            'other_config'=>'{"level"::level}'
        ];

        $base = request('base',[]);

        //如果有自定义的分类id 那么下面不会自动创建分类信息 而是直接关联选中的分类模型
        $customer_category_id = Arr::get($base,'category_id',0);

        $appname = DevService::appname();
        //先创建模型

        $category_level = Arr::get($base,'category_level',1);
        $category_type = Arr::get($base,'category_type','single');

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

        $model = new Model();

        $category_model = $model->where(['id'=>$customer_category_id])->first();

        $c_title = $category_model?$category_model['title']:'分类';

        $c_name = $category_model?implode('_',[$category_model['name'],'id']):'category_id';

        //数据表字段需要增加的字段
        $model_columns = [
            [
                "title"=> $c_title,
                "name"=> $c_name,
                "type"=> "varchar",
                "form_type"=>$type,
                "setting"=>[
                    "label"=> "title",
                    "value"=> "id"
                ]
            ],
            [
                "title"=> $c_title,
                "name"=> implode('_',['',$c_name]),
                "type"=> "varchar",
                "length"=> 500
            ]
        ];

        //页面中 table 和form 需要增加的字段
        $menu_columns = [
            [
                'key'=>$c_name
            ]
        ];
        $menu_form_columns = [[
            'columns'=>[
                [
                    'key'=>$c_name
                ]
            ]
        ]];

        //没有分类模块的话创建模块
        if(!$category_model)
        {
            $ds = new DevService;
            //2.分类表

            //2.2 category
            $model_category_data = [
                'title'=>$c_title,
                'name'=>'category',
                'admin_type'=>$appname,
                'type'=>1,
                'leixing'=>'category',
                'columns'=>$schema,
                'parent_id'=>$model_to_id
            ];
            $model_category_id = $this->checkHas($model_category_data);
            $category_model = $model->where(['id'=>$model_category_id])->first();
            $ds->createModelSchema($category_model);
            $ds->createModelFile($category_model);
            $ds->createControllerFile($category_model);
        }else
        {
            $model_category_id = $category_model['id'];
        }

        //3.关联模型
        $this->addRelation($model_id,$category_model,[
            'type'=>$relation_type,
            'local_key'=>$c_name
        ]);

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
            ],$this->getConfig($confJson,[':level'],[$category_level]));
            $menu_category_id = $this->checkHasMenu($category_menu);
            $this->updateMenuDesc($menu_category_id);
        }

        return [
            'columns'=>$model_columns,
            'menu_columns'=>$menu_columns,
            'menu_form_columns'=>$menu_form_columns
        ];

    }

    public function addRelation($model_id,$relation_model,$relation)
    {
        $relation = array_merge([
            'model_id'=>$model_id,
            'title'=>$relation_model['title'],
            'name'=>$relation_model['name'],
            'type'=>'one',
            'foreign_model_id'=>$relation_model['id'],
            'foreign_key'=>'id',
            'created_at'=>now(),
            'updated_at'=>now(),
            'is_with'=>1
        ],$relation);
        $has_relation = (new Relation())->where(['model_id'=>$model_id,'foreign_model_id'=>$relation_model['id'],'name'=>$relation['name']])->first();
        if(!$has_relation)
        {
            $relation_id = (new Relation())->insertGetId($relation);
        }else
        {
            (new Relation())->where(['id'=>$has_relation['id']])->update($relation);
            $relation_id = $has_relation['id'];
        }
        return $relation_id;
    }

}