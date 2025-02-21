<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat\offiaccount;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\offiaccount\Templatemessage;


//customer namespace start
use Echoyl\Sa\Events\WxMessageEvent;
use Illuminate\Support\Arr;
//customer namespace end

class TemplatemessageController extends CrudController
{
	//customer property start

	//customer property end
	public function __construct()
	{
		parent::__construct();
		$this->with_column = [
			'template',
			'app',
		];
		$this->model = new Templatemessage();
		$this->model_class = Templatemessage::class;
		//customer construct start

		//customer construct end
	}

	//customer code start
	public function sendmessage()
	{
		if (request()->isMethod('POST')) {
			//解析发送消息内容
			$item = $this->model->find(request('base.id'));
			$openid = request('base.openid.openid');
			$wxappopenid = request('base.wxappopenid.openid');
			$user_id = request('base.user_id');
			$to_id = [];
			if ($user_id) {
				$to_id = ['user_id' => explode(',', $user_id)];
			} elseif ($openid) {
				$to_id = ['openid' => $openid];
			} elseif ($wxappopenid) {
				$to_id = ['wxapp_openid' => $wxappopenid];
			}
			$send_data = request('base.data',[]);
			if (empty($to_id) || empty($send_data)) {
				return $this->failMsg('请选择发送对象和发送数据');
			}
			//解析发送数据
			$_send_data = [];
			foreach($send_data as $key=>$val)
			{
				if(substr($key,0,1) == '.')
				{
					$key = substr($key,1);
				}
				Arr::set($_send_data, $key, $val);
			}
			$event = new WxMessageEvent($item['name'], $_send_data, $to_id);
			$result = event($event);
			return $this->notification($result,['message'=>$event->messages]);
			//return $this->successMsg(is_array($result) ? json_encode($result) : $result, ['message'=>$event->messages,'result'=>$result]);
		} else {
			$ret = $this->post();
			$data = $ret->getData(true);
			$data['data']['data'] = array_flip($data['data']['data']);
			return $this->success($data['data']);
		}
	}
	//customer code end

}
