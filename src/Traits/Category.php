<?php

namespace Echoyl\Sa\Traits;

use Echoyl\Sa\Services\HelperService;

trait Category
{
    public function beforePost(&$data, $id = 0, $item = [])
    {
        $cid = request('cid', $this->cid);

        if (! isset($data['parent_id']) || ! $data['parent_id']) {
            // 未设置或者无父级id
            if ($cid) {
                // 菜单设置了父级id 使用菜单的父级id
                $data['parent_id'] = is_array($cid) ? array_pop($cid) : $cid;
            }
        }
        if (method_exists($this, 'getMaxDisplayorder') && $this->action_type == 'add') {
            // 如果使用了dragSort 后每次添加数据读取最大的displayorder + 1
            $data['displayorder'] = $this->getMaxDisplayorder();
        }
    }

    public function getListMaxLevel()
    {
        return property_exists($this, 'list_max_level') ? $this->list_max_level : 0;
    }

    public function index()
    {
        // 修改获取分类模式 直接递归 查询数据库获取数据

        // return ['code'=>0,'msg'=>'','data'=>$this->model->getChild($this->cid)];
        $search = [];
        $this->parseWiths($search);
        // 由于先执行构造函数再执行中间件检测权限导致 无法在构造函数中获取中间件中设置的参数信息，导致这里要手动设置
        $cid = request('cid', 0);
        // d($cid);
        $this->cid = $cid ? (is_array($cid) ? array_pop($cid) : $cid) : $this->cid;
        $displayorder = $this->displayorder;
        $sort_type = ['descend' => 'desc', 'ascend' => 'asc'];
        if (request('sort')) {
            // 添加排序检测
            $sort = json_decode(request('sort'), true);
            if (! empty($sort)) {
                foreach ($sort as $skey => $sval) {
                    array_unshift($displayorder, [$skey, $sort_type[$sval] ?? 'desc']);
                }
            }
        }
        $m = $this->getModel();
        if (empty($displayorder)) {
            $displayorder[] = ['displayorder', 'desc'];
        }
        if (! empty($this->select_columns)) {
            $m = $m->select($this->select_columns);
        }

        $m = $this->defaultSearch($m);

        $data = HelperService::getChildFromData($m->get()->toArray(), function ($item) {
            $this->parseData($item, 'decode', 'list');

            return $item;
        }, $displayorder, $this->cid, 'parent_id', $this->getListMaxLevel());

        return $this->list($data, count($data), $search);
    }
}
