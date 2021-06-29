<?php

namespace Echoyl\Sa\Http\Controllers\admin;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ToolController extends Controller
{
    var $msg = [];
    public function __construct()
    {
        //parent::__construct();
        $this->path_prefix = [
            'html'=>public_path('myadmin/dist/views'),
            'controller'=>app_path('Http/Controllers/admin'),
            'model'=>app_path('Models')
        ];
        $this->path_source = __DIR__.'/../../../../file/';
    }

    public function create()
    {
        return ['code'=>1,'msg'=>'false'];
    }

    public function show()
    {
        $cols = [
            [
				"name"=>"name",
				"title"=>"字段名",
				"type"=>"input",
				"required"=>1,
				"placeholder"=>"数据库字段名称",
				"width"=>"150px"
			],
            [
				"name"=>"desc",
				"title"=>"描述",
				"type"=>"input",
				"required"=>1,
				"placeholder"=>"字段描述",
				"width"=>"150px"
			],
			[
				"name"=>"type",
				"title"=>"数据类型",
				"type"=>"sa_picker",
				"data"=>[
					["title"=>"int","id"=>"int"],
					["title"=>"vachar","id"=>"vachar"],
					["title"=>"datetime","id"=>"datetime"],
					["title"=>"text","id"=>"text"],
					["title"=>"decimal","id"=>"decimal"]
				],
				"width"=>"140px"
			],
			[
				"name"=>"default",
				"title"=>"默认值",
				"type"=>"input",
                "placeholder"=>"1|0|null",
				"width"=>"140px"
			],
			[
				"name"=>"form_type",
				"title"=>"表单类型",
				"type"=>"sa_picker",
				"data"=>[
					["title"=>"输入框","id"=>"input"],
					["title"=>"图片选择","id"=>"attachment"],
                    ["title"=>"多图","id"=>"attachments"],
					["title"=>"dropdown","id"=>"sa_picker"],
					["title"=>"日期选","id"=>"bldate"],
					["title"=>"省市区","id"=>"cas"],
                    ["title"=>"简介输入框","id"=>"textarea"],
                    ["title"=>"富文本tinymce","id"=>"tinymce"],
                    ["title"=>"hidden","id"=>"hidden"],
                    ["title"=>"switch","id"=>"switch"],
					["title"=>"地图位置","id"=>"blmap"],
					["title"=>"查询数据","id"=>"sa_query"],
					["title"=>"复杂项配置","id"=>"bloption"],
					["title"=>"显示图片","id"=>"sa_images"],
					["title"=>"特色标签","id"=>"input_tags"],
                    ["title"=>"xm-select","id"=>"xm_select"],
                    ["title"=>"sa_radio","id"=>"sa_radio"]
				],
				"width"=>"140px"
			],
			
			[
				"name"=>"verify",
				"title"=>"表单验证",
				"type"=>"input",
				"placeholder"=>"required|number",
				"width"=>"150px"
			]
			,
			[
				"name"=>"with",
				"title"=>"模型with",
				"type"=>"input",
				"placeholder"=>"name|modelName|foreignKey",
				"width"=>"180px"
			]
			,
			[
				"name"=>"list_show",
				"title"=>"显示在列表",
				"type"=>"checkbox",
				"data"=>"是|否",
				"width"=>"70px"
			]
            ,
			[
				"name"=>"search_show",
				"title"=>"搜索项",
				"type"=>"checkbox",
				"data"=>"是|否",
				"width"=>"70px"
			]
            ,
			[
				"name"=>"search_type",
				"title"=>"搜索类型",
				"type"=>"sa_picker",
				"data"=>[
                    ['title'=>'dropdown','id'=>'sa_picker'],
                    ['title'=>'input','id'=>'input']
                ],
				"width"=>"120px"
			]
        ];

        return ['code'=>0,'msg'=>'','data'=>[
            'cols'=>$cols,
            'fields'=>[],
            'controller'=>'ext/posts',
            'model'=>'ext/posts',
            'is_list'=>1,
            'is_open'=>0,
            'support_query'=>0,
            'support_displayorder'=>1,
            'support_status'=>1,
            'many_relation'=>''
        ]];
    }

