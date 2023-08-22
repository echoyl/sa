<?php
namespace Echoyl\Sa\Services\shop;

use Illuminate\Support\Facades\DB;

class OrderService
{
    var $model;
    var $goodsModel;
    var $order_id;
    var $order;
    public function __construct($model,$goodsModel)
    {
        $this->model = new $model;
        $this->goodsModel = new $goodsModel;
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

}