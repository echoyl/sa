<?php
namespace Echoyl\Sa\Services\shop;

use Echoyl\Sa\Services\HelperService;

class GoodsService
{
    var $goodsModel;
    var $itemModel;
    var $guigeModel;
    var $columns;
    public function __construct($goodsModel,$itemModel,$guigeModel,$columns = [])
    {
        $this->goodsModel = $goodsModel;
        $this->itemModel = $itemModel;
        $this->guigeModel = $guigeModel;
        $this->columns = !empty($columns)?$columns:[
            ['name'=>'price','type'=>'price'],
            ['name'=>'old_price','type'=>'price'],
            ['name'=>'jiesuan_price','type'=>'price'],
            ['name'=>'chengben_price','type'=>'price'],
            ['name'=>'sku','type'=>''],
            ['name'=>'max','type'=>''],
        ];
    }


    public function parseGuige($goods)
    {
        if(!isset($goods['items']))
        {
            return [
                'items'=>[],
                'attrs'=>[],
                'open'=>false
            ];
        }
        $items = [];
        $items_id_map = [];
        foreach($goods['items'] as $item)
        {
            if($item['parent_id'] > 0)
            {
                continue;
            }
            $_its = [];
            foreach($item['items'] as $it)
            {
                $_its[] = [
                    'id'=>$it['id'],
                    'name'=>$it['name'],
                    'displayorder'=>$it['displayorder']??0
                ];
                $items_id_map[$it['id']] = $it['name'];
            }
            $_its = collect($_its)->sortBy('displayorder')->map(function($v){
                return ['id'=>$v['id'],'name'=>$v['name']];
            })->toArray();
            //d($_its);
            $items[] = [
                'id'=>$item['id'],
                'name'=>$item['name'],
                'items'=>array_values($_its),
                
            ];
        }
        //d($items);

        $guiges = [];
        foreach($goods['guiges'] as $guige)
        {
            $_guige = [
                'id'=>$guige['ids'],
            ];
            foreach($this->columns as $col)
            {
                $ctype = $col['type'];
                $cname = $col['name'];
                $v = '';
                if($ctype == 'price')
                {
                    $v = $guige[$cname]/100;
                }elseif($ctype == 'int')
                {
                    $v = $guige[$cname]??0;
                }else
                {
                    $v = $guige[$cname]??'';
                }
                $_guige[$cname] = $v;
            }
            if(isset($guige['titlepic']) && $guige['titlepic'])
            {
                $_guige['titlepic'] = HelperService::uploadParse($guige['titlepic'],false, ['p'=>'s']);
            }
            $ids = explode(':',$guige['ids']);
            foreach($ids as $id)
            {
                if(isset($items_id_map[$id]))
                {
                    $_guige[$id] = $items_id_map[$id];
                }
                
            }
            $guiges[] = $_guige;
        }
        
        return [
            'items'=>$items,
            'attrs'=>$guiges,
            'open'=>$goods['guige_open']?true:false
        ];

    }

    public function guige2DB($guige = '',$goods_id = 0)
    {
		if(!$guige || !$goods_id)
		{
			return;
		}
		$guige = is_array($guige)?$guige:json_decode($guige,true);

        if(!$guige['open'])
        {
            //关闭规格
            $this->goodsModel->where(['id'=>$goods_id])->update(['guige_open'=>0]);
        }

		if(!$guige['open'] || empty($guige['attrs']) || empty($guige['items']))
        {
            return;
        }

        $items_id_map = [];//id，name索引目录
       


        $has_item_ids = [];
        $itemModel = $this->itemModel;
        foreach($guige['items'] as $item)
        {
            if(!isset($item['items']) || empty($item['items']))
            {
                //子集为空没有属性值 跳过
                continue;
            }
            $item_id = 0;
            if(is_numeric($item['id']))
            {
                //如果是数字id表示已经插入数据库中
                $item_id = $item['id'];
                $itemModel->where(['id'=>$item['id']])->update(['name'=>$item['name']]);
            }else
            {
                //新增数据
                $item_id = $itemModel->insertGetId(['name'=>$item['name'],'goods_id'=>$goods_id]);
            }
            $items_id_map[$item['id']] = ['id'=>$item_id,'name'=>$item['name']];
            $has_item_ids[] = $item_id;

            foreach($item['items'] as $k=>$it)
            {
                if(is_numeric($it['id']))
                {
                    //如果是数字id表示已经插入数据库中
                    $item_2_id = $it['id'];
                    $itemModel->where(['id'=>$it['id']])->update(['name'=>$it['name'],'displayorder'=>$k]);
                }else
                {
                    //新增数据
                    $item_2_id = $itemModel->insertGetId(['name'=>$it['name'],'goods_id'=>$goods_id,'parent_id'=>$item_id,'displayorder'=>$k]);
                }
                $has_item_ids[] = $item_2_id;
                $items_id_map[$it['id']] = ['id'=>$item_2_id,'name'=>$it['name']];
            }
        }
        //将之前没有的id都删除
        $itemModel->whereNotIn('id',$has_item_ids)->where(['goods_id'=>$goods_id])->delete();


		$has_guige_ids = [];
		$guigeModel = $this->guigeModel;
		$price = $old_price = [];
		$sku = 0;
        //d($guige['attrs']);
        foreach($guige['attrs'] as $key=>$val)
        {
            $name = [];
            $ids = explode(':',$val['id']);
            $_ids = [];
            foreach($ids as $_id)
            {
                $_ids[] = $items_id_map[$_id]['id'];
                $name[] = $items_id_map[$_id]['name'];
            }
            $_ids = implode(':',$_ids);

			$has = $guigeModel->where(['ids'=>$_ids,'goods_id'=>$goods_id])->first();
			$update = [
				'desc'=>implode('-',$name),
                'ids'=>$_ids,
			];
            //字段通过设置写入
            foreach($this->columns as $col)
            {
                $ctype = $col['type'];
                $cname = $col['name'];
                $v = '';
                if($ctype == 'price')
                {
                    $v = bcmul($val[$cname]??0,100);//小数精度问题
                }elseif($ctype == 'int')
                {
                    $v = $val[$cname]??0;
                }else
                {
                    $v = $val[$cname]??'';
                }
                $update[$cname] = $v;
            }
            if(isset($val['titlepic']))
            {
                $update['titlepic'] = HelperService::uploadParse($val['titlepic']);
            }
			$sku += $update['sku']??0;
			$price[] = $update['price']??0;
			$old_price[] = $update['old_price']??0;
			if($has)
			{
				$has_guige_ids[] = $has['id'];
				$guigeModel->where(['id'=>$has['id']])->update($update);
			}else
			{
				$update['goods_id'] = $goods_id;
				$has_guige_ids[] = $guigeModel->insertGetId($update);
			}
        }
		//将没有的删除
		$guigeModel->whereNotIn('id',$has_guige_ids)->where(['goods_id'=>$goods_id])->delete();
		sort($price);
		rsort($old_price);
		//最小价格及最大原价
		$update = [
			'sku'=>$sku,
			'old_price'=>$old_price[0],
			'price'=>$price[0],
            'max_price'=>$price[count($price)-1],
            'guige_open'=>1
		];

		$this->goodsModel->where(['id'=>$goods_id])->update($update);
    }

}