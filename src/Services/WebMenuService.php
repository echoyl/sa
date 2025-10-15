<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\web\Menu;
use App\Services\WeburlService;
use Echoyl\Sa\Services\dev\crud\CrudService;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\SetsService;
use Echoyl\Sa\Services\web\UrlService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class WebMenuService
{

    public static $modules_routes = [
        'post'=>['detail'=>'PostsController@detail','list'=>'PostsController@list'],
    ];

    /**
     * 获取导航菜单的banner图片
     *
     * @return void
     */

    public function menuBanner()
    {
        $menu = $this->getMenu();
        if (!$menu) {
            return;
        }
        //有菜单的话读取下 属于菜单的banner
        $banner = '';
        if ($menu['banner']) {
            $banner = $menu['banner'];
        } elseif ($menu['parent']) {
            if ($menu['parent']['banner']) {
                $banner = $menu['parent']['banner'];
            } elseif ($menu['parent']['parent'] && $menu['parent']['parent']['banner']) {
                $banner = $menu['parent']['parent']['banner'];
            }
        }
        return $banner;
    }

    /**
     * Undocumented function
     * 在这里注册后台菜单中填写的url别名
     * @return void
     */
    public static function aliasRoute($menus = [])
    {
        $menus = self::all();
        foreach ($menus as $val) {
            if (!$val['alias']) {
                continue;
            }

            //获取上级数据
            if ($val['parent_id']) {
                $val['alias'] = implode('/', array_reverse(self::getParentAlias($val, $menus)));
            }
            if($val['specs'])
            {
                $val['specs'] = HelperService::json_validate($val['specs']);
            }
            self::createRoute($val);
        }
        return;

    }

    /**
     * 获取上级菜单的别名
     *
     * @param [type] $val
     * @param [type] $menus
     * @return array
     */
    public static function getParentAlias($val, $menus)
    {
        if(!$val)
        {
            return [];
        }
        $alias = [$val['alias']];
        //d($parent);
        if ($val['parent_id']) {
            $parent = collect($menus)->filter(function ($item) use ($val) {
                return $item['id'] === $val['parent_id'];
            })->first();
            //$alias[] = $parent['alias'];
            $alias = array_merge($alias, self::getParentAlias($parent, $menus));
        }
        return $alias;
    }

    /**
     * 动态生成导航菜单的路由
     * 这个后面可能需要根据父级菜单的alias来追加生成
     * @param [type] $val menu
     * @param string $alias
     * @return void
     */
    public static function createRoute($val, $alias = '')
    {
        if (!$val['alias'] && !$alias) {
            return;
        }
        $alias = $alias ?: $val['alias'];

        if ($val['link']) {
            //外链跳过
            return;
        }

        //$alias = env('APP_PREFIX', '').$alias;

        switch ($val['module']) {
            case 'menu':
                //菜单类型 获取第一个子菜单
                $menus = self::all();
                $child = collect($menus)->filter(function ($item) use ($val) {
                    return $val['id'] === $item['parent_id'];
                })->first();
                if ($child) {
                    //self::createRoute($child,$alias);
                    Route::redirect($alias, implode('/', array_reverse(self::getParentAlias($child, $menus))));
                    //Log::channel('daily')->info('createRoute menu:',['name'=>$alias,'to'=>array_reverse(self::getParentAlias($child, $menus)),]);
                }
                break;
            case 'post':
                //post 即内容类型
                // if($val['id'] == 85)
                // {
                //     d($val);
                //     Log::channel('daily')->info('createRoute menu:',['val'=>$val]);
                // }
                if ($val['pagetype'] == 'list') {
                    //列表模块
                    Route::get($alias . '/{id}.html', self::$modules_routes[$val['module']]['detail']);
                    Route::get($alias . '/{cid}/{id}.html', self::$modules_routes[$val['module']]['detail'])->where('cid', '[0-9]+');
                    $is_single_category = Arr::get($val,'specs.is_single_category',0);
                    if(!$is_single_category)
                    {
                        //新增如果设定为单一分类列表则不在连接路由中加入分类id
                        Route::get($alias . '/{cid?}', self::$modules_routes[$val['module']]['list'])->where('cid', '[0-9]+');
                    }else
                    {
                        Route::get($alias . '/{id}', self::$modules_routes[$val['module']]['detail']);//因为不需要判断是详情还是列表可以追加一个不用html后缀的路由
                        Route::get($alias, self::$modules_routes[$val['module']]['list']);
                    }
                    
                    //Log::channel('daily')->info('createRoute post:',['name'=>$alias . '/{id}.html','to'=>self::$modules_routes[$val['module']]['detail'],]);
                    //Log::channel('daily')->info('createRoute post:',['name'=>$alias . '/{cid}/{id}.html','to'=>self::$modules_routes[$val['module']]['detail'],]);
                    //Log::channel('daily')->info('createRoute post:',['name'=>$alias . '/{cid?}','to'=>self::$modules_routes[$val['module']]['list'],]);
                } else {
                    //单篇文章模块
                    Route::get($alias, self::$modules_routes[$val['module']]['detail']);
                    //Log::channel('daily')->info('createRoute post:',['name'=>$alias,'to'=>self::$modules_routes[$val['module']]['detail'],]);
                }
                break;
            case 'page':
                //单页类型
                Route::get($alias, 'MenuController@index');
                //Log::channel('daily')->info('createRoute page:',['name'=>$alias,'to'=>"MenuController@index",]);
                break;
            default:
                //其它模块不生成路由
        }
        return;
    }

    /**
     * 通过路由链接 获取这个导航菜单
     *
     * @param [array] $uri
     * @param boolean $menu
     * @return void
     */
    public function getByUri($uri, $menu = false)
    {
        //$uri = explode('/',$uri);
        if (empty($uri)) {
            return false;
        }

        $list = self::all();

        $alias = array_shift($uri);

        $data = [];

        $this_menu = collect($list)->first(function ($item) use ($alias, $menu) {
            if ($menu) {
                return $item['alias'] === $alias && $menu['id'] == $item['parent_id'];
            } else {
                return $item['alias'] === $alias && $item['parent_id'] == 0;
            }

        });

        if ($this_menu) {
            $this_menu['alias'] = implode('/', array_reverse(self::getParentAlias($this_menu, $list)));
        }

        $data = $this->getByUri($uri, $this_menu);
        $data = $data ?: $this_menu;

        return $data;

    }

    /**
     * 获取当前导航菜单
     *
     * @return void
     */
    public function getMenu($flag = false)
    {
        static $menu = [];

        if (!empty($menu)) {
            return $menu;
        }

        $mid = intval(request('mid', 0));
        if ($mid) {
            $menu = Menu::where(['id' => $mid])->with(['adminModel'])->first();
        }
        if (!$menu) {
            $uri = str_replace(env('APP_PREFIX', ''), '', request()->route()->uri);
            if($uri && $uri != '/')
            {
                $menu = $this->getByUri(explode('/', $uri));
            }
        }
        if ($menu) {
            if (!$flag) {
                $menu = $this->getMenuFromAllMenus($menu);
            }
            //如果菜单类型是 菜单的话
            //如果查找到数据的话 将数据中的 设置到request中
            request()->offsetSet('mid', $menu['id']);
            //request()->offsetSet('id',$has['content']);
            $id = request('id', 0); //如果已经有id的话 那么就不用再设置菜单中的设置
            if (!$id) {
                if ($menu['category_id']) {
                    request()->offsetSet('id', $menu['category_id']);
                } elseif ($menu['content_id']) {
                    request()->offsetSet('id', $menu['content_id']);
                }
            }

            //获取banner 往上推
            $menu['banner'] = $this->getBanner($menu);
            if($menu['specs'])
            {
                $menu['specs'] = $this->getSpecsNew($menu);
            }
            
            
        }
        

        return $menu;
    }

    public function getMenuFromAllMenus($menu, $all_menu = [])
    {
        $all_menu = !empty($all_menu) ? $all_menu : $this->getAll();
        $_menu = [];
        foreach ($all_menu as $val) {
            if ($val['id'] == $menu['id']) {
                $_menu = $val;
            } else {
                if (!empty($val['children'])) {
                    $_menu = $this->getMenuFromAllMenus($menu, $val['children']);
                }
            }

            if (!empty($_menu)) {
                return $_menu;
            }
        }
        return $_menu;
    }

    public function getBanner($menu)
    {
        if (empty($menu)) {
            return [];
        }

        HelperService::deImages($menu, ['banner'], true);

        if (!empty($menu['banner']) && $menu['banner'][0]['url']) {
            $banner = $menu['banner'];
        } else {
            $all = self::all();
            $pmenu = collect($all)->filter(function ($item) use ($menu) {
                return $item['id'] === $menu['parent_id'];
            })->first();
            $banner = $this->getBanner($pmenu);
        }
        return $banner;
    }

    public static function parseHref($menu)
    {
        $href = '';
        if ($menu['link']) {
            //外链权重最高放到最外面
            $href = $menu['link'];
        } else {
            //现在 alias 改为必填参数 故直接返回链接
            $us = new UrlService;

            $href = $us->url($menu['alias']);
        }
        return $href;
    }

    /**
     * 获取所有菜单信息
     * 这次只负责简单的菜单 不再读取分类中的类表之类的了
     * @param [type] $has_index 是否包含首页
     * @return array
     */
    public function getAll($index_name = '')
    {
        static $data = [];
        $key = $index_name?:'noindex';

        if (isset($data[$key]) && !empty($data[$key])) {
            return $data[$key];
        }
        $menu = $this->getMenu(true);

        $uri = str_replace(env('APP_PREFIX', ''), '', request()->route()->uri);
        $selected = $menu ? $menu['id'] : ($uri == '/'?0:-999);
        

        $list = self::format(0, self::all());

        if ($index_name) {
            $index_menu = [
                'id' => 0,
                'cid' => 0,
                'alias'=>'',
                'href' => '/',
                'title' => $index_name,
                'banner' => '',
                'selected' => $selected ? 0 : 1,
                'parent_id' => 0,
                'titlepic' => '',
                'blank' => 0,
                'top' => 1,
                'bottom' => 1,
                'children' => [],
                'content'=>'',
                'desc'=>'',
                'path'=>''
            ];
            array_unshift($list, $index_menu);
        }

        $list = collect($list)->map(function ($item) {
            $item['topchildren_count'] = collect($item['children'])->filter(fn($v) => $v['top'])->count();
            return $item;
        })->toArray();
        
        $list = self::selected($list, $selected); //选中菜单
        //d($list['selected'],$list['data'][3]);
        //d($list);
        //return $list['data'];
        $data[$key] = $list['data'];
        return $data[$key];
    }

    public static function checkCategoryDefaultFirst($cate)
    {
        if(isset($cate['category_default_first']) && !$cate['category_default_first'])
        {
            return false;
        }
        return true;
    }

    public static function selected($list, $mid, $top_selected = false)
    {
        $selected = false;
        $data = [];
        $cid = request('cid', -1);
        foreach ($list as $key => $val) {
            $real_select = false;

            //如果菜单被选中
            if ($val['id'] == $mid) {
                $val['selected'] = 1;
                $selected = true;
                $real_select = true;
            }

            //如果菜单已被选中 那么就不用再检测分类是否选中了
            if (isset($val['cid']) && !$real_select) {
                //如果是数据列表类型 数据分类下面的分类id相等 或者默认使第一个分类被选中
                //只有父级菜选中后 分类菜单才会选中
                if($top_selected)
                {
                    if ($cid == $val['cid'] || ($key == 0 && $cid == -1 && self::checkCategoryDefaultFirst($val))) {
                        $val['selected'] = 1;
                        $selected = true;
                    }
                }
            }

            if (isset($val['children']) && !empty($val['children'])) {
                $res = self::selected($val['children'], $mid, $real_select);
                if ($res['selected']) {
                    $val['selected'] = 1;
                    $selected = true;
                }
                $val['children'] = $res['data'];
            }
            $data[] = $val;
        }
        return ['data' => $data, 'selected' => $selected];
    }

    //读取同级菜单
    public static function siblings($menus = [], $menu = [])
    {
        $siblings = [];

        if (empty($menus)) {
            $ms = new self;
            $menus = $ms->getAll();
        }

        if (empty($menu)) {
            $ms = new self;
            $menu = $ms->getMenu();
        }

        //如果菜单是顶级菜单 - 且有子菜单 则返回自己的子菜单 而不是兄弟菜单了
        if ($menu['parent_id'] == 0) {
            if(!empty($menu['children']))
            {
                return $menu['children'];
            }else
            {
                return [$menu];
            }
            
        }

        foreach ($menus as $val) {
            if ($val['id'] == $menu['id']) {
                $siblings = $menus; //赋值
                return $siblings;
            } else {
                if (isset($val['children']) && !empty($val['children'])) {
                    $siblings = self::siblings($val['children'], $menu);
                    if (!empty($siblings)) {
                        return $siblings;
                    }
                }
            }

        }
        //var_dump($banner);exit;
        return $siblings;
    }

    /**
     * 面包屑
     * @staticvar array $banner_arr
     * @param type $menus
     * @param type $menu
     * @return array
     */
    public static function bread($menus = [])
    {
        if (empty($menus)) {
            $ms = new self;
            $menus = $ms->getAll();
        }
        $bread = [];
        foreach ($menus as $val) {
            if ($val['selected'] == 1) {
                if (isset($val['category_id']) && $val['category_id'] > 0) {
                    $val['cid'] = $val['category_id'];
                }
                $bread[] = $val;
                if (isset($val['children']) && !empty($val['children'])) {
                    $bread = array_merge($bread, self::bread($val['children']));
                }
            }

        }
        //var_dump($banner);exit;
        return $bread;
    }

    public static function all()
    {

        static $all = [];
        if (empty($all) && Schema::hasTable('web_menu')) {
            $all = Menu::where(['state' => '1'])->with(['adminModel'])->orderBy('parent_id', 'asc')->orderBy('displayorder', 'desc')->orderBy('id', 'asc')->get()->toArray();
        }
        return $all;
    }

    public static function format($id = 0, $s = [], $alias = '')
    {
        $data = collect($s)->filter(function ($item) use ($id) {
            return $item['parent_id'] === $id;
        })->sortByDesc('displayorder');
        $ret = [];
        $ms = new WebMenuService;
        foreach ($data as $val) {
            if ($alias) {
                $val['alias'] = $alias . '/' . $val['alias'];
            }
            $val['href'] = self::parseHref($val);
            $val['parsedBanner'] = HelperService::uploadParse($val['banner'],false);
            $val['parsedPics'] = HelperService::uploadParse($val['pics'],false);
            $val['parsedTitlepic'] = HelperService::uploadParse($val['titlepic'],false);
            $val['selected'] = 0;
            if(isset($val['specs']))
            {
                $val['specs_json'] = $val['specs']?json_decode($val['specs'],true):[];
                //$val['specs'] = $ms->getSpecs($val['specs']??'');
            }else
            {
                $val['specs_json'] = [];
            }
            $val['category'] = false;
            if(isset($val['category_id']) && $val['category_id'])
            {
                //将关联的分类信息读入
                if($val['admin_model'])
                {
                    $model = self::getModel($val['admin_model'],'category');
                    if($model)
                    {
                        $c = $model->where(['id'=>$val['category_id'],'state'=>1])->first();
                        if($c)
                        {
                            $c = $c->toArray();
                            HelperService::deImages($c,['titlepic']);
                            $val['category'] = $c;
                        }
                        
                    }
                }
                $val['cid'] = $val['category_id'];
            }
            //$val['cid'] = $val['category_id'];
            //获取子菜单逻辑 如果是内容模块且是列表类型,并且没有子菜单的情况下，那么读取相应的模型的分类数据
            $val['children'] = self::format($val['id'], $s, $val['alias']);
            if (empty($val['children'])) {
                $val['children'] = self::getModuleCategory($val);
                //如果是内容转菜单
                //$val['children'] = self::postsToMenu((new $ws->modulesModel[$val['module']][0])->where(['state'=>'1','category_id'=>$val['category_id']])->orderBy('displayorder','desc')->orderBy('id','desc')->get(),$val);
                
            }

            $ret[] = $val;
        }
        return $ret;
    }

    /**
     * 根据菜单关联的模型 获取 模型实例
     */
    public static function getModel($admin_model,$type = 'post')
    {
        $ds = new DevService;
        $namespace = $ds->getNamespace($admin_model);//这个是选中的模型
        
        //后台更新关联模型方法直接指向模型 不再选择文件夹
        if($type == 'category')
        {
            $namespaces = explode("\\",$namespace[2]);
            array_pop($namespaces);
            //$namespaces[] = $admin_model['name'];
            $namespaces[] = 'Category';
            $classname = implode("\\",$namespaces);
        }else
        {
            $classname = $namespace[2];
        }
        //$classname = $namespace[2];
        if(class_exists($classname))
        {
            return new $classname;
        }
        return false;
    }

    public static function getModuleCategory($val, $cid = false)
    {
        //获取子菜单逻辑 如果是内容模块且是列表类型,并且没有子菜单的情况下，那么读取相应的模型的分类数据
        $ws = new WebsiteService;
        $child_count = Menu::where(['parent_id' => $val['id']])->count();
        $children = [];
        if (!$child_count && $val['admin_model'] && $val['pagetype'] == 'list' && $val['category_all']) {
            $model = self::getModel($val['admin_model'],'category');
            if($model)
            {
                $children = self::categoryToMenu($model->getChild($val['category_id'], ['state' => '1']), $val, $cid);
            }
        }
        return $children;
    }

    /**
     * 文章内容转化成菜单
     */
    public static function postsToMenu($list,$menu)
    {
        $data = [];
        foreach($list as $val)
        {
            HelperService::deImages($val, ['titlepic'], true);
            $data[] = [
                'id' => -1,
                'cid' => $menu['category_id'],
                'href' => WeburlService::create($menu,$val['id']),
                'titlepic'=>$val['titlepic'],
                'title' => $val['title'],
                'icon'=>$val['icon'],
                'desc' => $val['desc'],
                'category'=>$menu,
                'selected' => 0,
                'top' => 1,
                'bottom' => 1,
                'blank' => 0,
                'category_menu'=>true
            ];
        }
        return $data;
    }

    public static function categoryToMenuData($category, $menu)
    {
        $category = HelperService::deImages($category, ['titlepic'], true);
        return array_merge($category,[
            'id' => -1,
            'cid' => $category['id'],
            'href' => self::parseHref($menu) . '/' . $category['id'],
            'titlepic'=>$category['titlepic'],
            'title' => $category['title'],
            'icon'=>$category['icon']??'',
            'desc' => $category['desc']??'',
            'category'=>$category,
            'selected' => 0,
            'top' => $menu['category_show_top'],
            'bottom' => $menu['category_show_bottom'],
            'blank' => 0,
            'category_default_first' => $menu['category_default_first']??false,
        ]);
    }

    public static function categoryToMenu($categorys, $topMenu, $cid = false)
    {
        $ret = [];
        $category = false;
        $selected = [];
        foreach ($categorys as $key => $cate) {
            $children = [];
            $has = false;
            $select = [];
            if (isset($cate['children'])) {
                if ($cid !== false) {
                    [$children, $has, $select] = self::categoryToMenu($cate['children'], $topMenu, $cid === 0 ? -1 : $cid);
                } else {
                    $children = self::categoryToMenu($cate['children'], $topMenu, $cid === 0 ? -1 : $cid);
                }
            }

            $new_cate = self::categoryToMenuData($cate, $topMenu);
            if ($cid == $cate['id'] || ($cid === 0 && $key == 0  && self::checkCategoryDefaultFirst($cate))) {
                $new_cate['selected'] = 1;
                $has = $new_cate;
            }
            if ($has) {
                $new_cate['selected'] = 1;
                $category = $has;
                $selected = array_merge([$new_cate], $select);
            }

            $new_cate['children'] = $children;

            $ret[] = $new_cate;
        }
        if ($cid !== false) {
            return [$ret, $category, $selected];
        } else {
            return $ret;
        }

    }

    public function seo($detail = false)
    {
        $ss = new SetsService;
        $seo = $ss->getWeb();
        //计算seo 的 title description keyword之类的
        $menu = $this->getMenu();
        //预设值防止未设置值报错
        $seo['seotitle'] = $seo['seotitle'] ?? $seo['name'];
        $seo['seokeywords'] = $seo['seokeywords'] ?? $seo['name'];
        $seo['seodescription'] = $seo['seodescription'] ?? $seo['name'];
        
        //d(caInfo('controller'));
        if (!empty($menu) && $seo) {
            $seo['seotitle'] = $menu['title'] . ',' . $seo['seotitle'];
            $seo['seokeywords'] = $menu['title'] . ',' . $seo['seokeywords']??'';

            if ($detail) {
                //如果有数据 则查看这个是属于哪个类目的
                $seo['seotitle'] = $detail['title'] . ',' . $seo['seotitle'];
                $seo['seokeywords'] = $detail['title'] . ',' . $seo['seokeywords'];
            }
        }else
        {
            if($seo)
            {
                $seo = [
                    'seotitle'=>($seo['seotitle']??'').' 首页',
                    'seokeywords'=>$seo['seokeywords']??'',
                    'seodescription'=>$seo['seokeywords']??'',
                ];
            }else
            {
                $seo = [
                    'seotitle'=>'首页',
                    'seokeywords'=>'',
                    'seodescription'=>'',
                ];
            }
            
        }

        return $seo;
    }

    public function getSelectedCid($menu = [])
    {
        $cids = [];
        if (empty($menu)) {
            $menu = $this->getMenu();
        }
        if (isset($menu['cid']) && $menu['cid'] && $menu['selected']) {
            $cids[] = $menu['cid'];
        }
        if (!empty($menu['children'])) {
            foreach ($menu['children'] as $val) {
                $b = $this->getSelectedCid($val);
                if(!empty($b))
                {
                    $cids = array_merge($cids, $b);
                    break;
                }
            }

        }
        return $cids;
    }

    /**
     * 获取菜单中类型是jsonform的配置信息数据
     *
     * @param array $data 数据信息
     * @param string $key 数据的key
     * @param integer $dev_menu_id 菜单id
     * @return array
     */
    public function getSpecsNew($data,$key = 'specs',$dev_menu_id = 30)
    {
        $menu = Utils::getDevMenu($dev_menu_id);
        if($menu)
        {
            request()->offsetSet('dev_menu',$menu);
        }
        $config = [
            'data'=>$data,'col'=>['name'=>$key,'type'=>'config','default'=>''],
        ];
        $cs = new CrudService($config);
        //make后的图片数据变成了json需要重新转换一下
        $data = $cs->make('config',[
            'encode'=>false,
            'isset'=>isset($data[$key]),
        ]);
        return $data[$key];
    }

    public function getSpecs($_specs,$hasConfig = false)
    {
        $specs = [];
        if(!empty($_specs))
        {
            if(is_string($_specs))
            {
                $_specs = json_decode($_specs,true);
            }
            $more = [$_specs];
            HelperService::deImagesFromConfig($more);
            $_specs = $more[0];
            $specs = $_specs['value'];
            // foreach($menu['specs'] as $spec)
            // {
            //     $spec = HelperService::deImagesOne($spec, ['image'], true);
            //     $specs[$spec['key']] = $spec['image']['url']?:$spec['value'];
            // }

            //渲染图片地址
            $specs = $this->specsImgParse($_specs['config'],$_specs['value']);
            $_specs['value'] = $specs;
        }
        if($hasConfig)
        {
            
            return $_specs;
        }else
        {
            return $specs;
        }
        
    }

    public function specsImgParse($configs,$values,$is_array = false)
    {
        foreach($configs as $val)
        {
            if(isset($val['dataIndex']))
            {
                $key = $val['dataIndex'];
            }else
            {
                $key = '';
            }
            
            if(isset($val['valueType']) && $val['valueType'] == 'uploader')
            {
                if($is_array)
                {
                    foreach($values as $k=>$v)
                    {
                        HelperService::deImages($v,[$key]);
                        $values[$k] = $v;
                    }
                }else
                {
                    HelperService::deImages($values,[$key]);
                }
                
            }
            if(isset($val['valueType']) && ($val['valueType'] == 'formList' || $val['valueType'] == 'saFormList'))
            {
                if(isset($val['columns']) && !empty($val['columns']) && isset($values[$key]))
                {
                    $values[$key] = $this->specsImgParse($val['columns'][0]['columns'],$values[$key],true);
                }
            }
            if(isset($val['valueType']) && $val['valueType'] == 'group')
            {
                if(isset($val['columns']) && !empty($val['columns']))
                {
                    //d($this->specsImgParse($val['columns'],$values,false));
                    $values = array_merge($values,$this->specsImgParse($val['columns'],$values,false));
                    
                    //d($val['columns'],$values,$this->specsImgParse($val['columns'],$values,false));
                }
            }
            
        }
        return $values;
    }

    // public static function aliasTpl($menu)
    // {
    //     $path = Utils::getPath($menu, self::all(),'alias');
    //     return array_reverse($path);
    // }

}
