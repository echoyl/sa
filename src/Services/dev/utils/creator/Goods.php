<?php
namespace Echoyl\Sa\Services\dev\utils\creator;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Creator;

class Goods extends Creator
{
    var $schema;
    var $confJson;
    public function __construct()
    {
        $this->schema = [
            'goods'=>'[{"title":"id","name":"id","type":"int"},{"title":"名称","name":"name","type":"vachar"},{"title":"封面图片","name":"titlepic","type":"vachar","form_type":"image","form_data":1},{"title":"描述简介","name":"desc","type":"vachar","form_type":"textarea"},{"title":"状态","name":"state","type":"int","form_type":"switch","default":1,"form_data":"禁用,启用","setting":{"open":"上架","close":"下架"},"table_menu":true},{"title":"价格","name":"price","form_type":"price","type":"int"},{"title":"结算价格","name":"jiesuan_price","form_type":"price","type":"int"},{"title":"成本价格","name":"chengben_price","form_type":"price","type":"int"},{"title":"最大价格","name":"max_price","type":"int","form_type":"price"},{"title":"库存","name":"sku","type":"int","form_type":"digit"},{"title":"原价","name":"old_price","type":"int","form_type":"price"},{"title":"图集轮播","name":"pics","type":"text","form_type":"image","setting":{"image_count":"9"}},{"title":"最大购买数量","name":"max","type":"int"},{"title":"开启规格","name":"guige_open","type":"int","default":"0"},{"title":"详情","name":"content","type":"text","form_type":"tinyEditor"},{"title":"参数","name":"canshu","type":"text","form_type":"json"},{"title":"是否推荐","name":"recommend","type":"int","form_type":"switch","setting":{"open":"推荐","close":"不推荐"}}]',
            'guige'=>'[{"title":"id","name":"id","type":"int"},{"title":"商品ID","name":"goods_id","type":"vachar"},{"title":"价格","name":"price","type":"int","form_type":"price"},{"title":"库存","name":"sku","type":"int","form_type":"digit"},{"title":"描述","name":"desc","type":"varchar"},{"title":"ID拼接","name":"ids","type":"varchar"},{"name":"old_price","title":"原价","type":"int","form_type":"price"},{"name":"max","title":"最大购买","type":"int","form_type":"digit"},{"title":"结算价格","name":"jiesuan_price","type":"int","form_type":"price"},{"title":"成本价格","name":"chengben_price","type":"int","form_type":"price"},{"title":"图片","name":"titlepic","type":"varchar","form_type":"image","setting":{"image_count":"1"}}]',
            'items'=>'[{"title":"id","name":"id","type":"int"},{"title":"名称","name":"name","type":"vachar"},{"title":"商品ID","name":"goods_id","type":"int"},{"title":"上级ID","name":"parent_id","type":"int"}]'
        ];

        $this->confJson = [
            'goods'=>[
                'table_config'=>'[{"key":"name","can_search":[1],"uid":"CDM1m4Na4R"},{"key":"titlepic","uid":"q8vlGU9aRK"},{"uid":"i3Mz74H9zh","afterUid":null,"key":"state","can_search":[],"table_menu":[1]},{"key":["recommend"],"uid":"MglveT7L5W"},{"key":"displayorder","uid":"P94u1slM6D"},{"key":"option","uid":"9vIzwfUuyP"}]',
                'form_config'=>'{"tabs":[{"tab":{"title":"基础信息"},"config":[{"columns":[{"key":["name"],"readonly":false,"required":true}]},{"columns":[{"key":"titlepic","props":{"tip":{"extra":"建议尺寸800*800"}}},{"key":["pics"],"props":{"tip":{"extra":"建议尺寸800*800"}}}]},{"id":"t7m6qz2qacu","columns":[{"key":["canshu"],"type":"formList","props":{"outside":{"columns":[{"valueType":"group","columns":[{"title":"名称","dataIndex":"key","colProps":{"span":12}},{"title":"参数值","dataIndex":"value","colProps":{"span":12}}]}]}}}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"displayorder"},{"key":"state"},{"key":["recommend"]}]}]},{"tab":{"title":"规格参数"},"config":[{"id":"feisotbwsd4","columns":[{"key":["guiges"],"type":"guigePanel","props":{"title":"规格属性","dataIndex":"guiges","fieldProps":{"columns":["price","sku","old_price","max"]}}}]}]},{"tab":{"title":"商品详情"},"config":[{"id":"jp135l2ydut","columns":[{"key":"content"}]}]}]}'
            ]
        ];
    }