    public function store()
    {
        if(env('APP_ENV') != 'local')
        {
            return ['code'=>1,'msg'=>'just local env'];
        }
        $data = request('base');
        $name = $this->parseName($data['model']);
        $this->createTable($name,$data['table_prefix']??'');
        $this->model($name);
        $name2 = $this->parseName($data['controller']);
        $this->controller($name2,$name);

        $this->html($name2);
        //d(implode("\r",$this->msg));
        //d(json_encode(request('fields')),implode("\r",$this->msg));
        return ['code'=>0,'msg'=>'生成成功'];


	}

    public function createTable($name,$prefix = '')
    {
        $fields = request('fields');
        $data = request('base');
        
        $table_name = $prefix?"{$prefix}_".$name['table_name']:"".$name['table_name'];
        $table_sql = ['CREATE TABLE `la_'.$table_name.'` ('];
        $table_sql[] = '`id`  int NOT NULL AUTO_INCREMENT ,';
        foreach($fields as $val)
        {
            $field_sql = '';
            $default_value = $val['default'] === ''?"''":$val['default'];
            $comment = $val['desc'];
            switch($val['type'])
            {
                case 'int':
                    $field_sql = "`{$val['name']}`  int(11) NOT NULL DEFAULT {$default_value} COMMENT '{$comment}',";
                break;
                case 'vachar':
                    $field_sql = "`{$val['name']}`  varchar(255) NOT NULL DEFAULT {$default_value} COMMENT '{$comment}',";
                break;
                case 'datetime':
                    $field_sql = "`{$val['name']}`  datetime NULL DEFAULT NULL COMMENT '{$comment}',";
                break;
                case 'text':
                    $field_sql = "`{$val['name']}`  text NULL COMMENT '{$comment}',";
                break;
                case 'decimal':
                    $field_sql = "`{$val['name']}`  decimal(10,2) NOT NULL DEFAULT {$default_value} COMMENT '{$comment}',";
                break;
            }
            
            $table_sql[] = $field_sql;
        }
        $table_sql[] = "`updated_at`  datetime NULL DEFAULT NULL ,";
        $table_sql[] = "`created_at`  datetime NULL DEFAULT NULL ,";

        if(isset($data['support_displayorder']) && $data['support_displayorder'])
        {
            $table_sql[] = "`displayorder`  int(11) NOT NULL DEFAULT 0 ,";
        }
        if(isset($data['support_status']) && $data['support_status'])
        {
            $table_sql[] = "`status`  int(11) NOT NULL DEFAULT 0 ,";
        }


        $table_sql[] = "PRIMARY KEY (`id`))ENGINE=MyISAM;";

        if(!Schema::hasTable($table_name))
        {
            DB::statement(implode('',$table_sql));
            $this->line('创建数据表:'.$table_name.'成功');
        }else
        {
            $this->line('数据表:'.$table_name.'已存在');
        }
        return;
    }

    public function parseName($name)
    {
        $name = explode('/',$name);

        $page = implode('/',$name);
        $url = implode('/',$name);
        $table_name = implode('_',$name);
        $realname = array_pop($name);

        if(empty($name))
        {
            $namespace = '';
        }else
        {
            $namespace = '\\'.implode('\\',$name);
        }
        return [
            'page'=>$page,
            'url'=>$url,
            'namespace'=>$namespace,
            'phpfolder'=>implode('/',$name),
            'name'=>$realname,
            'table_name'=>$table_name
        ];

    }

