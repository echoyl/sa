<?php
namespace Echoyl\Sa\Models\wechat\offiaccount;
use Echoyl\Sa\Models\Base;

//customer namespace start

//customer namespace end

class Template extends Base
{
    
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_offiaccount_template';

    /**
     * 在模型表中的id
     *
     * @var int
     */
    public $model_id = 10051;

    /**
     * 模型存在多语言的字段
     *
     * @var array
     */
    public $locale_columns = [];

    public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = [
			    [
			        'name' => 'account',
			        'type' => 'model',
			        'class' => Account::class,
			        'foreign_key' => 'appid',
			        'setting' => [],
			    ],
			    [
			        'name' => 'appid',
			        'type' => 'select',
			        'default' => 0,
			        'class' => Account::class,
			        'no_category' => true,
			        'columns' => [
			            'appname',
			            'appid',
			        ],
			        'with' => true,
			    ],
			    [
			        'name' => 'keys',
			        'type' => 'json',
			        'default' => '',
			    ],
			    [
			        'name' => 'state',
			        'type' => 'switch',
			        'default' => 1,
			        'table_menu' => true,
			        'with' => true,
			        'data' => [
			            [
			                'label' => '禁用',
			                'value' => 0,
			            ],
			            [
			                'label' => '启用',
			                'value' => 1,
			            ],
			        ],
			    ],
			];
        }
        return $data;
    }

    //relationship start
    
    public function account()
    {
        return $this->hasOne(Account::class,'appid','appid');
    }
    
    //relationship end

    //customer code start
	
	//customer code end
    
}