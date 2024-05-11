<?php
namespace Echoyl\Sa\Http\Controllers\admin\wechat;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\Menu;
use Echoyl\Sa\Services\WechatService;

class MenuController extends CrudController
{
    public function __construct()
	{
		$this->model = new Menu();
	}

	public function postData(&$item)
	{
		if(isset($item['id']))
		{
			$item['row'] = [
				'content'=>$item['content'],
				'name'=>$item['title'],
				'id'=>$item['id'],
				'rules'=>null,
				'type'=>'menu'
			];
		}
		return;
	}

	/**
	 * 编辑数据时 检测数据合法性
	 *
	 * @param [type] $item
	 * @return void
	 */
	public function checkPost($item)
	{
		if(false)
		{
			return ['code'=>1,'msg'=>'信息错误，请重试'];
		}
        return;
	}

	/**
	 * 提交数据时检测数据合法性
	 *
	 * @param [type] $data
	 * @param [type] $id
	 * @return void
	 */
	public function beforePost($data,$id = 0)
	{
		if(false)
		{
			return ['code'=>1,'msg'=>'数据错误，请重试'];
		}
        return;
	}

	public function afterPost($id)
	{
		if(request('type') == 'publish')
		{
			return $this->pub($id);
		}
		return;
	}
	public function post($type = 'save')
	{
		request()->merge(['type'=>$type]);
		return parent::post();
	}

	public function saveAndPub($id = 0)
	{
		return $this->post('publish');
	}

	public function pub($id = 0)
	{
		$id = $id?:request('id');
		$data = $this->model->where(['id'=>$id])->first();
		$content = json_decode($data['content']);
		$ret = WechatService::createMenu($content);
		if(!$ret['code'])
		{
			$this->model->where([['id','!=',$id]])->update(['status'=>0]);
			$this->model->where(['id'=>$id])->update(['status'=>1]);
		}
		return $ret;
	}

	public function sync()
	{
		set_time_limit(0);
		$menu = WechatService::getMenu();
		if($menu['code'])
		{
			return $menu;
		}
		if($menu['data']['selfmenu_info']['button'])
		{
			$content = [];
			foreach($menu['data']['selfmenu_info']['button'] as $button)
			{
				if(isset($button['sub_button']))
				{
					$sub_button = [];
					foreach($button['sub_button']['list'] as $val)
					{
						$sub_button[] = $val;
					}
					$content[] = [
						'name'=>$button['name'],
						'sub_button'=>$sub_button
					];
				}else
				{
					$content[] = $button;
				}
			}
			$has = $this->model->where(['status'=>1])->first();
			if($has)
			{
				$this->model->where(['id'=>$has['id']])->update(['content'=>json_encode($content)]);
			}else
			{
				$this->model->insert([
					'status'=>1,
					'content'=>json_encode($content),
					'title'=>'当前菜单',
					'created_at'=>now()
				]);
			}
		}
		return ['code'=>0,'msg'=>'拉取成功'];
	}

	public function handleSearch($search = [])
	{
		$m = $this->model;

		$keyword = request('keyword','');
		if($keyword)
		{
			$m = $m->where([['title','like','%'.urldecode($keyword).'%']]);

		}

		$status = request('status','');
		if($status !== '')
		{
			$m = $m->where('status',$status);

		}
		
		$sdate = request('sdate','');

		if($sdate)
		{
			$m = $m->whereBetween('created_at',[$sdate,date("Y-m-d H:i:s",strtotime($sdate)+3600*24-1)]);
			$search['sdate'] = $sdate;
		}

		$search['status'] = [
			['title'=>'启用','id'=>1],['title'=>'禁用','id'=>0],
		];

		return [$m,$search];

	}

}