    public function model($name)
    {
        $fields = request('fields');
        $data = request('base');
        $this->line('开始生成model文件：');
        $path_prefix = $this->path_source.'php';
        $path = $this->createFolder($name['phpfolder'],'model');
        $model_name = ucwords($name['name']);

        if($data['quick_type'] == 'category')
        {
            $content = file_get_contents(implode('/',[$path_prefix,'model_category.php']));
        }else
        {
            $content = file_get_contents(implode('/',[$path_prefix,'model.php']));
        }

        

        $hasone_tpl ="
    public function _name()
    {
        return \$this->_type(_modelName::class,'_foreignKey','_localKey');
    }";

        $hasone_data = [];
        foreach($fields as $val)
        {
            if(!$val['with'])
            {
                continue;
            }
            $withs = explode(' ',$val['with']);
            foreach($withs as $with)
            {
                $with = explode('|',$with);
                $hasone_data[] = str_replace(['_name','_modelName','_foreignKey','_localKey','_type'],[$with[0],$with[1],$with[2],$val['name'],'hasOne'],$hasone_tpl);
            }
        }
        
        $hasmany_data = '';

        if($data['many_relation'])
        {
            $with = explode('|',$data['many_relation']);
            $hasmany_data = str_replace(['_name','_modelName','_foreignKey','_localKey','_type'],[$with[0],$with[1],$with[2],'id','hasMany'],$hasone_tpl);
        }


        $replace_arr = [
            '/\$namespace\$/'=>$name['namespace'],
            '/\$table_name\$/'=>$name['table_name'],
            '/\$name\$/'=>ucwords($name['name']),
            '/\$hasone\$/'=>implode("\r",$hasone_data),
            '/\$hasmany\$/'=>$hasmany_data
        ];

        $search = $replace = [];
        
        foreach($replace_arr as $key=>$val)
        {
            $search[] = $key;
            $replace[] = $val;
        }

        $content = preg_replace($search,$replace,$content);
        $file = implode('/',[$path,$model_name.'.php']);
        $this->createFile($file,$content);
        return;
    }

    public function createFolder($name,$type = 'html')
    {
        $path_prefix = $this->path_prefix[$type];
        if(!$name)
        {
            $this->line('PHP文件夹无须创建文件夹');
            return $path_prefix;
        }
        $path = implode('/',[$path_prefix,$name]);
        if(!is_dir($path))
        {
            mkdir($path,0755,true);
            $this->line($path.' 文件夹创建成功');
        }else
        {
            $this->line($path.' 文件夹已存在');
        }
        return $path;
    }

    public function createFile($file,$content,$force = false)
    {
        if(file_exists($file) && !$force)
        {
            $this->line($file.' 文件已存在');
        }else
        {
            $fopen = fopen($file,'w');
            fwrite($fopen,$content);
            fclose($fopen);
            $this->line($file.' 创建成功');
        }
        return;
    }

    public function line($msg)
    {
        $this->msg[] = $msg;
    }

    public function controller($name,$model_name)
    {
        $this->line('开始生成controller文件：');
        $path_prefix = $this->path_source.'php';
        $path = $this->createFolder($name['phpfolder'],'controller');
        $controller_name = ucwords($name['name']).'Controller';

        $data = request('base');
        if($data['quick_type'] == 'category')
        {
            $content = file_get_contents(implode('/',[$path_prefix,'controller_category.php']));
        }else
        {
            $content = file_get_contents(implode('/',[$path_prefix,'controller.php']));
        }

        

        //with_column
        $with_column = [];
        //search
        $search = $relation_models = $relations = $search_relations = [];

        $input_search_tpl = "
        \$_name = request('_name','');
        if(\$_name)
        {
            \$_name = urldecode(\$_name);
            \$m = \$m->where([['_name','like','%'.\$_name.'%']]);

        }";
        $picker_search_tpl = "
        \$_name = request('_name','');
        if(\$_name)
        {
            \$m = \$m->where('_name',\$_name);
        }";

        $with_search_tpl = "
        \$_name = request('_name','');
        if(\$_name)
        {
            \$_name = urldecode(\$_name);
            \$m = \$m->whereHas('_hasname',function(\$q) use(\$_name){
                \$q = \$q->where([['title','like','%'.\$_name.'%']]);
            });
        }";

        $fields = request('fields');
        $model_name_info = $this->parseName($data['model']);
        foreach($fields as $val)
        {
            $with = $this->parseWith($val['with']);
            if(!empty($with))
            {
                $with_column[] = $with['name'];
                if($val['form_type'] == 'sa_picker' || $val['search_type'] == 'sa_picker')
                {
                    //添加关联获取列表数据
                    $relation_name = ucfirst($with['name']);
                    $relation_models[] = "use App\\Models".$model_name_info['namespace']."\\".$relation_name.";";
                    $relations[] = "\$item['".$with['name']."s'] = (new ".$relation_name."())->format(0);";
                    $search_relations[] = "\$search['".$with['name']."s'] = (new ".$relation_name."())->format(0);";
                }
            }
            if($val['search_show'])
            {

                if($val['search_type'] == 'input')
                {
                    if(!empty($with))
                    {
                        $search[] = str_replace(['_name','_hasname'],[$val['name'],$with['name']],$with_search_tpl);
                    }else
                    {
                        $search[] = str_replace(['_name'],[$val['name']],$input_search_tpl);
                    }
                    
                }else
                {
                    $search[] = str_replace(['_name'],[$val['name']],$picker_search_tpl);
                }
            }
            
        }
        //d($search);

        $replace_arr = [
            '/\$namespace\$/'=>$name['namespace'],
            '/\$controller_name\$/'=>$controller_name,
            '/\$modelname\$/'=>ucwords($model_name['name']),
            '/\$modelnamespace\$/'=>ucwords($model_name['namespace']),
            '/\$with_column\$/'=>json_encode($with_column),
            '/\$search\$/'=>implode("\r",$search),
            '/\$relation_models\$/'=>implode("\r",$relation_models),
            '/\$relations\$/'=>implode("\r",$relations),
            '/\$search_relations\$/'=>implode("\r",$search_relations),
        ];

        $search = $replace = [];
        
        foreach($replace_arr as $key=>$val)
        {
            $search[] = $key;
            $replace[] = $val;
        }

        $content = preg_replace($search,$replace,$content);
        $file = implode('/',[$path,$controller_name.'.php']);
        $this->createFile($file,$content);
        return;
    }

