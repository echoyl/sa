<?php
namespace Echoyl\Sa\Services\dev\utils\creator;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Creator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Order extends Creator
{
    var $schema;
    var $confJson;
    var $top_model_id = 0;
    var $top_menu_id = 0;
    var $appname;
    var $state_model_id = 0;
    var $goodss_model_id = 0;
    var $goods_model_id = 0;
    var $goods_page = 0;
    var $goodss_relation_id = 0;
    var $goods_items_relation_id = 0;
    public function __construct()
    {
        $order_schema = <<<'EOD'
        [{"title":"取消时间","name":"cancel_at","type":"datetime","form_type":"datetime"},{"title":"备注","name":"desc","type":"varchar","form_type":"textarea"},{"title":"完成时间","name":"end_at","type":"datetime","form_type":"datetime"},{"title":"id","name":"id","type":"int"},{"type":"int","title":"支付记录ID","name":"paylog_id"},{"title":"评价时间","name":"pingjia_at","type":"datetime","form_type":"datetime"},{"title":"评价内容","name":"pingjia_content","type":"varchar","length":"500","form_type":"textarea"},{"title":"评价分数","name":"pingjia_fenshu","type":"int","form_type":"digit"},{"title":"订单金额","name":"price","type":"int","form_type":"price"},{"title":"订单号","name":"sn","type":"varchar"},{"title":"开始配送时间","name":"start_at","type":"datetime","form_type":"datetime"},{"title":"状态","name":"state_id","type":"int","form_type":"select","setting":{"label":"title","value":"id"},"table_menu":true},{"title":"下单用户","name":"user_id","type":"int","form_type":"searchSelect","setting":{"label":"{{[record.username,record.mobile].join(' ^ ')}}","value":"id"}}]
        EOD;
        $this->schema = [
            'state'=>'[{"title":"id","name":"id","type":"int"},{"title":"名称","name":"title","type":"vachar"},{"title":"描述","name":"desc","type":"vachar"},{"title":"颜色","name":"color","type":"vachar"},{"title":"父级Id","name":"parent_id","type":"int","desc":null},{"title":"图片","name":"titlepic","type":"vachar","form_type":"image","setting":{"image_count":1}},{"title":"状态","name":"state","type":"int","form_type":"switch","default":1,"setting":{"open":"启用","close":"禁用"}}]',
            'order'=>$order_schema,
            'goods'=>'[{"name":"goods_id","title":"商品信息","type":"int","form_type":"searchSelect","setting":{"label":"name","value":"id"}},{"name":"guige","title":"规格属性","type":"varchar"},{"name":"guige_ids","title":"规格属性id","type":"varchar"},{"title":"id","name":"id","type":"int"},{"name":"num","title":"商品数量","type":"int","form_type":"digit"},{"name":"order_id","title":"订单信息","type":"int"},{"name":"price","title":"商品价格","type":"int","form_type":"price"}]',
        ];

        $form_json = <<<'EOD'
        {"tabs":[{"tab":{"title":"基础信息"},"config":[{"columns":[{"uid":"OgMLXVnFnr","key":["sn"],"props":{"tip":{"extra":"订单编号不填写系统会自动生成"}}}],"uid":"ukG7dH3xQI"},{"columns":[{"uid":"rib37KOJei","key":"user_id","required":true,"props":{"title":"客户选择"}}],"uid":"2nbMZbWNsl"},{"columns":[{"uid":"GY1hSZD9Y8","key":"state_id","required":true}],"uid":"s7CSmAgJJK"},{"columns":[{"key":"desc","uid":"QMuiQiVRol"}],"uid":"D82dK0hNVr"},{"columns":[{"uid":"LbuQ70PmBG","key":["end_at"],"readonly":true}],"uid":"tQe6BsJWkJ"}],"uid":"bHwYQIG1iz"},{"uid":"XkYi2dnvYb","tab":{"title":"商品信息"},"config":[{"columns":[{"uid":"0HFT58ZjyH","key":["goodss"],"type":"formList","props":{"title":"商品选择","fieldProps":{"max":10},"page":"$goods_page$","outside":{"rowProps":{"gutter":0}}}}],"uid":"3OzNzqUibW"},{"columns":[{"uid":"eKIOV89dZw","key":["price"],"type":"customerColumn","props":{"title":"统计","dom_direction":"none","dependencyOn":{"type":"render","condition":[{"name":["goodss"]}]},"items":[{"uid":"zo6rkwsmc30","domtype":"table","page":"$goods_page$","fieldProps":{"value":{"pagination":false,"rowKey":"fake_id"},"cal":"{{ ({record})=>{if(!record.goodss){return[]}const goodss_fake=[];var total_num=0;const total_p=record.goodss?.map((v,idx)=>{const goods=v.goods_id;if(!goods){return 0}var num=v.num?v.num:0;total_num=total_num+num;var price=goods.price?goods.price:0;var desc='';var ogid=v.id?v.id:0;if(goods?.guiges?.length>0){var itemids=[];for(var i in v){if(i.indexOf('item_')>-1){const[it,it_id]=i.split('_');if(goods.items.find(vgi=>vgi.id==it_id)){itemids.push(v[i])}else{delete v[i]}}};const guige=goods.guiges.find(gv=>gv.ids==itemids.join(':'));if(guige){price=guige.price;desc=guige.desc}};goodss_fake.push({fake_id:idx,goods:{name:goods.name},guige:desc,price:price,num:num,total_price:(price*num).toFixed(2),});return price*num}).reduce((total,currentValue)=>{return total+currentValue},0);goodss_fake.push({fake_id:'goods_all_price',goods:{name:'总计'},guige:'',price:'-',num:total_num,total_price:total_p.toFixed(2)});return goodss_fake} }}"}}]}}],"uid":"2O7v6ha6EG"}]}]}
        EOD;
        $form_json_goods = <<<'EOD'
        {"tabs":[{"tab":{"title":"基础信息"},"config":[{"columns":[{"uid":"8RY6KZoJ7M","key":["goods_id"],"props":{"span":"6","fieldProps":{"extColumns":["items","guiges","price"]}},"required":true},{"uid":"oKL2xyRJ5r","key":"num","props":{"title":"数量","width":"100%","span":"6"},"required":false},{"uid":"gz2mLDLndg","key":["customer_field"],"props":{"span":"8","outside":{"columns":"({goods_id})=>{if(goods_id){const count=goods_id.items?.length;const span=count>0?12/count:0;const items=goods_id.items?.map((v,index)=>{return{colProps:{span},dataIndex:'item_'+v.id,title:v.name,valueType:'select',formItemProps:{rules:[{required:true}]},fieldProps:{options:v.items,fieldNames:{label:'name',value:'id'},placeholder:'请选择'+v.name}}});return items}return[]}"},"dependencyOn":{"type":"render","condition":[{"name":["goods_id"]}]}}}],"uid":"HR1YqJhk5m"}],"uid":"qdnenUUCaT"}]}
        EOD;

        $this->confJson = [
            'order'=>[
                'table_config'=>'[{"uid":"mXTRFs1Y9F","key":["customer_field"],"type":"customerColumn","props":{"title":"操作栏","width":"150","fixed":"right","items":[{"uid":"zpanieefpth","domtype":"button","btn":{"type":"link","size":"small","icon":"FileSearchOutlined"},"action":"view"},{"uid":"bxl47xdsqlb","domtype":"button","action":"edit","btn":{"type":"link","size":"small","icon":"EditOutlined"}},{"uid":"4oyo675uvc5","domtype":"button","if":"{{record?.can_wancheng}}","btn":{"type":"link","size":"small","icon":"CheckOutlined","tooltip":"完成订单"},"action":"confirm","modal":{"msg":"确定要完成订单吗？"},"request":{"url":"order/order/wancheng"}},{"uid":"oxxqfv5vpxh","domtype":"button","action":"delete","btn":{"type":"link","size":"small","icon":"DeleteOutlined","danger":true}}]}},{"uid":"NOf3nTKnd6","key":"sn","props":{"title":"订单号","width":"160"},"can_search":[]},{"uid":"WjQZu0KEoH","key":["customer_field"],"props":{"title":"关键字搜索","dataIndex":"keyword","tip":{"placeholder":"请输入订单编号,单号,客户名称搜索"}},"can_search":[1],"hide_in_table":[1]},{"uid":"tgc66bKNR6","type":"dateRange","props":{"title":"完成时间","dataIndex":"end_at"},"can_search":[1],"hide_in_table":[1]},{"uid":"0SbNL6HWi4","key":["customer_field"],"type":"html","props":{"title":"商品信息","dataIndex":"goods_desc","width":"250"}},{"uid":"LiQaHiuTBc","key":["price"]},{"uid":"vOFs5DWkMd","key":["user_id"],"can_search":[]},{"uid":"LpG7uDad6P","key":"state_id","hide_in_table":[1],"table_menu":[1]},{"uid":"L1tAnfy13D","key":["customer_field"],"type":"export","props":{"title":"导出"}},{"key":"created_at","uid":"1oK7vSOmbv"},{"uid":"tJl5uxOGFY","key":["state"],"type":"customerColumn","props":{"items":[{"uid":"ox4zgj2ohm2","domtype":"tag"}]}}]',
                'form_config'=>$form_json
            ],
            //订单中商品列表表单配置
            'goods'=>[
                'table_config'=>'[{"uid":"newWUjcnpA","key":["goods","name"],"props":{"title":"商品名称","width":"300"}},{"uid":"wjYe9J4T98","key":["guige"]},{"uid":"pV0Pef84nX","key":["price"],"props":{"title":"单价"}},{"uid":"OFsqVPHoqh","key":"num","props":{"title":"数量"}},{"uid":"QbnZLZVw8c","key":["customer_field"],"props":{"title":"总价","dataIndex":"total_price"}}]',
                'form_config'=>$form_json_goods
            ]
        ];
        $this->appname = DevService::appname();
    }

