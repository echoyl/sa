<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\web\Menu;
use Echoyl\Sa\Services\web\PostsService;
use Echoyl\Sa\Services\web\UrlService;

class WebsiteService
{
    public $modules = [
        ['id' => 'link', 'title' => '外链'],
        ['id' => 'page', 'title' => '单页'],
        ['id' => 'post', 'title' => '内容','type'=>'content'],
        ['id' => 'menu', 'title' => '菜单']
    ];

    public $link_category_id = 9;

    public $spec_arr = [
        
        [
            'title' => '名称',
            'width' => 'sm',
            'dataIndex' => 'key',
            'valueType' => 'textarea',
            'formItemProps' => [
                'rules' => [
                    [
                        'required' => true,
                        'message' => '此项为必填项',
                    ],
                ],
            ],
        ],
        [
            'title' => '值',
            'width' => 'sm',
            'dataIndex' => 'value',
            'valueType' => 'textarea',
            // 'formItemProps' => [
            //     'rules' => [
            //         [
            //             'required' => true,
            //             'message' => '此项为必填项',
            //         ],
            //     ],
            // ],
        ],
        [
            'title' => '上传图片',
            'width' => 'sm',
            'dataIndex' => 'image',
            'valueType' => 'uploader',
            'fieldProps' => [
                'name' => 'image',
                'max' => 1,
            ]
        ],
    ];

    public $banner_spec_arr = [
        [
            'title' => '上传图片',
            'width' => 'sm',
            'dataIndex' => 'image',
            'valueType' => 'uploader',
            'fieldProps' => [
                'name' => 'image',
                'max' => 1,
            ],
            'formItemProps' => [
                'rules' => [
                    [
                        'required' => true,
                        'message' => '此项为必填项',
                    ],
                ],
            ],
        ],
        [
            'title' => '标题',
            'width' => 'sm',
            'dataIndex' => 'text1',
        ],
        [
            'title' => '链接',
            'width' => 'sm',
            'dataIndex' => 'href',
        ],

    ];

    public $fanwei_spec_arr = [
        [
            'title' => '上传图片',
            'width' => 'sm',
            'dataIndex' => 'image',
            'valueType' => 'uploader',
            'fieldProps' => [
                'name' => 'image',
                'max' => 1,
            ],
            'formItemProps' => [
                'rules' => [
                    [
                        'required' => true,
                        'message' => '此项为必填项',
                    ],
                ],
            ],
        ],
        [
            'title' => '中文',
            'width' => 'sm',
            'dataIndex' => 'cn',
            'formItemProps' => [
                'rules' => [
                    [
                        'required' => true,
                        'message' => '此项为必填项',
                    ],
                ],
            ],
        ],
        [
            'title' => '英文',
            'width' => 'sm',
            'dataIndex' => 'en',
            'formItemProps' => [
                'rules' => [
                    [
                        'required' => true,
                        'message' => '此项为必填项',
                    ],
                ],
            ],
        ],

    ];

    public $menuModel = Menu::class;

    public function menuContent($data = 0)
    {
        if (is_numeric($data)) {
            $data = $this->menuModel::where(['id' => $data])->with(['adminModel'])->first();
            if($data)
            {
                $data = $data->toArray();
            }
        }

        $content = [];

        if ($data) {
            $module = $data['module'];

            if (!in_array($module, ['link', 'page'])) {
                if ($data['pagetype'] == 'detail' && $data['admin_model']) {
                    $model = WebMenuService::getModel($data['admin_model']);
                    if($model)
                    {
                        $detail = $model->where(['id' => $data['content_id']])->first();
                        $content = $detail ? [
                            'label' => $detail['title'],
                            'value' => $detail['id'],
                            'id' => $detail['id'],
                        ] : $content;
                    }
                    
                }
            }
        }

        return $content;
    }

    public function webset()
    {
        $setservice = new SetsService();
        $data = $setservice->getWeb();
        // HelperService::deImagesFromConfig($data);
        // $data = HelperService::autoParseImages($data);
        return $data;
    }

    public function getData($menu_id, $category_ids = [], $limit = 1, $withCategory = false,$where = [])
    {
        $menu = (new Menu())->where(['id' => $menu_id])->with(['adminModel'])->first();

        //
        //$menu['href'] = MenuService::parseHref($menu);

        $ms = new WebMenuService;
        $menu = $ms->getMenuFromAllMenus($menu);
        if($menu_id == 21)
        {
            //d($menu);
        }
        //d($menu);
        //$menu['alias'] = implode('/', array_reverse(WebMenuService::getParentAlias($menu, WebMenuService::all())));
        //d($menu);
        $list = [];

        if ($menu && $menu['pagetype'] == 'list' && $menu['admin_model']) {
            $category_model = WebMenuService::getModel($menu['admin_model'],'category');
            if($category_model)
            {
                $id = $menu['category_id'];
                $ids = $category_model->childrenIds($id, $withCategory ? false : true);
                $cids = array_unique($ids);
                if (!empty($category_ids)) {
                    //传入分类id  取两个数组的交集
                    $cids = array_intersect($category_ids, $cids);
                }
                if ($withCategory) {
                    //获取分类信息 //只适用于1级 多级碰到再说
                    $categorys = $category_model->whereIn('id', $cids)->where(['state' => 1])->orderBy('displayorder', 'desc')->orderBy('id', 'asc')->get()->toArray();

                    foreach ($categorys as $category) {
                        //d($category,$menu);

                        $category = WebMenuService::categoryToMenuData($category, $menu);
                        $m = WebMenuService::getModel($menu['admin_model']);
                        if($m)
                        {
                            //d($where);
                            $data_list = $this->parseData($menu, $m->where($where)->whereRaw("FIND_IN_SET(?,category_id)", [$category['cid']]), $limit, $category['cid']);
                        }else
                        {
                            $data_list = [];
                        }
                        $list[] = [$category, $data_list];
                    }
                } else {
                    $m = WebMenuService::getModel($menu['admin_model']);
                    if($m)
                    {
                        $m = $m->where($where);
                        if (!empty($cids)) {
                        
                            $m = $m->where(function ($q) use ($cids) {
                                foreach ($cids as $cid) {
                                    $q->orWhereRaw("FIND_IN_SET(?,category_id)", [$cid]);
                                }
                            });
                        }
                        $list = $this->parseData($menu, $m, $limit);
                    }else
                    {
                        $list = [];
                    }
                    
                }
            }
            

        }

        return [$menu, !empty($list) && $limit == 1 ? $list[0] : $list];

    }

    public function parseData($menu, $m, $limit, $cid = 0)
    {
        $ps = new PostsService();
        $list = $m->where(['state' => 1])->orderBy('displayorder', 'desc')->orderBy('created_at', 'desc')->limit($limit)->get()->toArray();
        foreach ($list as $k => $val) {
            //$val = HelperService::autoParseImages($val);
            $val = HelperService::deImagesOne($val, ['titlepic']);
            //d($val);
            //$val['titlepic'] =
            $val['time_str'] = date("Y-m-d", strtotime($val['created_at']));

            $val['href'] = UrlService::create($menu, $val['id'], $cid,$val['link']??'');
            $val['desc_short'] = $this->shortDesc($val['desc']);
            //d($val['files']);
            $val['hits'] = $ps->hits($val['hits']??0);
            $list[$k] = $val;
        }
        return $list;
    }

    public function shortDesc($desc = '', $length = 70)
    {
        $ps = new PostsService();
        return $ps->shortDesc($desc,$length);
    }
}