    public function parseWith($with = '')
    {
        if(!$with)
        {
            return [];
        }
        $with = explode('|',$with);
        $data = [
            'name'=>$with[0],
            'model_name'=>$with[1],
            'foreign_key'=>$with[2],
        ];
        return $data;
    }
    public function html($name)
    {
        $fields = request('fields');
        $data = request('base');
        $this->line('开始生成html文件：');
        $path_prefix = $this->path_source.'html';
        $copy_index = 'index.html';
        $copy_post = 'post.html';
        $copy_post_open = 'post_open.html';
        $copy_query = 'query.html';

        $index_content = file_get_contents(implode('/',[$path_prefix,$copy_index]));
        $post_content = file_get_contents(implode('/',[$path_prefix,$copy_post]));
        $post_open_content = file_get_contents(implode('/',[$path_prefix,$copy_post_open]));


        //index.html 
        $search = [];//search项
        $cols = [];//列表项
        [$form,$switch] = $this->form();//form表单项
        $input_search_tpl = '{name:"_name",label:"查询",encode:true,type:"input",params:{placeholder:"请输入查询"}}';
        $picker_search_tpl = '{name:"__name",label:"选择查询",data_name:"_data_name",type:"sa_picker",params:{sa_pars:{empty:"全部"}}}';
        $cols_tpl = '{field: "_name", title:"_title", width:120, align:"center"}';
        $displayorder_tpl = '{field:"displayorder",sort: true, edit:"text",width:100, title: "排序"}';
        $status_tpl = '{field: "status", title: "状态", width: 92, sa_filter:{field:"status"}}';
        foreach($fields as $val)
        {
            $with = $this->parseWith($val['with']);
            if($val['search_show'])
            {
                if($val['search_type'] == 'input')
                {
                    $search[] = str_replace(['_name'],[$val['name']],$input_search_tpl);
                }else
                {
                    if(!empty($with))
                    {
                        $search[] = str_replace(['__name','_data_name'],[$val['name'],$with['name'].'s'],$picker_search_tpl);
                    }
                }
            }
            if($val['list_show'])
            {
                //处理name值
                if(!empty($with))
                {
                    $col_name = $with['name'].'.title';
                }else
                {
                    $col_name = $val['name'];
                }
                $cols[] =  str_replace(['_name','_title'],[$col_name,$val['desc']],$cols_tpl);
            }
            
        }
        if(isset($data['support_displayorder']) && $data['support_displayorder'])
        {
            $cols[] = $displayorder_tpl;
        }
        if(isset($data['support_status']) && $data['support_status'])
        {
            $cols[] = $status_tpl;
        }


        $is_list = $data['is_list']??0;
        $is_open = $data['is_open']??0;
        $is_query = $data['support_query']??0;

        $replace_arr = [
            '/\$type\$/'=>$is_list?'searchlist':'sa_category',
            '/\$url\$/'=>$name['url'],
            '/\$page\$/'=>$name['page'],
            '/\$post_type\$/'=>$is_open?'open':'',
            '/\$search\$/'=>implode(",\r\t\t\t\t",$search),
            '/\$cols\$/'=>implode(",\r\t\t\t",$cols),
            '/\$form\$/'=>implode("",$form),
            '/\$switch\$/'=>implode("",$switch),
        ];

        $search = $replace = [];
        
        foreach($replace_arr as $key=>$val)
        {
            $search[] = $key;
            $replace[] = $val;
        }

        //生成index.html
        $index_content = preg_replace($search,$replace,$index_content);
        $path = $this->createFolder($name['page'],'html');

        $index_html = implode('/',[$path,'index.html']);
        $this->createFile($index_html,$index_content);

        

        //生成post.html
        $post_html = implode('/',[$path,'post.html']);
        if($is_open)
        {
            $post_content = preg_replace($search,$replace,$post_open_content);
        }else
        {


            $post_content = preg_replace($search,$replace,$post_content);
        }
        $this->createFile($post_html,$post_content);

        if($is_query)
        {
            //是否创建query页面
            $query_html = implode('/',[$path,'query.html']);
            $query_content = preg_replace($search,$replace,file_get_contents(implode('/',[$path_prefix,$copy_query])));
            $this->createFile($query_html,$query_content);
        }
        return;
    }

