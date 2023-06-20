<?php
namespace Echoyl\Sa\Services;
use Echoyl\Sa\Constracts\SaAdminAppServiceInterface;

class AdminAppService implements SaAdminAppServiceInterface
{
    public function  chartData()
    {
        $data = [];
        // foreach($titles as $title)
        // {
        //     $data[] = ['type'=>$title,'value'=>10];
        // }
        // $data[] = ['type'=>'空闲','value'=>(new Zichan())->where(['usestate_id'=>2])->count()];
        // $data[] = ['type'=>'正常','value'=>(new Zichan())->where(['usestate_id'=>1])->count()];
        // $data[] = ['type'=>'调拨中','value'=>(new Diaobo())->whereDoesntHave('logs',function($q){
        //     $q->where(['action'=>0,'state'=>1]);
        // })->count()];
        
        return [['title'=>'状态统计','y'=>'','data'=>$data,'type'=>'pie']];
    }

    public function cards()
    {
        $items = [
            // ['title'=>'资产数量','value'=>(new Zichan())->count(),'href'=>'/zichan/zichan','unit'=>'件'],
            // ['title'=>'资产总值','value'=>(new Zichan())->sum('price') / 100,'href'=>'/zichan/zichan','unit'=>'元'],
            // ['title'=>'资产总净额','value'=>(new Zichan())->sum('jinge') / 100,'href'=>'/zichan/zichan','unit'=>'元'],
        ];
        return $items;
    }
}