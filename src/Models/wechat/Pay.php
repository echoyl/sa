<?php
namespace Echoyl\Sa\Models\wechat;

use Echoyl\Sa\Models\Base;

class Pay extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_pay';

    public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = [
			["name" => "state","type" => "switch","default" => 1,"with" => true,"data" => [
			["label" => "禁用","value" => 0],
			["label" => "启用","value" => 1],
		],"table_menu" => true],
		];
        }
        return $data;
    }

    //relationship start
    
    
    //relationship end
}