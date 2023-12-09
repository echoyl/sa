<?php
namespace Echoyl\Sa\Services\dev\utils\creator;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Creator;

class Shop extends Creator
{
    var $schema;
    var $confJson;
    public function __construct()
    {
        $this->schema = [
            'shop'=>'[{"title":"id","name":"id","type":"int"},{"title":"名称","name":"title","type":"vachar"},{"title":"图片","name":"titlepic","type":"vachar","form_type":"image","setting":{"image_count":1}},{"title":"描述","name":"desc","type":"vachar","form_type":"textarea","empty":[1]},{"title":"状态","name":"state","type":"int","form_type":"switch","default":1,"setting":{"open":"启用","close":"禁用"},"table_menu":true},{"title":"联系人","name":"username","type":"varchar","empty":[]},{"title":"联系电话","name":"mobile","type":"varchar"},{"title":"图集","name":"pics","type":"text","form_type":"image","setting":{"image_count":"9"}},{"title":"位置经度","name":"lng","type":"varchar"},{"title":"位置纬度","name":"lat","type":"varchar","form_type":"tmapInput"},{"title":"省","name":"province","type":"varchar","form_type":"pca","setting":{"pca_level":"3"}},{"name":"city","title":"市","type":"varchar"},{"name":"area","title":"区","type":"varchar"},{"title":"位置地址","name":"address","type":"varchar"}]',
        ];

        $this->confJson = [
            'shop'=>[
                'table_config'=>'[{"key":"id"},{"key":"title"},{"key":"titlepic"},{"key":"desc"},{"key":"username"},{"key":"mobile"},{"key":"province"},{"key":"address"},{"key":"displayorder"},{"key":"state","table_menu":[1]},{"key":"option"},{"id":"5aa6x9qokiq","key":["id"],"can_search":[1],"hide_in_table":[1],"props":{"title":"关键字检索","dataIndex":"keyword","tip":{"placeholder":"请输入门店名称，联系人，联系电话等"}}}]',
                'form_config'=>'{"tabs":[{"tab":{"title":"基础信息"},"config":[{"columns":[{"key":"id"}]},{"columns":[{"key":"title"}]},{"id":"wbeki0juh5p","columns":[{"key":["username"]},{"key":["mobile"]}]},{"columns":[{"key":"titlepic"},{"key":["pics"]}]},{"columns":[{"key":"province"},{"key":["address"]}]},{"columns":[{"key":"lat"}]},{"columns":[{"key":"desc"}]},{"columns":[{"key":"displayorder"},{"key":["state"]}]}]}]}'
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
            'search_columns'=>'[{"name":"keyword","type":"like","columns":["title","username","mobile"]}]',
        ];
        
        $model_id = $this->checkHas($model_data);
        $ds = new DevService;

        //创建分类
        $category = $this->category($model_id,$big_menu_id,$model_to_id);
        //创建省市区的关联
        $pca_relation = (new Model())->where(['id'=>25])->first()->toArray();
        $this->addRelation($model_id,$pca_relation,[
            'title'=>'省','name'=>'provinceData','local_key'=>'province','foreign_key'=>'code'
        ]);
        $this->addRelation($model_id,$pca_relation,[
            'title'=>'市','name'=>'cityData','local_key'=>'city','foreign_key'=>'code'
        ]);
        $this->addRelation($model_id,$pca_relation,[
            'title'=>'区','name'=>'areaData','local_key'=>'area','foreign_key'=>'code'
        ]);
        //更新字段
        $model->where(['id'=>$model_id])->update([
            'columns'=>$this->addColumns($this->schema['shop'],$category['columns']),
        ]);

        $model = $model->where(['id'=>$model_id])->first();
        
        //创建数据表
        
        $ds->createModelSchema($model);

        //4.1生成model文件
        
        $ds->createModelFile($model);
        
        //4.2及controller文件
        $ds->createControllerFile($model);
        
        
        
        //5.2列表
        $form_config = json_decode($this->confJson['shop']['form_config'],true);
        $tabs = json_encode($form_config['tabs'][0]);
        //d($tabs);
        $tabs = $this->addColumns($tabs,$category['menu_form_columns'],'config');
        $form_config = json_encode(['tabs'=>[json_decode($tabs,true)]]);

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
            'table_config'=>$this->addColumns($this->confJson['shop']['table_config'],$category['menu_columns']),
            'form_config'=>$form_config
        ]);
        
        $menu_id = $this->checkHasMenu($menu);
        //5.4生成菜单的配置信息 desc
        //所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);
        
        return;
    }
}