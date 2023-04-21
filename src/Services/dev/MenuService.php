<?php
namespace Echoyl\Sa\Services\dev;

use Echoyl\Sa\Models\dev\Menu;
use stdClass;

class MenuService
{
    var $basePerms = [
        // ['label'=>'查看列表','value'=>'index'],
        // ['label'=>'查看详情','value'=>'show'],
        // ['label'=>'新增','value'=>'add'],
        // ['label'=>'修改','value'=>'edit'],
        // ['label'=>'删除','value'=>'destroy'],
        'index' => '列表',
        'show' => '详情',
        'add' => '新增',
        'edit' => '修改',
        'destroy' => '删除',
    ];

    public function get($id = 0)
    {
        $data = $this->getAll()->filter(function ($item) use ($id) {
            return $item->parent_id === $id && in_array($item->type,['system',env('APP_NAME'),'']) === true;
        });
        $ret = [];
        foreach ($data as $val) {
            $item = [
                'name' => $val['title'],
                'path' => $val['path'],
                'icon' => $val['icon'],
                "access" => 'routeFilter',
                'routes' => $this->get($val['id']),
                'data' => (new stdClass),
                'page_type'=>$val['page_type']
            ];
            if ($val['desc']) {
                $item['data'] = json_decode($val['desc'], true);
            }
            $ret[] = $item;
        }
        return $ret;
    }

    public function getAll()
    {
        static $data = [];
        if (empty($data)) {
            $data = (new Menu())->where(['state' => 'enable'])->where([['path','!=','']])->orderBy('displayorder', 'desc')->orderBy('id', 'asc')->get();
        }
        return $data;
    }

    public function categoryId($model = '')
    {
        $where = [
            'model' => $model,
            'path' => request('path'),
        ];
        $item = (new Menu())->where($where)->first();
        return $item ? $item['category_id'] : 0;
    }

    public function menu($where)
    {
        return (new Menu())->where($where)->first();
    }

    public function posts()
    {
        $where = [
            'model' => 'posts',
            'path' => request('path'),
        ];
        $menu = $this->menu($where);
        if($menu)
        {
            $menu['desc'] = $menu['desc']?json_decode($menu['desc'], true):[];
        }
        return $menu;
    }

    public function getAll2()
    {
        static $data = [];
        if (empty($data)) {
            $data = (new Menu())->where(['state' => 'enable'])->orderBy('displayorder', 'desc')->orderBy('id', 'asc')->get();
        }
        return $data;
    }

    public function perms($id = 0,$prefix = [])
    {
        $data = $this->getAll2()->filter(function ($item) use ($id) {
            return $item->parent_id === $id && in_array($item->type,['system',env('APP_NAME'),'']) === true;
        });
        $ret = [];
        foreach ($data as $val) {
            $routers = $this->perms($val['id']);
            $more = true;
            if(empty($routers))
            {
                //没有子集 渲染权限
                $menu_perms = [];
                if($val['perms'])
                {
                    //如果设置了子权限
                    $menu_perms = json_decode($val['perms'],true);
                }
                $routers = $this->getBasePerms($val['id'],$menu_perms,$val['admin_model_id']?true:false);
                $more = false;
            }
            $item = [
                'label' => $val['title'],
                'value' => strval($val['id']),
                'more'=>$more,
                'options' => $routers,
            ];
            $ret[] = $item;
        }
        return $ret;
    }

    public function getBasePerms($menu_id,$ext_perms = [],$add_ext = false)
    {
        $ret = [];
        //检测菜单是否映射到了模型，是： 基础 + 子权限 否：只使用子权限
        if($add_ext)
        {
            $perms = array_merge($this->basePerms,$ext_perms);
        }else
        {
            $perms = !empty($ext_perms)?$ext_perms:$this->basePerms;
        }
        
        foreach($perms as $key=>$val)
        {
            $value = implode('.',[$menu_id,$key]);
            $ret[] = ['label'=>$val,'value'=>$value];
        }
        return $ret;
    }

    public function checkPerm($router,$user_perms,$role_perms)
    {
        
        $perms = explode(',', $user_perms);
        $role_perms = explode(',', $role_perms);
        //获取角色权限和用户权限的交集
        $perms = array_intersect($role_perms, $perms);

        if (empty($perms)) {
            return false;
        }

        //通过路由找到 admin menu 数据
        [$name,$menu] = $this->getMenuByRouter($router);
        //d($name,$menu);
        //d(implode('.',[$menu['id'],$name]),$perms);
        if(in_array(implode('.',[$menu['id'],$name]),$perms))
        {
            return true;
        }else
        {
            return false;
        }
        
    }

    public function getMenuByRouter($router)
    {
        //将action清除下
        $keys = array_keys($this->basePerms);

        $r = explode('/',$router);
        
        $name = array_pop($r);
        $posts_child = $name;
        //d($router);
        if(in_array($name,$keys))
        {
            $router = implode('/',$r);
            $posts_child = end($r);
            $r[] = $name;
        }else
        {
            $r[] = $name;
            $name = 'index';
        }

        
        //如果是posts 内容模块
        if(request('path'))
        {
            $menu = $this->posts();
            if($menu)
            {
                //检测内容模块是否有子内容
                $child = (new Menu())->where(['path'=>$posts_child,'parent_id'=>$menu['id']])->first();
                return [$name,$child?:$menu];
            }
            
        }

        $menu = (new Menu())->where(['router'=>$router])->first();
        //如果直接指定了路由
        if($menu)
        {
            return [$name,$menu];
        }

        //通过路由切割来找到菜单
        //上面将action name 加入到数组 现在重新删除该name
        array_pop($r);
        if(!empty($r))
        {
            $r = array_reverse($r);
        }
        //d($r);

        $m = (new Menu())->where(['path'=>$r[0],'type'=>env('APP_NAME')]);
        //这里不知道怎么回事只做到了 3层菜单模式 应该写一个递归 无限级菜单读取
        if(isset($r[1]))
        {
            array_shift($r);
            //d($r);
            $m = $m->whereHas('parent',function($q) use($r){
                $q->where(['path'=>$r[0]]);
                if(isset($r[1]))
                {
                    array_shift($r);
                    $q->whereHas('parent',function($query) use($r){
                        $query->where(['path'=>$r[0]]);
                    });
                }
            });
            
        }
        $menu = $m->first();
        //d($name,$menu);
        return [$name,$menu?:['id'=>0]];
    }
    
}
