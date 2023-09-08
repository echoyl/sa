<?php

namespace Echoyl\Sa\Models;

use Echoyl\Sa\Models\BaseAuth;
use Laravel\Sanctum\PersonalAccessToken;

class User extends BaseAuth
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'user';

    public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = [
			[
                "name" => "state","type" => "switch","default" => 1,"with" => true,"data" => [
                    ["label" => "禁用","value" => 0],
                    ["label" => "启用","value" => 1],
                ],"table_menu" => true
            ],
		];
        }
        return $data;
    }

    public function logs()
    {
        return $this->hasMany(PersonalAccessToken::class,'tokenable_id','id')->where(['tokenable_type'=>'Echoyl\Sa\Models\User']);
    }
}