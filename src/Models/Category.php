<?php

namespace Echoyl\Sa\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'category';

    public function format($id = 0, $fields = ['id' => 'value', 'title' => 'label', 'children' => 'children'])
    {
        $data = self::allData($this->table)->filter(function ($item) use ($id) {
            return $item->parent_id === $id;
        });
        $ret = [];
        foreach ($data as $val) {
            $children = $this->format($val['id'], $fields);
            $item = [
                $fields['id'] => $val['id'],
                $fields['title'] => $val['title'],
                'parent_id' => $val['parent_id'],
                //$fields['children'] => $this->format($val['id'], $fields),
            ];
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
        static $ids = [];
        $data = $this->find($id);
        if (!$data) {
            $ids[] = 0;
        }
        $ids[] = $data['id'];
        if ($data['parent_id'] != 0) {
            $this->parentIds($data['parent_id']);
        }
        return array_reverse($ids);
    }
    public function parentInfo($id)
    {
        static $par = [];
        $data = $this->find($id);
        if ($data) {
            $par[] = ['id' => $data['id'], 'title' => $data['title']];
            if ($data['parent_id'] != 0) {
                $this->parentInfo($data['parent_id']);
            }
        }

        return array_reverse($par);
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
     * @return void
     */
    public function getChild($cid = 0, $where = [],$parseData = false,$max_level = 0,$level = 1)
    {
        $list = $this->where(['parent_id' => $cid])->where($where)->orderBy('displayorder', 'desc')->orderBy('id', 'asc')->get()->toArray();
        foreach ($list as $key => $val) {
            if($parseData)
            {
                $list[$key] = $parseData($val);
            }
            if($max_level == 0 || $max_level > $level)
            {
                $children = $this->getChild($val['id'], $where,$parseData,$max_level,$level+1);
                if (!empty($children)) {
                    $list[$key]['children'] = $children;
                }
            }
            
        }
        return $list;
    }
}
