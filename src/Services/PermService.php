<?php
namespace Echoyl\Sa\Services;
class PermService
{
    static public $allPerms = array();
	static public $getLogTypes = array();
	static public $formatPerms = array();
	public $user_perms = '';
	public $role_perms = '';
	public function __construct($user_perms = '',$role_perms = '')
	{
		$this->user_perms = $user_perms;
		$this->role_perms = $role_perms;
	}
	public function allPerms() 
	{
		

		if (empty(self::$allPerms)) 
		{
			$perms = [
				'setting'=>$this->perm_setting(),
				'perm' => $this->perm_perm(),
				'wechat' => $this->perm_wechat(),
				//'attachment'=>$this->perm_attachment(),
			];
			if(file_exists(app_path('Services/PermService.php')))
			{
				$ps = new \App\Services\PermService();
				$perms = array_merge($perms,$ps->allPerms());
			}

			$perms['attachment'] = $this->perm_attachment();
			$perms['uploader'] = $this->perm_uploader();
			
			self::$allPerms = $perms;
		}
		return self::$allPerms;
	}

	// protected function perm_news()
    // {
    //     return [
	// 		'text' => '文章管理', 
	// 		'news'=>$this->normal('文章管理')
	// 		,'category'=>$this->normal('分类管理')
	// 	];
	// }

    protected function perm_perm() 
	{
		return [
			'text' => '后台权限', 
			'role' =>$this->normal('角色'),
			'user' =>$this->normal('用户'),
			'log' =>$this->normal('日志'),
		];
	}

	protected function perm_wechat() 
	{
		return [
			'text' => '微信相关', 
			'sets' =>[
				'text' => '配置', 
				'wxconfig' => '公众号配置',
				'wxpay' => '支付配置',
				'wxappconfig' => '小程序配置',
			],
			'menu' =>$this->normal('自定义菜单',['sync'=>'拉取菜单','saveAndPub'=>'保存并发布','pub'=>'发布']),
			'wx' =>$this->normal('公众号用户',['syncUser'=>'同步用户信息']),
			'wxapp' =>$this->normal('小程序用户'),
		];
	}


	protected function perm_setting() 
	{
		return [
			'text' => '设置'
			,'banner'=>$this->normal('轮播图')
			,'banner'=>$this->normal('外链')
			,'sets'=>[
				'text' => '配置', 
				'web' => '网站设置',
				'webindex' => '首页设置',
			]
		];
	}

	protected function perm_uploader()
	{
		return [
			'text' => '上传管理', 
			'index' => '上传图片', 
			'video'=>'上传视频',
			'createUploadVideo'=>'阿里云视频上传',
			'refreshUploadVideo'=>'阿里云视频上传（重传）',
			'getVideoUrl'=>'获取播放地址',
		];
	}

	protected function perm_attachment()
	{
		return [
			'text' => '图片文件管理', 
			'index' => '查看列表', 
			'add'=>'新增',
			'edit'=>'修改',
			'destroy' => '删除',
			'addGroup'=>'添加文件夹',
			'delGroup'=>'删除文件夹',
		];
	}

	public function  normal($text,$more = [])
	{
		return array_merge([
			'text' => $text, 
			'index' => '查看列表', 
			'show'=>'查看详情',
			'add'=>'新增',
			'edit'=>'修改',
			'destroy' => '删除'
		],$more);
	}

	public function check_perm($permtypes = '') 
	{
		$check = true;
		if (empty($permtypes)) 
		{
			return false;
		}
		$permtypes = str_replace("\\",'.',$permtypes);
		
		if (strpos($permtypes, '&') === false && strpos($permtypes, '|') === false) 
		{
			$check = $this->check($permtypes);
		}
		else if (strpos($permtypes, '&') !== false) 
		{
			$pts = explode('&', $permtypes);
			foreach ($pts as $pt ) 
			{
				$check = $this->check($pt);
				if ($check) 
				{
					continue;
				}
				break;
			}
		}
		else if (strpos($permtypes, '|') !== false) 
		{
			$pts = explode('|', $permtypes);
			foreach ($pts as $pt ) 
			{
				$check = $this->check($pt);
				if (!($check)) 
				{
					continue;
				}
				break;
			}
		}
		return $check;
	}
	//检测是否有权限 如果大的权限没有的话表示不检测权限 如果有大权限设置 没找到的话表示没有权限
	private function check($permtype = '') 
	{
		if (empty($permtype)) 
		{
			return false;
		}
		$perms = explode(',', $this->user_perms);
		$role_perms = explode(',',$this->role_perms);
		$perms = array_intersect($role_perms,$perms);
		if (empty($perms)) 
		{
			return false;
		}
		$is_xxx = $this->check_xxx($permtype);
		$allPerm = $this->allPerms();
		$first = explode('.',$permtype);
		if(!isset($allPerm[$first[0]]))
		{
			return true;
		}
		if ($is_xxx) 
		{
			if (!in_array($is_xxx, $perms)) 
			{
				return false;
			}
		}
		else if (!in_array($permtype, $perms)) 
		{
			return false;
		}
		return true;
	}
	public function check_xxx($permtype) 
	{
		if ($permtype) 
		{
			$allPerm = $this->allPerms();
			$permarr = explode('.', $permtype);
			if (isset($permarr[3])) 
			{
				$is_xxx = ((isset($allPerm[$permarr[0]][$permarr[1]][$permarr[2]]['xxx'][$permarr[3]]) ? $allPerm[$permarr[0]][$permarr[1]][$permarr[2]]['xxx'][$permarr[3]] : false));
			}
			else if (isset($permarr[2])) 
			{
				$is_xxx = ((isset($allPerm[$permarr[0]][$permarr[1]]['xxx'][$permarr[2]]) ? $allPerm[$permarr[0]][$permarr[1]]['xxx'][$permarr[2]] : false));
			}
			else if (isset($permarr[1])) 
			{
				$is_xxx = ((isset($allPerm[$permarr[0]]['xxx'][$permarr[1]]) ? $allPerm[$permarr[0]]['xxx'][$permarr[1]] : false));
			}
			else 
			{
				$is_xxx = false;
			}
			if ($is_xxx) 
			{
				$permarr = explode('.', $permtype);
				array_pop($permarr);
				$is_xxx = implode('.', $permarr) . '.' . $is_xxx;
			}
			return $is_xxx;
		}
		return false;
	}
	
	public function formatPerms() 
	{
		if (empty(self::$formatPerms)) 
		{
			$perms = $this->allPerms();
			$array = array();
			foreach ($perms as $key => $value ) 
			{
				if (is_array($value)) 
				{
					foreach ($value as $ke => $val ) 
					{
						if (!is_array($val)) 
						{
							$array['parent'][$key][$ke] = $val;
						}
						if (is_array($val) && ($ke != 'xxx')) 
						{
							foreach ($val as $k => $v ) 
							{
								if (!is_array($v)) 
								{
									$array['son'][$key][$ke][$k] = $v;
								}
								if (is_array($v) && ($k != 'xxx')) 
								{
									foreach ($v as $kk => $vv ) 
									{
										if (!is_array($vv)) 
										{
											$array['grandson'][$key][$ke][$k][$kk] = $vv;
										}
									}
								}
							}
						}
					}
				}
			}
			self::$formatPerms = $array;
		}
		return self::$formatPerms;
	}

}
