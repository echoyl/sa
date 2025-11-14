<?php

namespace Echoyl\Sa\Services\shop;

use Echoyl\Sa\Services\dev\crud\ParseData;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

class GoodsService
{
    public $goodsModel;

    public $itemModel;

    public $guigeModel;

    public $columns;

    public function __construct($goodsModel, $itemModel, $guigeModel, $columns = [])
    {
        $this->goodsModel = $goodsModel;
        $this->itemModel = $itemModel;
        $this->guigeModel = $guigeModel;
        $this->columns = ! empty($columns) ? $columns : [
            ['name' => 'price', 'type' => 'price'],
            ['name' => 'old_price', 'type' => 'price'],
            ['name' => 'jiesuan_price', 'type' => 'price'],
            ['name' => 'chengben_price', 'type' => 'price'],
            ['name' => 'sku', 'type' => ''],
            ['name' => 'max', 'type' => ''],
        ];
    }

    public function parseGuige($goods, $admin = false)
    {
        if (! isset($goods['items']) || ! $goods['items']) {
            return [
                'items' => [],
                'attrs' => [],
                'open' => false,
            ];
        }
        $items = [];
        $items_id_map = [];
        foreach ($goods['items'] as $item) {
            if ($item['parent_id'] > 0) {
                continue;
            }
            $_its = [];
            foreach ($item['items'] as $it) {
                $it['displayorder'] = $it['displayorder'] ?? 0;
                if (isset($it['created_at'])) {
                    unset($it['created_at']);
                }
                if (isset($it['updated_at'])) {
                    unset($it['updated_at']);
                }
                if (isset($it['titlepic']) && $it['titlepic']) {
                    $it['titlepic'] = HelperService::uploadParse($it['titlepic'], false, ['p' => 'm']);
                }
                $_its[] = $it;
                $items_id_map[$it['id']] = $it['name'];
            }
            $_its = collect($_its)->sortBy('displayorder')->toArray();
            $item['items'] = array_values($_its);
            $items[] = $item;
        }
        // d($items);

        $guiges = [];
        foreach ($goods['guiges'] as $guige) {
            $_guige = [
                'id' => $guige['ids'],
            ];
            foreach ($this->columns as $col) {
                $ctype = $col['type'];
                $cname = $col['name'];
                $v = Arr::get($guige, $cname, $ctype == 'int' || $ctype == 'price' ? 0 : '');
                if ($ctype == 'price') {
                    // 后台调用的时候根据关联性质 已经处理一遍价格属性
                    $v = ! $admin ? $v / 100 : $v;
                }
                $_guige[$cname] = $v;
            }
            if (isset($guige['titlepic']) && $guige['titlepic']) {
                $_guige['titlepic'] = HelperService::uploadParse($guige['titlepic'], false, ['p' => 's']);
            }
            $ids = explode(':', $guige['ids']);
            foreach ($ids as $id) {
                if (isset($items_id_map[$id])) {
                    $_guige[$id] = $items_id_map[$id];
                }

            }
            $guiges[] = $_guige;
        }

        return [
            'items' => $items,
            'attrs' => $guiges,
            'open' => $goods['guige_open'] ? true : false,
        ];

    }

    public function item2DB($item, $more = [])
    {
        $itemClass = get_class($this->itemModel);
        $itemModel = $this->itemModel;
        $has = (new $itemClass)->where(['id' => $item['id']])->first();

        $update = array_merge($item, $more);
        $update['originData'] = $has;

        $update = filterEmpty($update);

        $ps = new ParseData($itemClass);
        $ps->make($update, 'encode', $has ? 'update' : 'detail');

        if (is_numeric($item['id'])) {
            // 如果是数字id表示已经插入数据库中
            $item_id = $item['id'];
            $itemModel->where(['id' => $item['id']])->update($update);
        } else {
            // 新增数据
            unset($update['id']);
            $item_id = $itemModel->insertGetId($update);
        }

        return ['id' => $item_id, 'name' => $item['name']];
    }

    public function guige2DB($guige = '', $goods_id = 0)
    {
        if (! $guige || ! $goods_id) {
            return;
        }
        $guige = is_array($guige) ? $guige : json_decode($guige, true);

        if (! $guige['open']) {
            // 关闭规格
            $this->goodsModel->where(['id' => $goods_id])->update(['guige_open' => 0]);
        }

        if (! $guige['open'] || empty($guige['attrs']) || empty($guige['items'])) {
            return;
        }

        $items_id_map = []; // id，name索引目录

        $has_item_ids = [];
        $itemModel = $this->itemModel;
        foreach ($guige['items'] as $key => $item) {
            if (! isset($item['items']) || empty($item['items'])) {
                // 子集为空没有属性值 跳过
                continue;
            }
            $id_map = $this->item2DB($item, ['displayorder' => $key, 'goods_id' => $goods_id]);
            $items_id_map[$item['id']] = $id_map;
            $item_id = $id_map['id'];
            $has_item_ids[] = $item_id;

            foreach ($item['items'] as $k => $it) {
                $id_map = $this->item2DB($it, ['displayorder' => $k, 'goods_id' => $goods_id, 'parent_id' => $item_id]);
                $has_item_ids[] = $id_map['id'];
                $items_id_map[$it['id']] = $id_map;
            }
        }
        // 将之前没有的id都删除
        $itemModel->whereNotIn('id', $has_item_ids)->where(['goods_id' => $goods_id])->delete();

        $has_guige_ids = [];
        $guigeModel = $this->guigeModel;
        $guigeClass = get_class($guigeModel);
        $price = $old_price = [];
        $sku = 0;
        // d($guige['attrs']);
        foreach ($guige['attrs'] as $key => $val) {
            $name = [];
            $ids = explode(':', $val['id']);
            unset($val['id']);
            $_ids = [];
            foreach ($ids as $_id) {
                $_ids[] = $items_id_map[$_id]['id'];
                $name[] = $items_id_map[$_id]['name'];
            }
            // 排序ids查找数据
            sort($_ids);
            $_ids = implode(':', $_ids);

            $has = $guigeModel->where(['ids' => $_ids, 'goods_id' => $goods_id])->first();
            $update = [
                'desc' => implode('-', $name),
                'ids' => $_ids,
            ];
            // 字段通过设置写入
            $update = array_merge($val, $update);

            $sku += $update['sku'] ?? 0;

            $update['originData'] = $has;

            $ps = new ParseData($guigeClass);
            $ps->make($update, 'encode', $has ? 'update' : 'detail');

            $price[] = $update['price'] ?? 0;
            if (isset($update['old_price'])) {
                $old_price[] = $update['old_price'];
            }

            if ($has) {
                $has_guige_ids[] = $has['id'];
                $guigeModel->where(['id' => $has['id']])->update($update);
            } else {
                $update['goods_id'] = $goods_id;
                $has_guige_ids[] = $guigeModel->insertGetId($update);
            }
        }
        // 将没有的删除
        $guigeModel->whereNotIn('id', $has_guige_ids)->where(['goods_id' => $goods_id])->delete();
        sort($price);
        // 最小价格及最大原价
        $update = [
            'sku' => $sku,
            'price' => $price[0],
            'max_price' => $price[count($price) - 1],
            'guige_open' => 1,
        ];
        if (! empty($old_price)) {
            rsort($old_price);
            $update['old_price'] = $old_price[0];
        }

        $this->goodsModel->where(['id' => $goods_id])->update($update);
    }
}
