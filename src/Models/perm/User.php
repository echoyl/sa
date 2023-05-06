<?php

namespace Echoyl\Sa\Models\perm;

use Echoyl\Sa\Models\BaseAuth;
use Laravel\Sanctum\PersonalAccessToken;

class User extends BaseAuth
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'perm_user';

    public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = [
			["name" => "roleid","type" => "select","default" => 0,"data" => (new Role())->get()->toArray(),'with'=>true],
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

    public function role()
    {
        return $this->hasOne(Role::class,'id','roleid');
    }


    public function logs()
    {
        return $this->hasMany(PersonalAccessToken::class,'tokenable_id','id')->where(['tokenable_type'=>'Echoyl\Sa\Models\perm\User']);
    }
}