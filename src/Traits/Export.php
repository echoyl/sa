<?php

namespace Echoyl\Sa\Traits;

use Echoyl\Sa\Services\export\ExcelService;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

trait Export
{
    public function getExportConfig()
    {
        $this_model = $this->getModel();
        $model = HelperService::getDevModel($this_model->model_id);
        if (! $model) {
            return [1, 'model error'];
        }

        $index = request('export_index', 0);

        $setting = $model['setting'] ? json_decode($model['setting'], true) : [];

        $default_config = ['label' => 'default', 'value' => 'default'];

        $export_config = Arr::get($setting, 'export', [$default_config]);

        if ($index) {
            $config = collect($export_config)->first(function ($item) use ($index) {
                return $item['value'] == $index;
            });
            if (! $config) {
                $config = $default_config;
            }
        } else {
            $config = $export_config[0];
        }

        if (! $config) {
            return [1, 'export config error'];
        }

        // $ds->modelColumn2Export($model);
        $config['config']['dev_menu'] = request('dev_menu'); // 如果未设置表头，直接读取列表的表头进行导出

        return [0, $config];

    }

    /**
     * 默认的导出数据方法，如果不适用该方法可以自定义方法 获取数据后 调用getExportConfig方法获取导出配置
     *
     * @param  bool  $listData
     * @return void
     */
    public function export($listData = false)
    {
        // 获取导出格式的配置
        [$code,$config] = $this->getExportConfig();

        if ($code) {
            return $this->failMsg($config);
        }

        $search = []; // search数据将用于导出数据时数据的默认渲染
        $this->parseWiths($search);
        if (method_exists($this, $this->handleSearchName)) {
            // handleSearchName 不为handleSearch时走自己的流程，如果需要调用handleSearch请在handleSearchName方法中调用
            $method = $this->handleSearchName;
            [$m,$search] = $this->$method($search);
        } else {
            [$m,$search] = $this->handleSearch($search);
        }

        $ids = request('ids');
        if ($ids) {
            $m = $m->whereIn('id', $ids);
        }

        $m = $this->defaultSearch($m);

        $es = new ExcelService($config['config'], $search);

        $m = $this->handleSort($m);

        if (! empty($this->with_sum)) {
            foreach ($this->with_sum as $with_sum) {
                $m = $m->withSum($with_sum[0], $with_sum[1]);
            }
        }

        $page_size = 1000;

        $m->with($this->with_column)->chunk($page_size, function ($data) use ($es, $listData) {
            // $data = $data->toArray();
            if (! method_exists($this, 'exportFormatData')) {
                // 没有自定义渲染导出数据那么设置一个默认的渲染方法
                foreach ($data as $key => $val) {
                    $val['origin_data'] = $val;
                    $this->parseData($val, 'decode', 'list');
                    $val = $this->listItem($val);
                    if (! $listData) {
                        unset($val['origin_data']);
                    }
                    $data[$key] = $val;
                }
            }

            if ($listData && method_exists($this, $listData)) {
                $data = $this->$listData($data);
                $formatData = false;
            } else {
                $formatData = method_exists($this, 'exportFormatData') ? function ($val) {
                    return $this->exportFormatData($val);
                } : false;
            }
            $es = $es->exportPage($data, $formatData);
        });

        $ret = $es->getUrl();

        return $this->success($ret);
    }

    // /**
    //  * 导入方法示例 通过ExcelService读取数据后 自行处理导入逻辑
    //  *
    //  * @return void
    //  */
    // public function import()
    // {
    // 	set_time_limit(0);
    //     $datefun = function($excelTime)
    // 	{
    // 		return (int)(($excelTime - 25569) * 86400 - (8 * 60 * 60));
    // 	};

    //     $insert_count = $update_count = $fail_count = 0;
    // 	$inserts = [];
    //     $fail = [];
    //     $index_maps = [
    //         ['name'=>'field_name','index'=>'A'],
    //     ];
    //     $date_names = [
    // 		'bz_time','yx_time','fz_time','ztjz_time','ztbg_time'
    // 	];

    // 	$file = request()->file('file');
    // 	$data = ExcelService::getData($file);
    //     $model = $this->getModel();
    //     foreach($data as $key=>$val)
    //     {
    //         if($key  < 1)
    // 		{
    //             //第一行是表头跳过
    // 			continue;
    // 		}
    //         //处理数据逻辑

    //         foreach($index_maps as $col)
    // 		{
    // 			$_v = Arr::get($val,ExcelService::columnIndexFromString($col['index']));
    // 			if($_v)
    // 			{
    //                 if(in_array($col['name'],$date_names) && is_numeric($_v))
    // 				{
    //                     //检测如果是日期字段且值是数字类型则进行转换
    // 					$_v = date('Y-m-d',$datefun($_v));
    // 				}
    // 				$info[$col['name']] = $_v;
    // 			}
    // 		}
    //         $info = [];
    //         $id = 0;
    // 		$has = $model->where(['field_name'=>$info['field_name']])->first();
    // 		if($has)
    // 		{
    // 			$id = $has['id'];
    // 		}
    // 		if($id)
    // 		{
    // 			$update_count++;
    // 			//更新数据
    // 			$model->where('id',$id)->update($info);
    // 		}else
    // 		{
    // 			$info['created_at'] = now();
    // 			$insert_count++;
    // 			//新增数据
    // 			$model->insert($info);
    // 		}
    //     }
    //     return $this->successMsg(implode(' ',['变更数据:',$insert_count,'失败条数:',$fail_count]),$fail);
    // }

}
