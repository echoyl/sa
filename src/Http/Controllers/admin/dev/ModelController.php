<?php

namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Creator;
use Echoyl\Sa\Services\dev\utils\Dev;
use Echoyl\Sa\Services\dev\utils\Dump;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\HelperService;

class ModelController extends CrudController
{
    public $model;

    public $cid = 0;

    public $can_be_null_columns = ['search_columns'];

    public $with_column = ['relations.foreignModel'];

    public function __construct(Model $model)
    {
        parent::__construct();
        $this->model = $model;
        $post_parent_id = request('parent_id', 0);
        $this->default_post = [
            'parent_id' => $post_parent_id ?: $this->cid,
            'displayorder' => 0,
            'type' => 0,
            'leixing' => 'normal',
        ];
    }

    public function index()
    {
        // 修改获取分类模式 直接递归 查询数据库获取数据
        $search = [];
        // $ds = new DevService;
        $this->parseWiths($search);
        // $search['icons'] = (new Menu())->where([['icon','!=','']])->get()->pluck('icon');

        $table_menu_id = request('admin_type', DevService::appname());
        $q = $this->model;
        if ($table_menu_id != 'all') {
            $q = $q->where('admin_type', $table_menu_id);
        }

        $data = HelperService::getChildFromData($q->get()->toArray(), function ($item) {
            $this->parseData($item, 'decode', 'list');

            return $item;
        }, [['type', 'asc'], ['id', 'asc']]);

        $search['table_menu'] = [
            'admin_type' => Utils::packageTypes(),
        ];
        // d($data);

        // $search['foldermodels'] = $ds->getModelsFolderTree(); // 模型文件夹
        // $search['menus'] = $ds->getMenusTree(); // 增加快速创建内容模块 选择创建到菜单下
        // $search['models'] = $ds->getModelsTree(); // 可选模型

        return $this->list($data, count($data), $search);
    }

    public function copyToFolder()
    {
        $id = request('base.id');
        $toid = request('base.toid');
        // 当前模型
        $data = $this->model->where(['id' => $id])->with(['relations'])->first();
        $to_folder = (new Model)->where(['id' => $toid])->first();
        if (! $data) {
            return $this->fail([1, '数据错误']);
        }
        if ($to_folder) {
            if ($to_folder['type'] != 0) {
                return $this->fail([1, '所选文件夹类型错误']);
            }
        } else {
            $to_folder = [
                'id' => 0,
                'admin_type' => DevService::appname(),
            ];
        }

        $data = $data->toArray();
        $relations = $data['relations'];
        unset($data['id'],$data['relations']);
        $data['parent_id'] = $to_folder['id'];
        $data['title'] .= '-复制';
        $data['admin_type'] = $to_folder['admin_type'];

        $new_model_id = (new Model)->insertGetId($data);

        // 将该模型下的关联信息也复制进去
        foreach ($relations as $val) {
            unset($val['id']);
            $val['model_id'] = $new_model_id;
            (new Relation)->insert($val);
        }

        return $this->success('操作成功');

    }

    public function postData(&$item)
    {
        if (! $this->is_post) {
            $customer_columns = $this->service->modelCustomerColumns();
            if (! empty($customer_columns)) {
                $item['add_customer_columns'] = $customer_columns;
            }
        } else {
            $item = DevService::modelItem($item);
        }
    }

    /**
     * 提交前置操作 将多余的参数删掉
     *
     * @param [type] $data
     * @param  int  $id
     * @param  array  $item
     * @return void
     */
    public function beforePost(&$data, $id = 0, $item = [])
    {
        if (isset($data['afterPostOptions'])) {
            unset($data['afterPostOptions']);
        }
        if (isset($data['columns'])) {
            $data['columns'] = array_values(collect($data['columns'])->sortBy('name')->toArray());
        }

    }

    /**
     * 保存完后就直接创建 文件
     *
     * @param [type] $id
     * @param [type] $data
     * @return void
     */
    public function afterPost($id, $data)
    {
        if ($data['type'] == 0) {
            // 文件夹直接跳过
            return;
        }

        if (env('APP_ENV') != 'local') {
            return; // 生产环境直接返回
        }

        $ds = new DevService;

        // 默认都重新更新数据表结构
        $afterPostOptions = request('base.afterPostOptions', ['createModelSchema']);

        // 生成文件异步访问 dev/formatFile 路由生成
        // $files = [];

        // $files[] = $ds->createControllerFile($data);

        // $files[] = $ds->createModelFile($data);

        // $ds->formatFile($files);

        $ds->modelColumn2Export($data);

        // 检测如果需要创建数据库表
        if (in_array('createModelSchema', $afterPostOptions)) {
            $ds->createModelSchema($data);
        }

        $this->clearCache();

    }

