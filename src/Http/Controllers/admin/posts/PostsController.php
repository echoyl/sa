<?php

namespace Echoyl\Sa\Http\Controllers\admin\posts;

use App\Services\WebsiteService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Models\Posts;
use Echoyl\Sa\Services\dev\MenuService;

class PostsController extends CrudController
{
    public $cid = 0;
    public $can_be_null_colunms = ['pics'];

    public $displayorder = [
        ['displayorder', 'desc'],
        ['id', 'desc'],
    ];
    public $spec_arr = [];

    public function __construct()
    {
        $this->model = new Posts();

        if (!$this->cid) {
            $as = new MenuService;
            $menu = $as->posts();
            $this->cid = $menu ? $menu['category_id'] : 0;
            $this->spec_arr = $menu ? ($menu['desc']['spec_arr'] ?? []) : [];
        }
        if (empty($this->spec_arr)) {
            $ws = new WebsiteService;
            $this->spec_arr = $ws->spec_arr;
        }

        $this->parse_columns = [
            ['name' => 'titlepic', 'type' => 'image', 'default' => ''],
            ['name' => 'pics', 'type' => 'image', 'default' => ''],
            ['name' => 'category_id', 'type' => 'selects', 'default' => [$this->cid], 'class' => Category::class],
            ['name' => 'state', 'default' => 1],
            ['name' => 'attachment', 'type' => 'image', 'default' => ''],
        ];

        $this->withs = [
            ['name' => 'category', 'class' => Category::class, 'cid' => $this->cid],
        ];
        $this->default_post = [
            'id' => 0,
            'specs' => '',
        ];
    }

    public function handleSearch()
    {
        $m = $this->model;

        $search = [];

        $cids = (new Category)->childrenIds($this->cid);
        $cids = array_unique($cids);
        //d($cids);
        //$name = 'category_id';
        if (!empty($cids)) {
            $m = $m->where(function ($q) use ($cids) {
                foreach ($cids as $cid) {
                    $q->orWhereRaw("FIND_IN_SET(?,category_id)", [$cid]);
                }
            });
        }

        $search['spec_arr'] = $this->spec_arr;
        return [$m, $search];
    }

    public function postData(&$data)
    {
        //$ws = new WebsiteService;
        $data['spec_arr'] = $this->spec_arr;
        //$data['specs'] = $data['specs'] ? json_decode($data['specs'], true) : [];
        return;
    }

    public function beforePost(&$data)
    {
        if (isset($data['specs'])) {
            //$data['specs'] = json_encode($data['specs']);
        }
    }
}
