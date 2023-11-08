<?php

namespace Echoyl\Sa\Models;

class Category extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'category';

    public function format($id = 0, $fields = ['id' => 'value', 'title' => 'label', 'children' => 'children'],$parseData = false)
    {
        $data = self::allData($this->table)->filter(function ($item) use ($id) {
            return $item->parent_id === $id;
        })->sortByDesc('displayorder');
        $ret = [];
        foreach ($data as $val) {
            $children = $this->format($val['id'], $fields,$parseData);
            if($parseData)
            {
                $item = $parseData($val);
            }else
            {
                $item = [
                    $fields['id'] => $val['id'],
                    $fields['title'] => $val['title'],
                    'parent_id' => $val['parent_id'],
                    //$fields['children'] => $this->format($val['id'], $fields),
                ];
            }
            
            if(!empty($children))
            {
                $item[$fields['children']] = $children;
            }
            $ret[] = $item;
        }
        return $ret;
    }
    public static function allData($table)
    {
        static $all = [];
        if (!isset($all[$table]) || empty($all[$table])) {
            $all[$table] = self::all();
        }
        return $all[$table];
    }
    public function parentIds($id)
    {
        $data = $this->find($id);
        if (!$data) {
            return '';
        }
        $ids = [];
        if ($data['parent_id'] != 0) {
            $pids = $this->parentIds($data['parent_id']);
            if($pids)
            {
                foreach($pids as $pid)
                {
                    $ids[] = $pid;
                }
            }
        }
        $ids[] = $data['id'];
        return $ids;
    }
    public function parentInfo($id)
    {
        $par = [];
        $all = self::allData($this->table);
        $data = collect($all)->first(function($val) use($id){
            return $val['id'] == $id;
        });
        if ($data) {
            $par[] = ['id' => $data['id'], 'title' => $data['title']];
            if ($data['parent_id'] != 0) {
                $par = array_merge($par,$this->parentInfo($data['parent_id']));
            }
        }
        return $par;
    }
    public function childrenIds($id, $self = true)
    {
        //获取子类的所有id
        $ids = [];
        if (!$id) {
            //return [];
        }
        if ($self) {
            $ids[] = $id;
        }

        $children = self::allData($this->table)->filter(function ($user) use ($id) {
            return $user->parent_id == $id;
        });
        if ($children) {
            foreach ($children as $val) {
                $ids[] = $val['id'];
                $ids = array_merge($ids, $this->childrenIds($val['id'], $self));
            }
        }
        return array_filter(array_unique($ids));
    }
    /**
     * 将数据全部取出后 循环获取自己的子集
     *
     * @param integer $parent_id
     * @return void
     */
    public function children($parent_id = 0)
    {
        $children = self::allData($this->table)->filter(function ($user) use ($parent_id) {
            return $user->parent_id == $parent_id;
        });
        return $children;
    }

    /**
     * 循环通过读取数据库获取自己的子集
     *
     * @param [int] $cid
     * @return array
     */
    public function getChild($cid = 0, $where = [],$parseData = false,$max_level = 0,$level = 1,$displayorder = [])
    {
        if(empty($displayorder))
        {
            $displayorder = [['displayorder','desc'],['id','asc']];
        }
        if(is_array($cid))
        {
            $parent = $cid;
            $cid = $parent['id'];
        }else
        {
            $parent = false;
        }
        $list = $this->where(['parent_id' => $cid])->where($where);
        foreach($displayorder as $dis)
        {
            $list = $list->orderBy($dis[0],$dis[1]);
        }
        $list = $list->get()->toArray();
        foreach ($list as $key => $val) {
            if($parseData)
            {
                $list[$key] = $parseData($val,$parent);
            }
            if($max_level == 0 || $max_level > $level)
            {
                $children = $this->getChild($val, $where,$parseData,$max_level,$level+1,$displayorder);
                if (!empty($children)) {
                    $list[$key]['children'] = $children;
                }
            }
            
        }
        return $list;
    }
}
