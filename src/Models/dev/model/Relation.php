<?php
namespace Echoyl\Sa\Models\dev\model;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\Base;

class Relation extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'dev_model_relation';


    public function model()
    {
        return $this->hasOne(Model::class,'id','model_id');
    }

    public function foreignModel()
    {
        return $this->hasOne(Model::class,'id','foreign_model_id');
    }

}