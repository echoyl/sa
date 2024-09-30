<?php
namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;

class RelationController extends CrudController
{
    public $model;
    public $model_id = 0;
    public $displayorder = [['id','asc']];
    public function __construct(Relation $model)
    {
        $this->model = $model;
        $post_parent_id = request('model_id', 0);
        $this->model_id = $post_parent_id?:0;
        $this->default_post = [
            'model_id' => $this->model_id,
            //'displayorder' => 0,
        ];

        $this->can_be_null_columns = ['with_default'];

        $this->parse_columns = [
            //['name' => 'parent_id', 'type' => '', 'default' => $this->cid],
            //['name' => 'category_id', 'type' => 'cascader', 'default' => ''],
            
            ['name' => 'search_columns', 'type' => 'selects', 'default' => ''],
            ['name' => 'with_sum', 'type' => 'selects', 'default' => ''],
            ['name' => 'select_columns', 'type' => 'selects', 'default' => ''],
            ['name' => 'in_page_select_columns', 'type' => 'selects', 'default' => ''],
            ['name' => 'with_default', 'type' => 'json', 'default' => ''],
            ['name' => 'filter', 'type' => 'json', 'default' => ''],
            ['name' => 'order_by', 'type' => 'json', 'default' => ''],
        ];

    }

    public function handleSearch($search = [])
    {
        $m = $this->model;
        $m = $m->where(['model_id'=>$this->model_id]);
        $ds = new DevService;
        $search = array_merge([
            'columns'=>$this->getModelColumns($this->model_id),
            'models'=>$ds->getModelsTree(),
            'allModels'=>$this->allModels(),
        ],$search);
        return [$m,$search];

    }

    public function beforePost(&$data,$id)
    {
        $has = $this->model->where(['model_id'=>$data['model_id'],'name'=>$data['name']]);
        if($id)
        {
            $has = $has->where([['id','!=',$id]]);
        }
        if($has->first())
        {
            return $this->fail([1,'关系已经存在不能重复添加']);
        }
    }

    public function postData(&$data)
    {
        //$data['columns'] = $this->getModelColumns($this->model_id);
        if(!request()->isMethod('post'))
        {
            $ds = new DevService;
            $data = array_merge($data,[
                //'columns'=>$this->getModelColumns($this->model_id),
                'models'=>$ds->getModelsTree(),
                'allModels'=>$this->allModels(),
            ]);
        }
        
        return;
    }

    protected function createFile($model_id)
    {
        $model = (new Model())->where(['id'=>$model_id])->first();

        if($model)
        {
            $ds = new DevService;

            $data = $model->toArray();

            $ds->createControllerFile($data);
    
            $ds->createModelFile($data);
        }

        return;
    }

    public function afterPost($id, $data)
    {
        return $this->createFile($data['model_id']);
    }

    public function getModelColumns($id)
    {
        $model_data = (new Model())->where(['id'=>$id])->first();
        $columns = [];
        if($model_data['columns'])
        {
            $columns = json_decode($model_data['columns'],true);
            $columns = collect($columns)->map(function($item){
                return ['label'=>implode(' - ',[$item['title'],$item['name']]),'value'=>$item['name']];
            });
        }
        return $columns;
    }

    public function allModels()
    {
        $model = new Model();
        $data = [];
        $list = $model->where(['type'=>1])->with(['relations'=>function($query){
            $query->select(['id','title','model_id','name','foreign_model_id'])->whereIn('type',['one','many']);
        }])->whereIn('admin_type',['system',env('APP_NAME'),''])->get()->toArray();
        foreach($list as $val)
        {
            $data[] = [
                'id'=>$val['id'],
                'columns'=>$val['columns']?json_decode($val['columns'],true):[],
                'relations'=>$val['relations']?:[]
            ];
        }
        return $data;
    }

    public function destroy()
    {
        $id = request('id', 0);
        if ($id) {
            if (!is_array($id)) {
                $id = [$id];
            }
        }
        if (!empty($id)) {
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
