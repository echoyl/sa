<?php

namespace Echoyl\Sa\Services;

use Echoyl\Sa\Constracts\SaAdminAppServiceInterface;
use stdClass;

class AdminAppService implements SaAdminAppServiceInterface
{
    public function  chartData()
    {
        $carr = function($length){
            $r = [];
            for($i=0;$i<$length;$i++)
            {
                $r[] = 1;
            }
            return $r;
        };
        $data = [
            'row' => [
                [
                    'col' => [
                        [
                            'tab'=>[
                                ['title' => '当月数据', 'y' => '', 'data' => collect($carr(30))->map(function($v,$k){
                                    $day = 30 - $k;
                                    return ['x'=>date("m-d",strtotime("-{$day} days")),'y'=>rand(10,200)];
                                }), 'type' => 'column', 'config' => [
                                    'meta'=>[
                                        'y'=>[
                                            'alias'=>'销售额'
                                        ]
                                    ]
                                ]],
                                ['title' => '全年', 'y' => '', 'data' => collect($carr(12))->map(function($v,$k){
                                    $day = 12 - $k;
                                    return ['x'=>date("Ym",strtotime("-{$day} months")),'y'=>rand(10,200)];
                                }), 'type' => 'column', 'config' => [
                                    'meta'=>[
                                        'y'=>[
                                            'alias'=>'销售额'
                                        ]
                                    ]
                                ]]
                            ]
                        ]
                        
                    ]
                ],
                [
                    'col' => [
                        ['title' => '环图', 'y' => '', 'data' => collect($carr(6))->map(function($v,$k){
                            return ['type'=>'type'.$k,'value'=>rand(10,100)];
                        }), 'type' => 'pie', 'config' => [
                            'angleField'=>'value',
                            'colorField'=>'type',
                            'innerRadius'=>0.6,
                        ]],
                        ['title' => '线图', 'y' => '', 'data' => collect($carr(12))->map(function($v,$k){
                            return ['x'=>'month'.$k,'y'=>rand(10,100)];
                        }), 'type' => 'line', 'config' => '{}']
                    ]
                ],
                
            ]
        ];

        return $data;
    }

    public function cards()
    {
        $items = [
            'row'=>[
                [
                    'col'=>[
                        ['title'=>'总销售额','value'=>126560,'href'=>'','prefix'=>'￥','type'=>'card'],
                        ['title'=>'访问量','value'=>8846,'href'=>'','suffix'=>'次','type'=>'card'],
                        ['title'=>'支付比数','value'=>6560,'href'=>'','suffix'=>'笔','type'=>'card'],
                        ['title'=>'营销活动效果','value'=>78,'href'=>'','suffix'=>'%','type'=>'card'],
                    ]
                ]
            ]
            // ['title'=>'资产数量','value'=>(new Zichan())->count(),'href'=>'/zichan/zichan','unit'=>'件'],
            // ['title'=>'资产总值','value'=>(new Zichan())->sum('price') / 100,'href'=>'/zichan/zichan','unit'=>'元'],
            // ['title'=>'资产总净额','value'=>(new Zichan())->sum('jinge') / 100,'href'=>'/zichan/zichan','unit'=>'元'],
        ];
        return $items;
    }

    public function panel()
    {
        $charts = $this->chartData();
        $cards = $this->cards();

        $data = array_merge($cards['row'],$charts['row']);

        return ['row'=>$data];
    }

    public function parseUserInfo($userinfo,$user)
    {
        return $userinfo;
    }
}
