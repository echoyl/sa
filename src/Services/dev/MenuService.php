<?php
namespace Echoyl\Sa\Services\dev;

use Echoyl\Sa\Models\dev\Menu;
use stdClass;

class MenuService
{
    /**
     * @var array 模型默认基础权限子集
     */
    var $basePerms = [
        // ['label'=>'查看列表','value'=>'index'],
        // ['label'=>'查看详情','value'=>'show'],
        // ['label'=>'新增','value'=>'add'],
        // ['label'=>'修改','value'=>'edit'],
        // ['label'=>'删除','value'=>'destroy'],
        'index' => '列表',
        'show' => '查看',
        'add' => '新增',
        'edit' => '修改',
        'destroy' => '删除',
    ];

    /**
     * @var array 表单类型基础权限子集
     */
    var $formBasePerms = [
        'show'=>'查看',
        'submit'=>'提交'
    ];

    /**
     * 获取后台菜单信息
     *
     * @param integer $id
     * @param boolean | array $auth_ids
     * @return void
     */
    public function getMenuData($id = 0,$auth_ids = false)
    {
        $data = $this->getAll()->filter(function ($item) use ($id,$auth_ids) {
            if($auth_ids)
            {
                return $item->parent_id === $id && in_array($item->type,['system',env('APP_NAME'),'']) === true && in_array($item->id,$auth_ids) === true;
            }else
            {
                return $item->parent_id === $id && in_array($item->type,['system',env('APP_NAME'),'']) === true;
            }
            
        });
        
        $ret = [];
        foreach ($data as $val) {
            $item = [
                'name' => $val['title'],
                'path' => $val['path'],
                'icon' => $val['icon'],
                "access" => 'routeFilter',
                'routes' => $this->getMenuData($val['id'],$auth_ids),
                //'data' => (new stdClass),
                'data'=>[],
                'page_type'=>$val['page_type'],
                'id'=>$val['id'],
                'model_id'=>$val['admin_model_id'],
                'schema'=>$val
            ];
            if($val['status'] == 0)
            {
                //将菜单隐藏
                $item['hideInMenu'] = true;
            }
            if ($val['desc']) {
                $item['data'] = json_decode($val['desc'], true);
            }
            if($val['open_type'])
            {
                $item['data']['openType'] = $val['open_type'];
            }
            $item['data']['addable'] = $val['addable']?true:false;
            $item['data']['editable'] = $val['editable']?true:false;
            $item['data']['deleteable'] = $val['deleteable']?true:false;
            $item['data']['setting'] = $val['setting']?json_decode($val['setting'], true):[];
            $ret[] = $item;
        }
        return $ret;
    }

    public function get($id = 0,$auth_ids = false)
    {
        $data = $this->getMenuData($id,$auth_ids);
        return $this->bigFirstMenu($data);
    }

    /**
     * 将菜单最外层大菜单默认设置一个跳转链接 兼容头部分割菜单模式
     *
     * @param [type] $menus
     * @return void
     */
    public function bigFirstMenu($menus)
    {
        foreach($menus as $key=>$bigmenu)
        {
            if(!empty($bigmenu['routes']))
            {
                $first = $this->getFirstChildPath($bigmenu);
                //将大菜单去取到的第一个有页面的子菜单的路径放入数据中，前端中转页面判断后跳转页面
                $bigmenu['data'] = [
                    'redirect'=>'/'. implode('/',$first['path'])
                ];
            }
            $menus[$key] = $bigmenu;
        }
        return $menus;
    }

    public function getFirstChildPath($menu)
    {
        $first_child = $menu['routes'][0];
        //d($children);
        if(!empty($first_child['routes']))
        {
            //d($this->getFirstChildPath($first_child['routes'],$first_child['path']));
            $c = $this->getFirstChildPath($first_child);
            $path = array_merge([$menu['path']],$c['path']);
            $name = array_merge([$menu['name']],$c['name']);
            return ['path'=>$path,'name'=>$name,'route'=>$c['route']];
        }else
        {
            $path = [
                'path'=>[$menu['path'],$first_child['path']],
                'name'=>[$menu['name'],$first_child['name']],
                'route'=>$first_child
            ];
        }
        return $path;
    }

    public function getAll()
    {
        static $data = [];
        if (empty($data)) {
            $data = (new Menu())->where(['state' => 1])->orderBy('displayorder', 'desc')->orderBy('id', 'asc')->get();
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
            $data = (new Menu())->where(['state' => 1])->orderBy('displayorder', 'desc')->orderBy('id', 'asc')->get();
        }
        return $data;
    }

