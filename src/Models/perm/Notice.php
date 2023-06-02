<?php
namespace Echoyl\Sa\Models\perm;

use Echoyl\Sa\Models\Base;

class Notice extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'perm_notice';

    public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = [];
        }
        return $data;
    }

    //relationship start
    
    
    //relationship end
}