    /**
     * 创建订单状态模型
     *
     * @return void
     */
    public function state()
    {
        $model_data = [
            'title'=>'状态',
            'name'=>'state',
            'admin_type'=>$this->appname,
            'type'=>1,
            'leixing'=>'normal',
            'parent_id'=>$this->top_model_id,
            'columns'=>$this->schema['state'],
        ];
        $model_id = $this->checkHas($model_data);
        //创建表
        $ds = new DevService;

        $this->state_model_id = $model_id;

        $model = new Model();

        $schema = $model->where(['id'=>$model_id])->first();
        
        //创建数据表
        
        $table_name = $ds->createModelSchema($schema);

        //插入默认订单状态数据
        $datas = [
            ['id'=>1,'title'=>'已下单','color'=>'orange'],
            ['id'=>2,'title'=>'已付款','color'=>'green'],
            ['id'=>3,'title'=>'已发货','color'=>'yellow'],
            ['id'=>4,'title'=>'已收货','color'=>'purple'],
            ['id'=>5,'title'=>'已完成','color'=>'blue'],
            ['id'=>99,'title'=>'已取消','color'=>'gray'],
        ];

        DB::table($table_name)->upsert($datas,['id']);

        $ds->createModelFile($schema);

        return;
    }

    public function goods()
    {
        $model_data = [
            'title'=>'商品',
            'name'=>'goods',
            'admin_type'=>$this->appname,
            'type'=>1,
            'leixing'=>'normal',
            'parent_id'=>$this->top_model_id,
            'columns'=>$this->schema['goods'],
        ];
        $model_id = $this->checkHas($model_data);
        //创建表
        $ds = new DevService;

        $this->goodss_model_id = $model_id;

        $model = new Model();

        $schema = $model->where(['id'=>$model_id])->first();
        
        //创建数据表
        
        $ds->createModelSchema($schema);
        //创建菜单
        $menu = array_merge([
            'title'=>'商品',
            'path'=>'goods',
            'parent_id'=>$this->top_menu_id,
            'status'=>0,//隐藏显示
            'state'=>1,
            'type'=>$this->appname,
            'page_type'=>'table',
            'open_type'=>'modal',
            'admin_model_id'=>$model_id
        ],[
            'table_config'=>$this->confJson['goods']['table_config'],
            'form_config'=>$this->confJson['goods']['form_config']
        ]);
        
        $menu_id = $this->checkHasMenu($menu);
        $this->goods_page = $menu_id;
        //创建关联至商品模型 必填项
        $goods_model_id = request('base.goods_id');
        $this->goods_model_id = $goods_model_id;
        $this->goodss_relation_id = $this->addRelation($model_id,Model::where(['id'=>$goods_model_id])->first(),['local_key'=>'goods_id']);
        $goods_items_relation = Relation::where(['model_id'=>$goods_model_id,'name'=>'items'])->first();
        $this->goods_items_relation_id = $goods_items_relation?$goods_items_relation->id:0;
        
        $ds->createModelFile($schema);

        $ds->createControllerFile($schema);

        //所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);

        return;
    }

