<?php

namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;

class RelationController extends CrudController
{
    public $model;

    public $model_id = 0;

    public $displayorder = [['id', 'asc']];

    public function __construct(Relation $model)
    {
        $this->model = $model;
        $post_parent_id = request('model_id', 0);
        $this->model_id = $post_parent_id ?: 0;
        $this->default_post = [
            'model_id' => $this->model_id,
            'is_with' => 0,
            // 'displayorder' => 0,
        ];

        $this->can_be_null_columns = ['with_default'];
    }

    public function handleSearch($search = [])
    {
        $m = $this->model;
        $m = $m->where(['model_id' => $this->model_id]);

        // $ds = new DevService;
        // $search = array_merge([
        //     'models'=>$ds->getModelsTree(),
        // ],$search);
        return [$m, $search];

    }

    public function beforePost(&$data, $id)
    {
        $has = $this->model->where(['model_id' => $data['model_id'], 'name' => $data['name']]);
        if ($id) {
            $has = $has->where([['id', '!=', $id]]);
        }
        if ($has->first()) {
            return $this->fail([1, '关系已经存在不能重复添加']);
        }
    }

    public function postData(&$data)
    {
        // if(!request()->isMethod('post'))
        // {
        //     $ds = new DevService;
        //     $data = array_merge($data,[
        //         'models'=>$ds->getModelsTree(),
        //     ]);
        // }

    }

    public function copyToModel()
    {
        if (! request()->isMethod('post')) {
            return $this->success([]);
        }
        $id = request('base.id');
        $toid = request('base.toid');
        $type = request('base.type', 'create'); // 复制方式 create 插入如果已存在则跳过 update更新如果已存在则更新 copy复制如果已存在则复制
        // 当前模型
        $data = $this->model->where(['id' => $id])->first();
        $to_model = (new Model)->where(['id' => $toid])->first();
        if (! $data || ! $to_model) {
            return $this->fail([1, '数据错误']);
        }
        if ($to_model['type'] != 1) {
            return $this->fail([1, '所选模型类型错误']);
        }

        $data = $data->toArray();
        unset($data['id']);
        $data['model_id'] = $to_model['id'];

        // 检测是否已存在关联
        $model = $this->getModel();
        $has = $model->where(['name' => $to_model['name'], 'model_id' => $to_model['id'], 'foreign_model_id' => $data['foreign_model_id']]);
        if ($has->first()) {
            if ($type == 'update') {
                $has->update($data);
            } elseif ($type == 'copy') {
                $data['name'] = $data['name'].'copy';
                $has->insert($data);
            } else {
                return $this->fail([1, '关系已经存在不能重复添加']);
            }
        } else {
            $model->insert($data);
        }

        $this->createFile($to_model['id']);

        return $this->success('操作成功');

    }

    protected function createFile($model_id)
    {
        $model = (new Model)->where(['id' => $model_id])->first();

        if ($model) {
            $ds = new DevService;

            $data = $model->toArray();

            $ds->createControllerFile($data);

            $ds->createModelFile($data);
        }

    }

    public function afterPost($id, $data)
    {
        // return $this->createFile($data['model_id']);
    }

    public function destroy()
    {
        $id = request('id', 0);
        if ($id) {
            if (! is_array($id)) {
                $id = [$id];
            }
        }
        if (! empty($id)) {
            $m = $this->beforeDestroy($this->model->whereIn('id', $id));

            $items = $m->get();
            foreach ($items as $val) {
                $model_id = $val['model_id'];
                $val->delete();
                $this->createFile($model_id);
            }

            return ['code' => 0, 'msg' => '删除成功'];
        }

        return ['code' => 1, 'msg' => '参数错误'];
    }
}
