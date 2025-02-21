<?php
namespace Echoyl\Sa\Http\Controllers\admin\wechat\offiaccount;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\offiaccount\Template;
//customer namespace start
use Echoyl\Sa\Models\wechat\offiaccount\Account;
use Echoyl\Sa\Services\WechatService;
use Illuminate\Support\Arr;
//customer namespace end

class TemplateController extends CrudController
{
	//customer property start
	
	//customer property end
    public function __construct()
	{
		parent::__construct();
		$this->with_column = [
		    'account',
		];
		$this->model = new Template();
		$this->model_class = Template::class;
		//customer construct start
		
		//customer construct end
	}

	//customer code start
	public function getTemplate()
	{
		$appid = request('appid');
		if(!$appid)
		{
			return $this->failMsg('请先选择公众号');
		}
		$account = Account::where(['appid'=>$appid])->first();
		if(!$account)
		{
			return $this->failMsg('账号不存在');
		}
		$ret = WechatService::getOffiaccountTemplate($account['id']);
		if($ret['code'])
		{
			return $this->failMsg($ret['msg']);
		}
		//d($content);
		$templates = $ret['data']['template_list'];
		$inserts = [];
		foreach($templates as $key=>$template)
		{
			$content = Arr::get($template,'content');
			//读取key 匹配 {{key.DATA}} 中的key
			preg_match_all('/{{(.*?)\.DATA}}/', $template['content'], $matches);
        	$keys = $matches[1];
			$_keys = [];
			if(!empty($keys))
			{
				foreach($keys as $k)
				{
					$_keys[$k] = '';
				}
			}
			$inserts[] = [
				"template_id"=>Arr::get($template,'template_id'),
				"title"=>Arr::get($template,'title'),
				"primary_industry"=>Arr::get($template,'primary_industry'),
				"deputy_industry"=>Arr::get($template,'deputy_industry'),
				"content"=>$content,
				"example"=>Arr::get($template,'example'),
				"appid"=>$appid,
				'keys'=>!empty($_keys)?json_encode($_keys):'',
			];
		}
		Template::upsert($inserts,['template_id','appid']);
		return $this->successMsg('同步成功');
	}
	//customer code end
	
}
