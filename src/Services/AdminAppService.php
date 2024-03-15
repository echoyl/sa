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
                                // [
                                //     'title'=>'全国吃辣程度统计',
                                //     'row'=>[
                                //         [
                                //             'col'=>[
                                //                 [
                                //                     'title'=>'地图统计',
                                //                     'data' => $this->areaMap($mapdata), 
                                //                     'type' => 'areaMap', 
                                //                     'field'=>'la',
                                //                     'config'=>[
                                //                         'tooltip'=>[
                                //                             'items'=>[
                                //                                 ['field'=>'name','alias'=>'省份'],
                                //                                 ['field'=>'la','alias'=>'数值'],
                                //                                 ['field'=>'li','alias'=>'彩礼']
                                //                             ]
                                //                         ]
                                //                     ]
                                //                 ],
                                //                 [
                                //                     'title'=>'表格统计',
                                //                     'data' => $mapdatas, 
                                //                     'type' => 'table', 
                                //                     'columns'=>[
                                //                         ['dataIndex'=>'name','title'=>'省份','width'=>250],
                                //                         ['dataIndex'=>'la','title'=>'辣值','sort'=>true],
                                //                         ['dataIndex'=>'li','title'=>'彩礼','sort'=>true]
                                //                     ],
                                //                     'props'=>[
                                //                         'size'=>'small'
                                //                     ]
                                //                 ],
                                //             ]
                                //         ]
                                //     ]
                                // ],
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
            return [];
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
    public function panel2()
    {
        $carr = function ($length) {
            $r = [];
            for ($i = 0; $i < $length; $i++) {
                $r[] = 1;
            }
            return $r;
        };
        $names = ['家用电器','食用酒水','个护健康','服饰箱包','母婴产品','其他'];
        $source = [
            'label' => '示例数据',
            'value' => 'source1',
            'fields' => ['type', 'value'],
            'data' => [
                'chart' => [
                    'fields' => ['type', 'value'],
                    'data' => collect($carr(6))->map(function ($v, $k) use($names) {
                        return ['type' => $names[$k], 'value' => rand(10, 100)];
                    })
                ]
            ]

        ];
        $source2 = [
            'label' => '示例数据2',
            'value' => 'source2',
            'fields' => ['typex', 'valuex'],
            'data' => [
                'chart' => [
                    'fields' => ['typex', 'valuex'],
                    'data' => collect($carr(6))->map(function ($v, $k) use($names) {
                        return ['typex' => $names[$k], 'valuex' => rand(10, 100)];
                    })
                ]
            ]
        ];
        $source3 = [
            'label' => '示例数据3',
            'value' => 'source3',
            'fields' => ['gdp', 'year', 'name'],
            'data' => [
                'chart' => [
                    'fields' => ['gdp', 'year', 'name'],
                    'data' => collect($carr(24))->map(function ($v, $k) {
                        return [
                            'year' => 2010 + $k % 12, 
                            'gdp' => rand(10, 100), 
                            'name' => $k > 11 ? 'china' : 'us'
                        ];
                    })
                ]
            ]
        ];

        $pca = (new Pca())->where(['pcode' => 0])->get()->toArray();
        $mapdatas = collect($pca)->map(function ($q) {
            return [
                'code' => $q['code'], 'la' => rand(0, 100), 'li' => rand(0, 100), 'name' => $q['name'], 'id' => $q['code']
            ];
        })->toArray();
        $mapdata = [];
        foreach ($mapdatas as $val) {
            $mapdata[$val['code']] = $val;
        }
        // $mapdata[710000] = [
        //     'code' => 710000, 'la' => rand(0, 100), 'li' => rand(0, 100), 'name' => '台湾', 'id' => 710000
        // ];
        $as = new AdminAppService;

        $sourcemap = [
            'label' => '地图数据-map',
            'value' => 'sourcemap',
            'fields' => ['name', 'la', 'li'],
            'data' => [
                'chart' => [
                    'fields' => ['name', 'la', 'li'],
                    'data' => $as->areaMap($mapdata)
                ]
            ]
        ];

        $source4 = [
            'label' => '当月数据-table',
            'value' => 'source4',
            'fields' => ['name', 'la', 'li'],
            'data' => $mapdatas
        ];

        $source5 = [
            'label' => '地图数据5',
            'value' => 'source5',
            'fields' => ['name', 'la', 'li'],
            'data' => array_values($mapdata)
        ];
        $sourceMonth = [
            'label' => '当月数据',
            'value' => 'sourcemonth',
            'data' => [
                'chart' => [
                    'fields' => ['x', 'y'],
                    'data' => collect($carr(30))->map(function ($v, $k) {
                        $day = 30 - $k;
                        return ['x' => date("m-d", strtotime("-{$day} days")), 'y' => rand(10, 200)];
                    })
                ]
            ]
        ];
        $sourceYear = [
            'label' => '全年数据',
            'value' => 'sourceyear',
            'data' => [
                'chart' => [
                    'fields' => ['x', 'y'],
                    'data' => collect($carr(12))->map(function ($v, $k) {
                        $day = 12 - $k;
                        return ['x' => date("Ym", strtotime("-{$day} months")), 'y' => rand(10, 200)];
                    })
                ]
            ]

        ];

        $source6 = [
            'label' => '总销售额',
            'value' => 'source6',
            'fields' => [],
            'data' => [
                'value' => rand(10000, 90000),
                // 'chart'=>[
                //     'fields'=>['type','value'],
                //     'data'=>collect($carr(17))->map(function($v,$k){
                //         return ['type'=>'type'.$k,'value'=>rand(10,100)];
                //     })
                // ],
                'trend' => [
                    ['title' => '日环比', 'value' => $this->random_float(50, 100) . '%', 'trend' => 'up'],
                    ['title' => '月环比', 'value' => $this->random_float(1, 50) . '%', 'trend' => 'down']
                ],
                'footer' => '日销售额 ￥' . rand(100000, 999999)
            ],
        ];
        $source7 = [
            'label' => '访问量',
            'value' => 'source7',
            'fields' => [],
            'data' => [
                'value' => rand(10000, 90000),
                'chart' => [
                    'fields' => ['type', 'value'],
                    'data' => collect($carr(17))->map(function ($v, $k) {
                        return ['type' => 'type' . $k, 'value' => rand(100, 400)];
                    })
                ],
                'trend' => [
                    ['title' => '日访问量', 'value' => rand(50, 500) . '人', 'trend' => 'down'],
                ]
            ],
        ];
        $source8 = [
            'label' => '支付比数',
            'value' => 'source8',
            'fields' => [],
            'data' => [
                'value' => rand(10000, 90000),
                'chart' => [
                    'fields' => ['type', 'value'],
                    'data' => collect($carr(17))->map(function ($v, $k) {
                        return ['type' => 'type' . $k, 'value' => rand(10, 100)];
                    })
                ],
                'trend' => [
                    ['title' => '转化率', 'value' => $this->random_float(1, 50) . '%', 'trend' => 'up']
                ]
            ],
        ];
        $source9 = [
            'label' => '营销活动效果',
            'value' => 'source9',
            'fields' => [],
            'data' => [
                'value' => rand(10, 99),
                'progress' => [
                    ['percent' => rand(10, 99), 'status' => 'active', "strokeColor" => ["from" => '#108ee9', "to" => '#87d068']]
                ]
            ]
        ];
        $source10 = [
            'label' => '表单字段',
            'value' => 'source10',
            'fields' => ['date[]'],
            'data' => [
                ['name' => 'date[]', 'props' => [
                    'fieldProps' => [
                        'defaultValue' => [date("Y-m-d", strtotime("-7 days")), date("Y-m-d")],
                        'presets' => [
                            ['label' => '近7日', 'value' => [date("Y-m-d", strtotime("-7 days")), date("Y-m-d")]],
                            ['label' => '近30日', 'value' => [date("Y-m-d", strtotime("-30 days")), date("Y-m-d")]],
                            ['label' => '近1年', 'value' => [date("Y-m-d", strtotime("-1 year")), date("Y-m-d")]]
                        ]
                    ]
                ]]
            ]
        ];
        $dots = [
            ['title'=>'门店1号','lat'=>'31.231024533405503','lng'=>'121.46091217796811'],
            ['title'=>'门店2号','lat'=>'31.2303340000322','lng'=>'121.45518260832819']
            
        ];
        $sourcemapdots = [
            'label' => '标点地图',
            'value' => 'sourcemapdots',
            'data'=>[
                'chart'=>[
                    'data'=>$dots
                ]
            ]
        ];
        $data = [$source, $source2, $source3, $source4, $source5, $sourceMonth, $sourceYear, $source6, $source7, $source8, $source9, $source10,$sourcemap,$sourcemapdots];
        return $data;
    }
    public function random_float($min, $max) {
        return number_format($min + mt_rand() / mt_getrandmax() * ($max - $min),2,'.');
    }
}
