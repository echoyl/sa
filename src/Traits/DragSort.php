<?php


namespace Echoyl\Sa\Traits;

use Illuminate\Support\Arr;

trait DragSort
{

    public function getMaxDisplayorder($field = 'displayorder')
	{
		if (!property_exists($this, 'model_class')) {
            return 0;
        }
		return $this->model_class::max($field) + 1;
	}

	public function dragSort()
	{
		//读取active 和 over数据 将over的displayorder赋值给active，然后active和over之间的displayorder全部位移1位
		$active = request('active');
		$over = request('over');
		$active_id = Arr::get($active,'id',0);
		$over_id = Arr::get($over,'id',0);

		$model = $this->getModel();
		$active_data = $model->find($active_id);
		$over_data = $model->find($over_id);
		if(!$active_data || !$over_data)
		{
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
		$type = $active_displayorder > $over_displayorder?'increment':'decrement';
		$between_sort = $type == 'decrement' ? [$active_displayorder, $over_displayorder] : [$over_displayorder, $active_displayorder];
		//d($type,$between_sort);
		//读取active和over之间的所有数据
		$between = $model->whereBetween('displayorder',$between_sort)->get();
		foreach($between as $v)
		{
			if($v->id != $active_id)
			{
				$new_sort = $type == 'increment'?
					$v->displayorder + 1 :
					$v->displayorder - 1;
				$upsert[] = [
					'id'=>$v->id,
					'displayorder' => $new_sort
				];
			}
		}	
		$this->model_class::upsert($upsert, ['id'], ['displayorder']);
		return $this->success();
	}

}