    /**
     * 生成规格模型
     *
     * @param integer $parent_id
     * @return void
     */
    public function guige($parent_id = 0,$goods_model_id = 0)
    {
        $appname = DevService::appname();
        //规格
        $guige_model_data = [
            'title'=>'商品规格',
            'name'=>'guige',
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'normal',
            'parent_id'=>$parent_id,
            'columns'=>$this->schema['guige']
        ];
        $model_id = $this->checkHas($guige_model_data);
        $ds = new DevService;
        $model = Model::where(['id'=>$model_id])->first();

        //添加关联
        $this->addRelation($goods_model_id,$model,[
            'title'=>'规格','name'=>'guiges','local_key'=>'id','foreign_key'=>'goods_id','type'=>'many',
        ]);

        $ds->createModelSchema($model);
        $ds->createModelFile($model);

        return $model;
    }

    public function items($parent_id = 0,$goods_model_id = 0)
    {
        $appname = DevService::appname();
        //规格
        $guige_model_data = [
            'title'=>'属性名称',
            'name'=>'items',
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'normal',
            'parent_id'=>$parent_id,
            'columns'=>$this->schema['items']
        ];
        $model_id = $this->checkHas($guige_model_data);
        $ds = new DevService;
        $model = Model::where(['id'=>$model_id])->first();

        //创建子属性关联
        $s_relation_id = $this->addRelation($model_id,$model,[
            'title'=>'子属性',
            'name'=>'items',
            'local_key'=>'id',
            'foreign_key'=>'parent_id',
            'type'=>'many'
        ]);

        $fields = ['id','name','goods_id','parent_id'];
        $_fields = $fields;
        foreach($fields as $f)
        {
            $_fields[] = implode('-',[$s_relation_id,$f]);
        }

        //添加关联
        $this->addRelation($goods_model_id,$model,[
            'title'=>'属性',
            'name'=>'items',
            'local_key'=>'id',
            'foreign_key'=>'goods_id',
            'type'=>'many',
            'select_columns'=>implode(',',$_fields)
        ]);

        $ds->createModelSchema($model);
        $ds->createModelFile($model);

        return $model;
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
                'type'=>$appname,
            ];
            $big_menu_id = $this->checkHasMenu($big_menu);
        }else
        {
            //不创建大菜单 直接使用 传入的 菜单id值
            $big_menu_id = $menu_to_id;
            $menu_name = $title;
        }

        
        //先创建模型 模型默认放到文件夹下

        //2.1 先创建posts父级
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
            'search_columns'=>'[{"name":"name","type":"like","columns":["name"]}]',
        ];
        
        $model_id = $this->checkHas($model_data);
        $ds = new DevService;

        //创建商品规格和属性
        $guige_model = $this->guige($model_to_id,$model_id);
        $items_model = $this->items($model_to_id,$model_id);

        //创建分类
        $category = $this->category($model_id,$big_menu_id,$model_to_id);
        //更新字段
        $model->where(['id'=>$model_id])->update([
            'columns'=>$this->addColumns($this->schema['goods'],$category['columns']),
        ]);

        $model = $model->where(['id'=>$model_id])->first();
        
        //创建数据表
        
        $ds->createModelSchema($model);

        //4.1生成model文件
        
        $ds->createModelFile($model);
        
        //4.2及controller文件
        $customer_code = <<<'EOD'
public function postData(&$item)
    {
        if(!$this->is_post)
        {
            $s = new GoodsService(new Goods(),new Items(),new Guige());
            $item['guiges'] = $s->parseGuige($item);
        }
        return;
    }
    public function afterPost($id, $data)
    {
        $s = new GoodsService(new Goods(),new Items(),new Guige());
        $s->guige2DB(request('base.guiges'),$id);
        return;
    }
EOD;
    [$guige_namespace] = $ds->getNamespace($guige_model);
    [$items_namespace] = $ds->getNamespace($items_model);
        $customer_namespace = [
            'use Echoyl\Sa\Services\shop\GoodsService;',
            $guige_namespace.';',
            $items_namespace.';',
        ];
        $customer_contruct = <<<'EOD'
        $this->dont_post_columns = ['guiges'];
        EOD;
        $ds->createControllerFile($model,[$customer_code,$customer_contruct,implode("\r",$customer_namespace),'']);
        
        
        
        //5.2列表
        $form_config = json_decode($this->confJson['goods']['form_config'],true);
        $tabs = json_encode($form_config['tabs'][0]);
        //d($tabs);
        $tabs = $this->addColumns($tabs,$category['menu_form_columns'],'config');
        $form_config['tabs'][0] = json_decode($tabs,true);
        $form_config = json_encode($form_config);

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
        ],[
            'table_config'=>$this->addColumns($this->confJson['goods']['table_config'],$category['menu_columns']),
            'form_config'=>$form_config
        ]);
        
        $menu_id = $this->checkHasMenu($menu);
        //5.4生成菜单的配置信息 desc
        //所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);
        
        return;
    }
}