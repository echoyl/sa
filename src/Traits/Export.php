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
        if(!$model)
        {
            return [1,'model error'];
        }

		$index = request('export_index',0);

        $setting = $model['setting']?json_decode($model['setting'],true):[];

        $default_config = ['label'=>'default','value'=>'default'];

        $export_config = Arr::get($setting,'export',[$default_config]);

        if($index)
        {
            $config = collect($export_config)->first(function($item) use($index){
                return $item['value'] == $index;
            });
            if(!$config)
            {
                $config = $default_config;
            }
        }else
        {
            $config = $export_config[0];
        }

        if(!$config)
        {
            return [1,'export config error'];
        }

        //$ds->modelColumn2Export($model);
        $config['config']['dev_menu'] = request('dev_menu');//如果未设置表头，直接读取列表的表头进行导出

		return [0,$config];

	}

	/**
	 * 默认的导出数据方法，如果不适用该方法可以自定义方法 获取数据后 调用getExportConfig方法获取导出配置
	 *
	 * @param boolean $listData
	 * @return void
	 */
	public function export($listData = false)
    {
		//获取导出格式的配置
		[$code,$config] = $this->getExportConfig();

		if($code)
		{
			return $this->failMsg($config);
		}

        $search = [];//search数据将用于导出数据时数据的默认渲染
        $this->parseWiths($search);
        if (method_exists($this, $this->handleSearchName)) 
        {
            //handleSearchName 不为handleSearch时走自己的流程，如果需要调用handleSearch请在handleSearchName方法中调用
            $method = $this->handleSearchName;
            [$m,$search] = $this->$method($search);
        }else
        {
            [$m,$search] = $this->handleSearch($search);
        }

		$ids = request('ids');
		if($ids)
		{
			$m = $m->whereIn('id',$ids);
		}

		$m = $this->defaultSearch($m);

        
        
		$es = new ExcelService($config['config'],$search);

        $m = $this->handleSort($m);

        if (!empty($this->with_sum)) {
            foreach($this->with_sum as $with_sum)
            {
                $m = $m->withSum($with_sum[0],$with_sum[1]);
            }
        }

        $data = $m->with($this->with_column)->get()->toArray();

        if($listData && method_exists($this, $listData))
        {
            $data = $this->$listData($data);
            $formatData = false;
        }else
        {
            $formatData = method_exists($this, 'exportFormatData')?function($val){
                return $this->exportFormatData($val);
            }:false;
        }
		$ret = $es->export($data,$formatData);
		return $this->success($ret);
    }

}