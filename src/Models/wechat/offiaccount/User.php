<?php
namespace Echoyl\Sa\Models\wechat\offiaccount;

use Echoyl\Sa\Models\BaseAuth;
use Echoyl\Sa\Models\wechat\miniprogram\User as MiniprogramUser;

class User extends BaseAuth
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_offiaccount_user';

    public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = [
			["name" => "app","type" => "model","class" => Account::class],
			["name" => "gender","type" => "select","default" => 0,"data" => [
			["label" => "未知","value" => 0],
			["label" => "男","value" => 1],
			["label" => "女","value" => 2],
		],"with" => true],
			["name" => "state","type" => "switch","default" => 1,"with" => true,"data" => [
			["label" => "禁用","value" => 0],
			["label" => "启用","value" => 1],
		],"table_menu" => true],
			["name" => "appid","type" => "select","default" => 0,"data" => (new Account())->get()->toArray(),"with" => true],
			["name" => "subscribe","type" => "switch","default" => 1],
		];
        }
        return $data;
    }

    //relationship start
    
    public function app()
    {
        return $this->hasOne(Account::class,'appid','appid');
    }

    public function miniprogramUser()
    {
        return $this->hasOne(MiniprogramUser::class,'unionid','unionid');
    }
    
    //relationship end
}