<?php

namespace Echoyl\Sa\Console\Commands;

use Echoyl\Sa\Services\UploadService;
use Illuminate\Console\Command;

class HelperCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'sa:command {name} {type=list_0_0}';

    protected $signature = 'deadmin:helper {params}
                    {--compressimage : compress image in dir,argument params should be the dir path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'some helper commands';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('compressimage')) {
            $this->compressimage();
        }

    }

    public function compressimage()
    {
        $params = $this->argument('params');

        $dir = storage_path('app/public/'.$params);

        if (! is_dir($dir)) {
            $this->components->info($dir.' is not dir');

            return;
        }

        $list = scandir($dir);
        $us = new UploadService;
        $count = 0;
        foreach ($list as $file) {
            if ($file != '.' && $file != '..') {
                // 检测是否是image
                $filepath = $dir.'/'.$file;
                if ($us->shouldBeCompressed($filepath)) {
                    // 压缩图片
                    $us->resizeImage($filepath, 1000);
                    $this->components->info($filepath.' is compressed');
                    $count++;
                }
            }
        }

        $this->components->info($count.' files is compressed');

    }
}