    public function clearCache()
    {
        $ds = new DevService;
        $ds->allMenu(true);
        $ds->allModel(true);

        return $this->success('success');
    }

    /**
     * 快速创建内容模块
     * 1.posts 2.category 3.posts_category 关联
     *
     * @return void
     */
    public function quickCreate()
    {
        if (env('APP_ENV') != 'local') {
            return $this->fail([1, '环境错误！']);
        }

        $c = new Creator;

        [$code,$msg] = $c->create(request('base.type'));

        if ($code) {
            return $this->fail([1, $msg]);
        }

        return $this->success('操作成功');
    }

    /**
     * 导出开发配置sql文件 直接在服务器中运行更新
     * 修改为导出json格式文件，做到导入导出同步线上线下的功能
     *
     * @return void
     */
    public function export($listData = false)
    {
        $c = new Dump;
        [$code,$msg] = $c->export(request('ids'));
        if ($code) {
            return $this->fail([1, $msg]);
        } else {
            return $this->success($msg);
        }
    }

    public function import()
    {
        $file = request()->file('file');

        $content = file_get_contents($file);

        $dump = new Dump;

        [$code,$msg] = $dump->import($content);

        if ($code) {
            return $this->fail([1, $msg]);
        } else {
            return $this->success(null, [0, $msg]);
        }
    }

    /**
     * 数据模型创建数据库
     *
     * @return void
     */
    public function createModelSchema($id = 0)
    {
        if (env('APP_ENV') != 'local') {
            return $this->fail([1, '环境错误！']);
        }
        $id = $id ?: request('id');
        $data = $this->model->where(['id' => $id])->first();
        if (! $data) {
            return $this->fail([1, '参数错误']);
        }
        $ds = new DevService;
        $ds->createModelSchema($data->toArray());

        return $this->success('操作成功');
    }

    public function createModelFile()
    {
        if (env('APP_ENV') != 'local') {
            return $this->fail([1, '环境错误！']);
        }
        $id = request('id');
        $data = $this->model->where(['id' => $id])->first();

        $ds = new DevService;

        $ds->createModelFile($data);
        $ds->createControllerFile($data);

        return $this->success('操作成功');
    }

    public function createControllerFile()
    {
        if (env('APP_ENV') != 'local') {
            return $this->fail([1, '环境错误！']);
        }
        $id = request('id');
        $data = $this->model->where(['id' => $id])->first();

        $ds = new DevService;

        $ds->createControllerFile($data);

        return $this->success('操作成功');
    }

    public function beforeDestroy($m)
    {
        $list = $m->get();
        foreach ($list as $val) {
            // 删除关系
            (new Relation)->where(['model_id' => $val['id']])->delete();
        }

        return $m;
    }

    public function getJsonFromTable()
    {
        $name = request('name');
        $parent_id = request('parent_id');

        $ds = new DevService;
        [$code,$ret] = $ds->tabel2SchemaJson($name, $parent_id);

        if ($code) {
            return $this->fail([$code, $ret]);
        }

        return $this->success($ret);
    }

    /**
     * 通过模型字段信息生成一个form插入数据的php $data 格式代码
     *
     * @return void
     */
    public function getFormCodeByColumns()
    {
        $item = $this->model->find(request('id'));
        if (! $item) {
            return $this->failMsg('参数错误');
        }
        $columns = $item->columns ? json_decode($item->columns, true) : [];
        $code = [];
        $required = $msg = $data_var = [];
        foreach ($columns as $col) {
            $name = $col['name'];
            if (strpos($name, 'id') !== false) {
                continue;
            }
            $title = $col['title'];
            $data_var[$name] = 'Arr::get($request,"'.$name.'"),//'.$title;
            $required[$name] = 'required';
            $msg[$name.'.required'] = $title.'为必填项';
        }
        $vali = '$validator = Validator::make($data,'.Dev::export($required, 0).','.Dev::export($msg, 0).');
if ($validator->fails()) 
{
    return $this->failMsg($validator->errors()->first());
}';
        $vali = str_replace(['\'required\''], ['[\'required\']'], $vali);
        $data_var = '$data = '.Dev::export($data_var, 0).';';
        // 替换 '$ 和 ',
        $data_var = str_replace(['\'Arr', '\','], ['Arr', ''], $data_var);

        $code = ['use Illuminate\Support\Arr;', 'use Illuminate\Support\Facades\Validator;', '$request = request()->all();', $data_var, $vali];

        $data = [
            'code' => implode("\n\r", $code),
        ];

        return $this->success($data);
    }
}
