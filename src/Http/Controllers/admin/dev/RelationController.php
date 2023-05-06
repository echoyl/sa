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
    public function __construct(Relation $model)
    {
        $this->model = $model;
        $post_parent_id = request('model_id', 0);
        $this->model_id = $post_parent_id?:0;
        $this->default_post = [
            'model_id' => $this->model_id,
            //'displayorder' => 0,
        ];

        $this->parse_columns = [
            //['name' => 'parent_id', 'type' => '', 'default' => $this->cid],
            //['name' => 'category_id', 'type' => 'cascader', 'default' => ''],
            
            ['name' => 'search_columns', 'type' => 'selects', 'default' => ''],
            ['name' => 'with_sum', 'type' => 'selects', 'default' => ''],
        ];

    }

    public function handleSearch()
    {
        $m = $this->model;
        $m = $m->where(['model_id'=>$this->model_id]);
        $ds = new DevService;
        $search = [
            'columns'=>$this->getModelColumns($this->model_id),
            'models'=>$ds->getModelsTree(),
            'allModels'=>$this->allModels(),
        ];
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
        $list = $model->where(['type'=>1])->whereIn('admin_type',['system',env('APP_NAME'),''])->get()->toArray();
        foreach($list as $val)
        {
            $data[] = [
                'id'=>$val['id'],
                'columns'=>$val['columns']?json_decode($val['columns'],true):[],
            ];
        }
        return $data;
    }

}
