<?php

namespace Echoyl\Sa\Services;

use Echoyl\Sa\Constracts\SaAdminAppServiceInterface;
use Echoyl\Sa\Models\Pca;
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
        $pca = (new Pca())->where(['pcode'=>0])->get()->toArray();
        $mapdatas = collect($pca)->map(function($q){
            return [
                'code'=>$q['code'],'la'=>rand(0,100),'li'=>rand(0,100),'name'=>$q['name']
            ];
        })->toArray();
        $mapdata = [];
        foreach($mapdatas as $val)
        {
            $mapdata[$val['code']] = $val;
        }
        //d($mapdata);
        $data = [
            'row' => [
                [
                    'form'=>$this->form(),
                    'col' => [
                        [
                            'tab'=>[
                                [
                                    'title'=>'全国吃辣程度统计',
                                    'row'=>[
                                        [
                                            'col'=>[
                                                [
                                                    'title'=>'地图统计',
                                                    'data' => $this->areaMap($mapdata), 
                                                    'type' => 'areaMap', 
                                                    'field'=>'la',
                                                    'config'=>[
                                                        'tooltip'=>[
                                                            'items'=>[
                                                                ['field'=>'name','alias'=>'省份'],
                                                                ['field'=>'la','alias'=>'数值'],
                                                                ['field'=>'li','alias'=>'彩礼']
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'title'=>'表格统计',
                                                    'data' => $mapdatas, 
                                                    'type' => 'table', 
                                                    'columns'=>[
                                                        ['dataIndex'=>'name','title'=>'省份','width'=>250],
                                                        ['dataIndex'=>'la','title'=>'辣值','sort'=>true],
                                                        ['dataIndex'=>'li','title'=>'彩礼','sort'=>true]
                                                    ],
                                                    'props'=>[
                                                        'size'=>'small'
                                                    ]
                                                ],
                                            ]
                                        ]
                                    ]
                                ],
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
                                ]],
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
                        ['title'=>'总销售额','value'=>rand(10000,90000),'href'=>'','prefix'=>'￥','type'=>'card'],
                        ['title'=>'访问量','value'=>rand(1000,10000),'href'=>'','suffix'=>'次','type'=>'card'],
                        ['title'=>'支付比数','value'=>rand(100,1000),'href'=>'','suffix'=>'笔','type'=>'card'],
                        ['title'=>'营销活动效果','value'=>rand(10,100),'href'=>'','suffix'=>'%','type'=>'card'],
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

    public function areaMap($data,$type = 'cn')
    {
        $file = storage_path('app/public/map/'.$type.'.json');
        if(!file_exists($file))
        {
            return false;
        }

        $map_data = file_get_contents($file);
        //return $data;
        $map_data = json_decode($map_data,true);
        foreach($map_data['features'] as $key=>$val)
        {
            //d($val['properties']['adcode']);
            if(!isset($val['properties']['adcode']))
            {
                //d($val['properties']);
                continue;
            }
            if(isset($data[$val['properties']['adcode']]))
            {
                $val['properties'] = array_merge($val['properties'],$data[$val['properties']['adcode']]);
            }
            $map_data['features'][$key] = $val;
        }
        //$data['features'] = $features;
        return $map_data;
    }

    public function form()
    {
        return [
            //form的默认值
            'value'=>[
                'date[]'=>[date("Y-m-d",strtotime("-7 days")),date("Y-m-d")],
            ],
            //form的项
            'columns'=>[
                [
                    'dataIndex'=>'date[]',
                    'title'=>'日期检索',
                    'valueType'=>'dateRange',
                    'fieldProps'=>[
                        'presets'=>[
                            ['label'=>'近7日','value'=>[date("Y-m-d",strtotime("-7 days")),date("Y-m-d")]],
                            ['label'=>'近30日','value'=>[date("Y-m-d",strtotime("-30 days")),date("Y-m-d")]],
                            ['label'=>'近1年','value'=>[date("Y-m-d",strtotime("-1 year")),date("Y-m-d")]]
                        ]
                    ]
                ]
            ]
        ];
    }
}
