<?php
namespace Echoyl\Sa\Console\Commands;

use Echoyl\Sa\ServiceProvider;
use Illuminate\Foundation\Console\VendorPublishCommand;

class SaCommand extends VendorPublishCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'sa:command {name} {type=list_0_0}';

    protected $signature = 'sa:publish
                    {--existing : Publish and overwrite only the files that have already been published}
                    {--force : Overwrite any existing files}
                    {--all : Publish assets for all service providers without prompt}
                    {--provider= : The service provider that has assets you want to publish}
                    {--update : Just update static files except config and sql files}
                    {--tag=* : One or many tags that have assets you want to publish}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish sa static files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if($this->option('update'))
        {
            //只更新静态文件 强制覆盖静态文件
            //$this->input->setOption('force',true);
            //将旧文件移除

            $this->tags = ['antadmin','antadmindev'];

            foreach($this->tags as $tag)
            {
                foreach($this->pathsToPublish($tag) as $to)
                {
                    $this->files->deleteDirectory($to);
                }
            }
        }else
        {
            //初始化发布 静态文件及配置，数据库文件
            $this->tags = ['antadmin','antadmindev','deadmin'];
        }

        foreach ($this->tags ?: [null] as $tag) {
            $this->publishTag($tag);
        }
    }


}
