<?php
namespace Echoyl\Sa\Services\shop;

use Illuminate\Support\Facades\DB;
use stdClass;

class OrderService
{
    var $model;
    var $goodsModel;
    var $order_id;
    var $order;
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    var $realGoodsModel;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    var $goodsGuigeModel;
    public function __construct($model,$goodsModel,$realGoodsModel = false,$goodsGuigeModel = false)
    {
        $this->model = new $model;
        $this->goodsModel = new $goodsModel;
        $this->realGoodsModel = $realGoodsModel?new $realGoodsModel:false;
        $this->goodsGuigeModel = $goodsGuigeModel?new $goodsGuigeModel:false;
    }

    public function getOrder($order_id)
    {
        $this->order_id = $order_id;
        $this->order = $this->model->where(['id'=>$order_id])->first();
    }


    public function calculateGoodsPrice($order_id)
    {
        $goods = $this->goodsModel->where(['order_id'=>$order_id])->select([DB::raw('sum(`num`*price) as total')])->get()->toArray();

        return $goods[0]['total'];
    }

    public function calculateTotalPrice($order_id)
    {
        $this->getOrder($order_id);
        $goods_price = $this->calculateGoodsPrice($order_id);
        if(!$this->order)
        {
            return 0;
        }
        return $this->order['yun_fee'] + $goods_price;
    }

    public static function createSn($length = 4,$prefix = '',$sufix = '')
    {
        $rand = [];
        for($i=0;$i<$length;$i++)
        {
            $rand[] = mt_rand(0,9);
        }
        return $prefix.date("YmdHis").(implode('',$rand)).$sufix;
    } 

    public function updateGoods($ordergoods,$order_id)
	{
		$ids = [];
		foreach($ordergoods as $og)
		{
			unset($og['goods']);
			if($og['id'])
			{
				//更新
				$this->goodsModel->where(['id'=>$og['id']])->update($og);
				$ids[] = $og['id'];
			}else
			{
				//插入
				$og['order_id'] = $order_id;
				$ids[] = $this->goodsModel->where(['id'=>$og['id']])->insertGetId($og);
			}
		}
		if($order_id)
		{
			//删除没有的
			$this->goodsModel->where(['order_id'=>$order_id])->whereNotIn('id',$ids)->forceDelete();
		}
		return;
	}
	public function getCreateGoods($posts)
	{
		$fake_goods = [];
		foreach($posts as $goodss)
		{
            $cg = $this->createGoods($goodss);
            if(is_string($cg))
            {
                return $cg;
            }
			$fake_goods[] = $cg;
		}
		return $fake_goods;
	}


	/**
	 * 生成订单商品数据 保证数据是保存至数据库的格式内容
	 *
	 * @param [type] $data
	 * @return void
	 */
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
		$ordergoods = [
			'goods_id'=>$data['goods']['id'],
			'id'=>$data['id']??0,
			'num'=>$data['num'] * 100,
			'goods'=>$data['goods']
		];
		if(isset($data['price']))
		{
			$ordergoods['price'] = $data['price'] * 100;
		}
		$ordergoods['goods_id'] = $data['goods']['id'];
		$goods_id = $ordergoods['goods_id'];
		unset($data['goods']);
		if(!empty($ids))
		{
			//选择了规格
			sort($ids);
			$guige = $this->goodsGuigeModel->where(['goods_id'=>$goods_id,'ids'=>implode(':',$ids)])->first();
			if(!$guige)
			{
				return '规格属性错误';
				
			}
			
			$ordergoods['price'] = $ordergoods['price']??$guige['price'];
			$ordergoods['guige'] = $guige['desc'];
			$ordergoods['guige_ids'] = $guige['ids'];
		}else
		{
			//无规格直接读取商品价格
			$goods = $this->realGoodsModel->with(['category'])->where(['id'=>$goods_id])->first();
			if(!$goods)
			{
				return '请选择商品';
			}
			$ordergoods['price'] = $ordergoods['price']??$goods['price'];
		}
		
		return $ordergoods;
	}

}