    public function form()
    {
        $form = [];
        $fields = request('fields');
        $data = request('base');
        $tpls = [
        'input' => '
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <input type="text" name="base[__name]" value="{{d.data.__name}}" __verify placeholder="请输入__desc" autocomplete="off" class="layui-input layui-col-md6">
                                    </div>
                                </div>
        ',
        "sa_picker" => '
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <input type="text" __verify name="base[__name]" data-data="{{=JSON.stringify(d.data.__withname)}}" data-value="{{d.data.__name}}"  placeholder="请选择__desc" value="" readonly="" class="layui-input sa_picker">
                                    </div>
                                </div>
        ',
        "attachment" => '
                                <div class="attachment" __verify data-name="base[__name]" data-limit="1" data-input="1" data-title="__desc" data-value="{{d.data.__name}}"></div>
        ',
        "attachments" => '
                                <div class="attachment" __verify data-name="base[__name]" data-limit="10" data-title="__desc" data-value="{{d.data.__name}}"></div>
        ',
        "bldate" => '
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <input type="text" __verify name="base[__name]" sa_pars=\'{type:"datetime"}\' value="{{d.data.__name?d.data.__name:\'\'}}" placeholder="请选择__desc" autocomplete="off" class="layui-input layui-col-md6 bldate">
                                    </div>
                                </div>
        ',
        "cas" => '
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <input type="text" __verify sa_pars=\'{level:3,split:" - "}\' name="base[__name]" value="{{d.data.__name}}" placeholder="请选择__desc" autocomplete="off" class="layui-input layui-col-md6 cas">
                                    </div>
                                </div>
        ',
        "blmap" => '
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <div class="blmap" name="base[lat],base[lng]" data-value="{{d.data.__name}}"></div>
                                    </div>
                                </div>
        ',
        "sa_query"=>'
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="sa_query" data-url="__withone/posts/query" data-value="{{d.data.__name}}" data-title="{{d.data.__withone.title}}"  name="base[__name]" tpl-title="{{!{{d.id}}-{{d.title}}!}}"></div>
                                </div>
        ',
        "bloption"=>'
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline" style="width:600px;">
                                        <div class="bloption" name="base[__name]" data-value="{{d.data.__name}}" data-col="{{=JSON.stringify(d.data.option)}}"></div>
                                    </div>
                                    <div class="layui-form-mid layui-word-aux">{name:"",title:"",type:""}</div>
                                </div>
        ',
        "sa_images"=>'
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <div class="sa_images" data-value="{{d.data.__name}}"></div>
                                    </div>
                                </div>
        ',
        "input_tags"=>'
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <div class="input_tags" data-name="base[__name]" data-value="{{d.data.__name}}" data-source="极简,封装,简单复制,测试中"></div>
                                    </div>
                                </div>
        ',
        "switch"=>'
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-block">
                                        <input type="checkbox" name="base[__name]" {{# if(d.data.__name == 1){ }}checked{{# } }} value="1" lay-skin="switch" lay-text="是|否">
                                    </div>
                                </div>
        ',
        "textarea"=>'
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <textarea class="layui-textarea" name="base[__name]" placeholder="请输入__desc">{{d.data.__name}}</textarea>
                                    </div>
                                </div>
        ',
        'tinymce'=>'
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-block">
                                        <textarea class="tinymce" name="base[__name]" placeholder="请输入__desc">{{d.data.__name}}</textarea>
                                    </div>
                                </div>
        ',
        'xm_select'=>'
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <div filterable="true" toolbar="true" class="xm_select" name="base[__name]" value="{{d.data.__name}}" placeholder="请选择__desc" data-list="{{=JSON.stringify(d.data.xm_select)}}"></div>
                                    </div>
                                </div>
        ',
        'sa_radio'=>'
                                <div class="layui-form-item">
                                    <label class="layui-form-label">__desc</label>
                                    <div class="layui-input-inline">
                                        <div class="sa_radio" name="base[__name]" value="{{d.data.__name}}" data-data="{{=JSON.stringify(d.data.select.id)}}"></div>
                                    </div>
                                    <div class="layui-form-mid layui-word-aux">{name:"",id:""}</div>
                                </div>
        ',
        "hidden"=>'
                                <input type="hidden" name="base[__name]" value="{{d.data.__name}}" />
        ',
        ];
        $displayorder_tpl = '
                                <div class="layui-form-item">
									<label class="layui-form-label">排序</label>
									<div class="layui-input-inline" style="width:200px;">
										<input type="text" name="base[displayorder]" value="{{d.data.displayorder?d.data.displayorder:0}}" placeholder="请输入排序" autocomplete="off" class="layui-input layui-col-md6">
									</div>
									<div class="layui-form-mid layui-word-aux">
										值越大越排在前面
									</div>
								</div>
        ';
        $status_tpl = '
                                <div class="layui-form-item">
									<label class="layui-form-label">状态</label>
									<div class="layui-input-block">
										<input type="checkbox" name="base[status]" {{# if(d.data.status == 1){ }}checked{{# } }} value="1" lay-skin="switch" lay-text="启用|启用">
									</div>
								</div>
        ';

        $switch_tpl = '
            if(!data.field["base[__name]"])
            {
                data.field["base[__name]"] = 0;
            }
        ';
        $switch = [];

        foreach($fields as $val)
        {
            $verify = '';
            if($val['verify'])
            {
                $verify = 'lay-verify="'.$val['verify'].'"';
            }
            $withname = $withname2 = $val['name'];
            $search = ['__name','__desc','__verify','__withname','__withone'];
            $type = $val['form_type'];
            $with = $this->parseWith($val['with']);
            if(!empty($with))
            {
                $withname = $with['name'].'s';
                $withname2 = $with['name'];
            }
            $replace = [$val['name'],$val['desc'],$verify,$withname,$withname2];
            if(isset($tpls[$type]))
            {
                $form[] = str_replace($search,$replace,$tpls[$type]);
            }
            
            if($type == 'switch')
            {
                $switch[] = str_replace($search,$replace,$switch_tpl);
            }

            
            /*
            ["title"=>"省市区","id"=>"cas"],
					["title"=>"地图位置","id"=>"blmap"],
					["title"=>"查询数据","id"=>"sa_query"],
					["title"=>"复杂项配置","id"=>"bloption"],
					["title"=>"显示图片","id"=>"sa_images"],
					["title"=>"特色标签","id"=>"input_tags"],
                    ["title"=>"switch","id"=>"switch"]
                    */
        }
        if(isset($data['support_displayorder']) && $data['support_displayorder'])
        {
            $form[] = $displayorder_tpl;
        }
        if(isset($data['support_status']) && $data['support_status'])
        {
            $form[] = $status_tpl;
            $switch[] = str_replace($search,['status'],$switch_tpl);
        }

        return [$form,$switch];
    }
}
