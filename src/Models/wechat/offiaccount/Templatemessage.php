<?php
namespace Echoyl\Sa\Models\wechat\offiaccount;
use Echoyl\Sa\Models\Base;

//customer namespace start

//customer namespace end

class Templatemessage extends Base
{
    
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_offiaccount_templatemessage';

    /**
     * 在模型表中的id
     *
     * @var int
     */
    public $model_id = 10052;

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
			        'name' => 'template',
			        'type' => 'model',
			        'class' => Template::class,
			        'foreign_key' => 'template_id',
			        'setting' => [],
			    ],
			    [
			        'name' => 'app',
			        'type' => 'model',
			        'class' => Account::class,
			        'foreign_key' => 'appid',
			        'setting' => [],
			    ],
			    [
			        'name' => 'app_id',
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
			        'name' => 'app_param',
			        'type' => 'json',
			        'default' => '',
			    ],
			    [
			        'name' => 'data',
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
			    [
			        'name' => 'template_id',
			        'type' => 'searchSelect',
			        'default' => 0,
			        'data_name' => 'template',
			        'label' => 'title',
			        'value' => 'template_id',
			    ],
			];
        }
        return $data;
    }

    //relationship start
    
    public function template()
    {
        return $this->hasOne(Template::class,'template_id','template_id');
    }

    public function app()
    {
        return $this->hasOne(Account::class,'appid','app_id');
    }
    
    //relationship end

    //customer code start
	
	//customer code end
    
}