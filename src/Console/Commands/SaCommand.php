<?php
namespace Echoyl\Sa\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaCommand extends Command
{
    var $path_prefix = [];
    var $path_source = '';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sa:command {name} {type=list_0_0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->path_prefix = [
            'html'=>public_path('myadmin/dist/views'),
            'controller'=>app_path('Http/Controllers/admin'),
            'model'=>app_path('Models')
        ];

        $this->path_source = __DIR__.'/../../../file/';

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $name = $this->argument('name');

        $type = $this->argument('type');
        //$end = $this->argument('e');
        $name = $this->parseName($name);
        $this->html($name,$type);
        $this->controller($name,$type);
        $this->model($name,$type);
        return;
        //$this->info($content);
    }

    public function model($name,$type = 'list_0_0')
    {
        $this->line('开始生成model文件：');
        $path_prefix = $this->path_source.'php';
        $path = $this->createFolder($name['phpfolder'],'model');
        $model_name = ucwords($name['name']);
        $content = file_get_contents(implode('/',[$path_prefix,'model.php']));

        $replace_arr = [
            '/\$namespace\$/'=>$name['namespace'],
            '/\$table_name\$/'=>$name['table_name'],
            '/\$name\$/'=>ucwords($name['name'])
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

    public function controller($name,$type = 'list_0_0')
    {
        $this->line('开始生成controller文件：');
        $path_prefix = $this->path_source.'php';
        $path = $this->createFolder($name['phpfolder'],'controller');
        $controller_name = ucwords($name['name']).'Controller';
        $content = file_get_contents(implode('/',[$path_prefix,'controller.php']));

        $replace_arr = [
            '/\$namespace\$/'=>$name['namespace'],
            '/\$controller_name\$/'=>$controller_name,
            '/\$name\$/'=>ucwords($name['name'])
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

    public function html($name,$type = 'list_0_0')
    {
        $this->line('开始生成html文件：');
        $path_prefix = $this->path_source.'html';
        $copy_index = 'index.html';
        $copy_post = 'post.html';
        $copy_post_open = 'post_open.html';
        $copy_query = 'query.html';

        $index_content = file_get_contents(implode('/',[$path_prefix,$copy_index]));
        $post_content = file_get_contents(implode('/',[$path_prefix,$copy_post]));
        $post_open_content = file_get_contents(implode('/',[$path_prefix,$copy_post_open]));

        $type = explode('_',$type);

        $is_category = $type[0] == 'category'?true:false;
        $is_open = $type[1]?true:false;
        $is_query = $type[2]?true:false;

        $replace_arr = [
            '/\$type\$/'=>!$is_category?'searchlist':'sa_category',
            '/\$url\$/'=>$name['url'],
            '/\$page\$/'=>$name['page'],
            '/\$post_type\$/'=>$is_open?'open':'',
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

    public function createFile($file,$content)
    {
        if(file_exists($file))
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

}
