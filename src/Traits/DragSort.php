<?php

namespace Echoyl\Sa\Traits;

use Illuminate\Support\Arr;

trait DragSort
{
	/**
	 * 获取表中最大排序值
	 *
	 * @param string $field 排序字段
	 * @param [type] $getModel 检索获取最大排序值的函数
	 * @return void
	 */
	public function getMaxDisplayorder($field = 'displayorder', $getModel = null)
	{
		if (!property_exists($this, 'model_class')) {
			return 0;
		}
		$model = $this->getModel();
		$model = $this->getDragModel($model);
		$model = $getModel ? $getModel($model) : $model;
		return $model->max($field) + 1;
	}

	/**
	 * 获取排序操作的实例化对象，自定义检索数据
	 * 在控制器中重写该方法可以实现数据过滤，比如区分平台数据 只修改当前用户所属平台相同的数据
	 * @param [type] $model 实例化后的模型对象
	 * @param [type] $active_data 当前drag的数据
	 * @return any
	 */
	public function getDragModel($model, $active_data = null)
	{
		return $model;
	}

	public function dragSort()
	{
		//读取active 和 over数据 将over的displayorder赋值给active，然后active和over之间的displayorder全部位移1位
		$active = request('active');
		$over = request('over');
		$active_id = Arr::get($active, 'id', 0);
		$over_id = Arr::get($over, 'id', 0);

		$model = $this->getModel();
		$active_data = $model->find($active_id);
		$over_data = $model->find($over_id);
		if (!$active_data || !$over_data) {
			return $this->failMsg('排序错误，无数据');
		}
		$active_displayorder = $active_data->displayorder;
		$over_displayorder = $over_data->displayorder;
		$upsert = [
			[
				'id' => $active_id,
				'displayorder' => $over_displayorder
			]
		];
		$type = $active_displayorder > $over_displayorder ? 'increment' : 'decrement';

		$between_sort = $type == 'decrement' ? [$active_displayorder, $over_displayorder] : [$over_displayorder, $active_displayorder];
		//d($type,$between_sort);
		$model = $this->getDragModel($model, $active_data);
		//读取active和over之间的所有数据
		$between = $model->whereBetween('displayorder', $between_sort)->get();
		foreach ($between as $v) {
			if ($v->id != $active_id) {
				$new_sort = $type == 'increment' ?
					$v->displayorder + 1 :
					$v->displayorder - 1;
				$upsert[] = [
					'id' => $v->id,
					'displayorder' => $new_sort
				];
			}
		}
		$this->model_class::upsert($upsert, ['id'], ['displayorder']);
		return $this->success();
	}
}