    /**
     * 先创建最外层的模型和菜单
     *
     * @return void
     */
    public function top()
    {
        $title = request('base.title');
        $name = request('base.name');

        $model_to_id = request('base.model_to_id',0);
        $menu_to_id = request('base.menu_to_id',0);

        $appname = DevService::appname();

        //5 创建菜单
        //5.1大菜单
        $big_menu = [
            'title'=>$title,
            'path'=>$name,
            'parent_id'=>$menu_to_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'icon'=>'UnorderedListOutlined'
        ];
        $this->top_menu_id = $this->checkHasMenu($big_menu);

        
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
        $this->top_model_id = $this->checkHas($model_parent);
        return;
    }

    public function order()
    {
        $menu_name = '列表';

        $appname = $this->appname;

        //1.列表
        $model = new Model();
        $title = request('base.title');
        $name = request('base.name');
        $model_data = [
            'title'=>$title,
            'name'=>$name,
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'normal',
            'parent_id'=>$this->top_model_id,
            'columns'=>$this->schema['order'],
            'search_columns'=>'[{"name":"keyword","type":"like","columns":["sn"]},{"name":"end_at","type":"whereBetween","columns":["end_at"]}]'
        ];

        $model_id = $this->checkHas($model_data);
        $ds = new DevService;

        $schema = $model->where(['id'=>$model_id])->first();
        
        //创建数据表
        
        $ds->createModelSchema($schema);

        //4.1生成model文件
        //创建关联 state_id 和 hasmany goods
        $this->addRelation($model_id,Model::where(['id'=>$this->state_model_id])->first(),['local_key'=>'state_id']);
        
        $this->addRelation($model_id,Model::where(['id'=>$this->goodss_model_id])->first(),[
            'type'=>'many',
            'local_key'=>'id',
            'foreign_key'=>'order_id',
            'name'=>'goodss',
            'select_columns'=>implode(',',[implode('-',[$this->goodss_relation_id,'guiges','']),implode('-',[$this->goodss_relation_id,$this->goods_items_relation_id,'items',''])]),
        ]);
        

        $user_model_id = request('base.user_id');
        if($user_model_id)
        {
            //如果选择了用户模型 那么关联用户模型
            $this->addRelation($model_id,Model::where(['id'=>$user_model_id])->first(),['local_key'=>'user_id']);
        }

        $ds->createModelFile($schema);
        
        //4.2及controller文件
        $ds->createControllerFile($schema,$this->customerCode());

        $form_config = str_replace('$goods_page$',$this->goods_page,$this->confJson['order']['form_config']);

        $menu = array_merge([
            'title'=>$menu_name,
            'path'=>$name,
            'parent_id'=>$this->top_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'table',
            'open_type'=>'modal',
            'admin_model_id'=>$model_id,
            'icon'=>'UnorderedListOutlined'
        ],[
            'table_config'=>$this->confJson['order']['table_config'],
            'form_config'=>$form_config,
        ]);
        
        $menu_id = $this->checkHasMenu($menu);
        //5.4生成菜单的配置信息 desc
        //所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);
        
        return;
    }