    public function perms($id = 0,$enable_keys = [])
    {
        $data = $this->getAll2()->filter(function ($item) use ($id) {
            return $item->parent_id === $id && in_array($item->type,['system',env('APP_NAME'),'']) === true;
        })->toArray();
        $ret = [];
        $total = count($data);
        $disable_count = 0;
        foreach ($data as $val) {
            [$routers,$routers_disable] = $this->perms($val['id'],$enable_keys);
            //$more = true;
            if(empty($routers))
            {
                //没有子集 渲染权限
                
                if($val['page_type'] == 'panel')
                {
                    //面板类型只有自己的权限
                    $val['id'] = implode('.',[$val['id'],$val['path']]);
                    if(!empty($enable_keys) && !in_array($val['id'],$enable_keys))
                    {
                        $routers_disable = true;
                    }
                    
                    //d($val);
                }elseif($val['page_type'] == 'form')
                {
                    //form 类型使用 $formBasePerms
                    [$routers,$routers_disable] = $this->getFormBasePerms($val['id'],$enable_keys);
                }else
                {
                    $menu_perms = [];
                    if($val['perms'])
                    {
                        //如果设置了子权限
                        $menu_perms = json_decode($val['perms'],true);
                    }
                    [$routers,$routers_disable] = $this->getBasePerms($val['id'],$menu_perms,$val['admin_model_id']?true:false,$enable_keys);
                }
                
                //$more = false;
            }
            if($routers_disable)
            {
                $disable_count++;
            }
            $item = [
                'label' => $val['title'],
                'value' => strval($val['id']),
                //'more'=>$more,
                'options' => $routers,
            ];
            if($routers_disable)
            {
                $item['disabled'] = true;
            }
            
            $ret[] = $item;
        }
        return [$ret,$total && $total == $disable_count];
    }

    public function getFormBasePerms($menu_id,$enable_keys = [])
    {
        return $this->getPerms($this->formBasePerms,$menu_id,$enable_keys);
    }

    public function getBasePerms($menu_id,$ext_perms = [],$add_ext = false,$enable_keys = [])
    {
        //检测菜单是否映射到了模型，是： 基础 + 子权限 否：只使用子权限
        if($add_ext)
        {
            $perms = array_merge($this->basePerms,$ext_perms);
        }else
        {
            $perms = !empty($ext_perms)?$ext_perms:$this->basePerms;
        }
        return $this->getPerms($perms,$menu_id,$enable_keys);
    }

    public function getPerms($perms,$menu_id,$enable_keys = [])
    {
        $ret = [];
        $total = count($perms);
        $disable_count = 0;
        foreach($perms as $key=>$val)
        {
            $value = implode('.',[$menu_id,$key]);
            $item= ['label'=>$val,'value'=>$value];

            if(!empty($enable_keys) && !in_array($item['value'],$enable_keys))
            {
                $item['disableCheckbox'] = true;
                $item['disabled'] = true;
                $disable_count++;
            }

            $ret[] = $item;
        }
        return [$ret,$total == $disable_count];
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

        //上面将action name 加入到数组 现在重新删除该name 但是要获取这个name
        $name = array_pop($r);
        if(!empty($r))
        {
            $r = array_reverse($r);
        }

        //先搜索一遍是否有菜单 - 最后一项可能是 action name 也可能是真的path 如果菜单设置为是form类型的话
        $m = (new Menu())->where(['path'=>$name,'state'=>1])->whereIn('page_type',['form','panel'])->whereIn('type',[env('APP_NAME'),'system']);
        if(!empty($r))
        {
            $m = $this->searchParent($m,$r);
        }
        $form_menu = $m->first();
        if($form_menu)
        {
            //如果是form的话需要判断 请求是get或是post
            if($form_menu['page_type'] == 'form')
            {
                $name = request()->isMethod('POST')?'submit':'show';
            }
            return [$name,$form_menu];
        }
        

        //通过路由切割来找到菜单
        $m2 = (new Menu())->where(['path'=>$r[0]])->whereIn('type',[env('APP_NAME'),'system']);
        //d(['path'=>$r[0],'type'=>env('APP_NAME')]);
        //这里不知道怎么回事只做到了 3层菜单模式 应该写一个递归 无限级菜单读取
        if(isset($r[1]))
        {
            array_shift($r);
            $m2 = $this->searchParent($m2,$r);
            // $m = $m->whereHas('parent',function($q) use($r){
            //     $q->where(['path'=>$r[0]]);
            //     if(isset($r[1]))
            //     {
            //         array_shift($r);
            //         $q->whereHas('parent',function($query) use($r){
            //             $query->where(['path'=>$r[0]]);
            //         });
            //     }
            // });
            
        }
        $menu = $m2->first();
        //d($name,$menu);
        return [$name,$menu?:['id'=>0]];
    }

    public function searchParent($query,$path)
    {
        $query->where(['state'=>1])->whereHas('parent',function($q) use($path){
            $q->where(['path'=>$path[0]])->whereIn('type',[env('APP_NAME'),'system']);
            if(isset($path[1]))
            {
                array_shift($path);
                $q = $this->searchParent($q,$path);
            }
        });
        return $query;
    }

    public function getParentId($id)
    {
        $item = $this->getAll2()->first(function ($item) use ($id) {
            return $item->id == $id;
        });
        if($item)
        {
            $this_id = $item['id'];
            $parent_id = $this->getParentId($item['parent_id']);
            if($parent_id)
            {
                $parent_id[] = $this_id;
                return $parent_id;
            }else
            {
                return [$this_id];
            }
        }else
        {
            return false;
        }
    }
    
}
