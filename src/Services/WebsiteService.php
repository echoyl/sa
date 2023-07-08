<?php
namespace Echoyl\Sa\Services;

use App\Models\banlvit\Project;
use App\Models\banlvit\project\Category as ProjectCategory;
use Echoyl\Sa\Models\menu\Menu;
use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Models\Posts;

class WebsiteService
{
    public $modules = [
        ['id' => 'link', 'title' => '外链'],
        ['id' => 'page', 'title' => '单页'],
        ['id' => 'post', 'title' => '内容','url'=>['category'=>'category','posts'=>'posts/posts'],'type'=>'content'],
        ['id' => 'menu', 'title' => '菜单']
    ];

    public $modulesModel = [
        'post' => [
            Posts::class, Category::class,
        ],
        'project'=>[
            Project::class,ProjectCategory::class
        ]
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
            $data = $this->menuModel::where(['id' => $data])->first();
            if($data)
            {
                $data = $data->toArray();
            }
        }

        $content = [];

        if ($data) {
            $module = $data['module'];

            if (!in_array($module, ['link', 'page'])) {
                if ($data['pagetype'] == 'detail' && isset($this->modulesModel[$module])) {
                    [$modelName] = $this->modulesModel[$module];
                    $detail = (new $modelName)->where(['id' => $data['content_id']])->first();
                    $content = $detail ? [
                        'label' => $detail['title'],
                        'value' => $detail['id'],
                        'id' => $detail['id'],
                    ] : $content;
                }
            }
        }

        return $content;
    }

    public function webset()
    {
        $setservice = new SetsService();
        $key = implode('_',[env('APP_NAME','web'),'web']);
        $data = $setservice->get($key);
        //d($data);
        HelperService::deImagesOne($data, ['logo', 'logo2','brand_image', 'qrcode'], true);
        //HelperService::deImages($data, ['logo', 'logo2','brand_image', 'qrcode'], true);
        return $data;
    }

    public function getLinks()
    {
        $categorys = (new Category())->where(['parent_id' => $this->link_category_id, 'state' => 'enable'])->get()->toArray();
        $data = [];
        foreach ($categorys as $cate) {
            $data[] = [
                'title' => $cate['title'],
                'links' => (new Posts())->select(['id', 'title', 'small_title'])->whereRaw("FIND_IN_SET(?,category_id)", [$cate['id']])->where(['state' => 'enable'])->orderBy('displayorder', 'desc')->orderBy('id', 'desc')->get()->toArray(),
            ];
        }
        return $data;
    }

}