    public function make()
    {
        $this->top();
        $this->state();
        $this->goods();
        $this->order();
        
        return;
    }

    public function customerCode()
    {
        $customer_code = <<<'EOD'
public function listData(&$list)
    {
        foreach($list as $key=>$val)
        {
            $val = array_merge($val,$this->parseGoodsList($val['goodss']));
            $list[$key] = $val;
        }
        return $list;
    }
    public function postData(&$item)
    {
        if(!$this->is_post)
        {
            if(isset($item['goodss']) && !empty($item['goodss']))
            {
                //处理订单商品信息
                foreach($item['goodss'] as $key=>$val)
                {
                    $nval = $val['goods'];
                    $nval['label'] = $val['goods']['name'];
                    $nval['value'] = $val['goods']['id'];
                    $nval['num'] = $val['num'];

                    
                    $ids = explode(':',$val['guige_ids']);
                    foreach($val['goods']['items'] as $k=>$v)
                    {
                        $val['item_'.$v['id']] = intval($ids[$k]);
                    }
                    
                    $val['goods'] = $nval;
                    $item['goodss'][$key] = $val;
                }
            }
        }
    }
    public function beforePost(&$data, $id = 0, $item = [])
    {
        // $data['user_id'] = $data['user_id']['id'];
        if(!isset($data['sn']) || !$data['sn'])
        {
            $data['sn'] = OrderService::createSn();
        }
        if(isset($data['goodss']))
        {
            if(empty($this->getCreateGoods($data['goodss'])))
            {
                return $this->failMsg('商品信息错误');
            }
        }
    }
    public function setBase($data)
    {
        if(isset($data['goodss']))
        {
            $data['goodss'] = $this->getCreateGoods($data['goodss']);
        }
        return $data;
    }
    public function afterPost($id, $data)
    {
        $os = new OrderService(Order::class,OrderGoods::class);
        $price = $os->calculateGoodsPrice($id);
        $this->model->where(['id'=>$id])->update(['price'=>$price]);
        return;
    }

    public function getCreateGoods($posts)
    {
        static $fake_goods = [];
        if(!empty($fake_goods))
        {
            return $fake_goods;
        }
        foreach($posts as $goodss)
        {
            $data = $this->createGoods($goodss);
            if($data)
            {
                $fake_goods[] = $data;
            }
        }
        return $fake_goods;
    }

    public function createGoods($data)
    {
        $ids = [];
        foreach($data as $key=>$val)
        {
            if(strpos($key,'item_') !== false)
            {
                unset($data[$key]);
                $ids[] = $val;

            }
        }
        $goods = $data['goods_id'];
        $ordergoods = [
            'goods_id'=>$goods['id'],
            'id'=>$data['id']??0,
            'num'=>$data['num'],
            //'goods'=>$goods
        ];
        $goods_id = $goods['id'];
        //$goods_id = $ordergoods['goods_id'];
        //unset($data['goods_id']);
        if(!empty($ids))
        {
            //选择了规格
            sort($ids);
            $guige = (new Guige())->where(['goods_id'=>$goods_id,'ids'=>implode(':',$ids)])->first();
            if(!$guige)
            {
                return false;
            }
            $ordergoods['price'] = $guige['price'] / 100;
            $ordergoods['guige'] = $guige['desc'];
            $ordergoods['guige_ids'] = $guige['ids'];
        }else
        {
            //无规格直接读取商品价格
            $goods = (new Goods())->with(['category'])->where(['id'=>$goods_id])->first();
            if(!$goods)
            {
                return false;
            }
            $ordergoods['price'] = $goods['price'] / 100;
        }
        
        return $ordergoods;

    }
    public function afterDestroy($val)
    {
        (new OrderGoods())->where(['order_id'=>$val['id']])->forceDelete();
        return;
    }

    public function parseGoodsList($goodss,$split = '<br>')
    {
        $val = [];
        $desc = [];
        foreach($goodss as $g)
        {
            if(!$g['goods'])
            {
                continue;
            }
            $desc[] = implode(' ',[$g['num'],'X',$g['goods']['name'],$g['guige']]);
        }
        $val['goods_desc'] = implode($split,$desc);

        return $val;
    }
    /**
     * 导出数据格式化单个数据
     *
     * @param [type] $val
     * @return void
     */
    public function exportFormatData($val)
    {
        $val = array_merge($val,$this->parseGoodsList($val['goodss'],"\n"));
        $val['state'] = $val['state']['title'];
        $val['user']["{{[record.username,record.mobile].join(' ^ ')}}"] = implode(' ',[$val['user']['username'],$val['user']['mobile']]);//设置了模板label，这里默认导出需要处理下
        return $val;
    }
EOD;
    $ds = new DevService;
    $goods_model = Model::where(['id'=>$this->goods_model_id])->first();
    [$goods_namespace,$name] = $ds->getNamespace($goods_model);
    $guige_model = Model::where(['name'=>'guige','parent_id'=>$goods_model->parent_id])->first();

    [$guige_namespace] = $ds->getNamespace($guige_model);
   
    [$goodss_namespace] = $ds->getNamespace(Model::where(['id'=>$this->goodss_model_id])->first(),[$name]);
        $customer_namespace = [
            $goods_namespace.';',
            $guige_namespace.';',
            $goodss_namespace.';',
            'use Echoyl\Sa\Services\shop\OrderService;'
        ];

        return [$customer_code,'',implode("\r",$customer_namespace),''];
    }
}