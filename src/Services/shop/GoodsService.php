<?php
namespace Echoyl\Sa\Services\shop;

use Echoyl\Sa\Services\HelperService;

class GoodsService
{
    var $goodsModel;
    var $itemModel;
    var $guigeModel;
    public function __construct($goodsModel,$itemModel,$guigeModel)
    {
        $this->goodsModel = $goodsModel;
        $this->itemModel = $itemModel;
        $this->guigeModel = $guigeModel;
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
                    'name'=>$it['name']
                ];
                $items_id_map[$it['id']] = $it['name'];
            }
            $items[] = [
                'id'=>$item['id'],
                'name'=>$item['name'],
                'items'=>$_its
            ];
        }
        //d($items_id_map);

        $guiges = [];
        foreach($goods['guiges'] as $guige)
        {
            $_guige = [
                'id'=>$guige['ids'],
                'price'=>$guige['price']/100,
                'sku'=>$guige['sku'],
                'max'=>$guige['max'],
                'old_price'=>$guige['old_price']/100,
                'chengben_price'=>$guige['chengben_price']/100,
                'jiesuan_price'=>$guige['jiesuan_price']/100,
            ];
            if(isset($guige['titlepic']) && $guige['titlepic'])
            {
                $_guige['titlepic'] = HelperService::uploadParse($guige['titlepic'],false, ['p'=>'s']);
            }
            $ids = explode(':',$guige['ids']);
            foreach($ids as $id)
            {
                $_guige[$id] = $items_id_map[$id];
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

            foreach($item['items'] as $it)
            {
                if(is_numeric($it['id']))
                {
                    //如果是数字id表示已经插入数据库中
                    $item_2_id = $it['id'];
                    $itemModel->where(['id'=>$it['id']])->update(['name'=>$it['name']]);
                }else
                {
                    //新增数据
                    $item_2_id = $itemModel->insertGetId(['name'=>$it['name'],'goods_id'=>$goods_id,'parent_id'=>$item_id]);
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
                'price'=>intval(($val['price']??0)*100),
                'old_price'=>intval(($val['old_price']??0)*100),
                'jiesuan_price'=>intval(($val['jiesuan_price']??0)*100),
                'chengben_price'=>intval(($val['chengben_price']??0)*100),
                'sku'=>$val['sku']??0,
                'max'=>$val['max']??0,
			];
            if(isset($val['titlepic']))
            {
                $update['titlepic'] = HelperService::uploadParse($val['titlepic']);
            }
			$sku += $update['sku'];
			$price[] = $update['price'];
			$old_price[] = $update['old_price'];
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