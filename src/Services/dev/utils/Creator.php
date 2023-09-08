<?php
namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Http\Controllers\admin\dev\MenuController;
use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\creator\Perm;
use Echoyl\Sa\Services\dev\utils\creator\Posts;

class Creator
{

    public function postsContent()
    {
        $c = new Posts;
        $c->make();
        return;
    }  

    public function permContent()
    {
        $c = new Perm;
        $c->make();
        return;
    }

    public function getConfig($data,$search,$relpace)
    {
        if(is_string($data))
        {
            return str_replace($search,$relpace,$data);
        }
        foreach($data as $key=>$val)
        {
            $data[$key] = str_replace($search,$relpace,$val);
        }
        return $data;
    }

    

    public function updateMenuDesc($menu_id)
    {
        $ds = new DevService;
        $ds->allMenu(true);
        $ds->allModel(true);
        $mc = new MenuController(new Menu());
        $mc->tableConfig($menu_id);
        $mc->formConfig($menu_id);
        $mc->otherConfig($menu_id);
        return;
    }

    public function checkHas($model_data)
    {
        $model = new Model();
        $model_has = $model->where(['name'=>$model_data['name'],'parent_id'=>$model_data['parent_id'],'type'=>$model_data['type'],'admin_type'=>$model_data['admin_type']])->first();
        if(!$model_has)
        {
            $model_id = $model->insertGetId($model_data);
        }else
        {
            //更新
            $model->where(['id'=>$model_has['id']])->update($model_data);
            $model_id = $model_has['id'];
        }
        return $model_id;
    }
    public function checkHasMenu($model_data)
    {
        $model = new Menu();
        $model_has = $model->where(['path'=>$model_data['path'],'parent_id'=>$model_data['parent_id'],'type'=>$model_data['type']])->first();
        if(!$model_has)
        {
            $model_id = $model->insertGetId($model_data);
        }else
        {
            //更新
            $model->where(['id'=>$model_has['id']])->update($model_data);
            $model_id = $model_has['id'];
        }
        return $model_id;
    }
}