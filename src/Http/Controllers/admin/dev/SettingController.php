<?php

namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\Setting;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\SetsService;
use Illuminate\Support\Facades\Process;

class SettingController extends ApiBaseController
{
    public $model;

    public function __construct(Setting $model)
    {
        $this->model = $model;
    }

    public function setting()
    {
        // 设置系统设置中的菜单，主要可以自动检索出菜单中的图片字段信息
        request()->offsetSet('dev_menu', Utils::$setting_dev_menu);

        return (new SetsService)->post('setting');
        // return (new SetsService)->post('setting',[['logo','image'],['loginBgImage','image']]);
    }

    /**
     * 格式化文件
     *
     * @return void
     */
    public function formatFile($id)
    {
        $model = Model::where(['id' => $id])->with(['relations.foreignModel'])->first();
        if (! $model) {
            return $this->failMsg('模型不存在');
        }

        $ds = new DevService;

        $files = [];

        $files[] = $ds->createControllerFile($model);

        $files[] = $ds->createModelFile($model);

        if (! config('sa.formatCode.enable', false)) {
            return $this->success();
        }
        // $file_paths = request('file_path', []);
        foreach ($files as $file_path) {
            if (! $file_path || ! file_exists($file_path)) {
                continue;
            }
            $command = base_path('vendor/bin/pint ').$file_path;
            Process::run($command);
        }

        return $this->success();
    }
}
