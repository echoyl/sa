<?php
/*
 * @Author: echoyl yliang_1987@126.com
 * @Date: 2022-09-27 16:37:08
 * @LastEditors: echoyl yliang_1987@126.com
 * @LastEditTime: 2022-11-18 13:36:13
 * @FilePath: \donglifengdianchi\vendor\echoyl\sa\src\Http\Controllers\admin\wechat\PayController.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */
namespace Echoyl\Sa\Http\Controllers\admin\wechat;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\Pay;

class PayController extends CrudController
{
	//var $with_column = ['category'];

    public function __construct()
	{
		$this->model = new Pay();

        $this->parse_columns = [
            ['name' => 'state', 'type' => 'state', 'default' => 'enable'],
        ];

	}

	public function listData(&$data)
	{
		foreach($data as $key=>$val)
		{
			$data[$key]['cert'] = '';
			$data[$key]['key'] = '';
		}
	}

	public function postData(&$item)
	{
		if(isset($item['cert']))
		{
			unset($item['cert']);
		}
		if(isset($item['key']))
		{
			unset($item['key']);
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
	public function afterPost($id = 0)
	{
		//写入文件
		$data = $this->model->where(['id'=>$id])->first();

		$cert_path = base_path('cert/cert_'.$data['id'].'.pem');
		//d($cert_path);
        $key_path = base_path('cert/key_'.$data['id'].'.pem');
        if(file_exists($cert_path))
        {
            //写入文件
            
        }
		$f = fopen($cert_path,'w');
		fwrite($f,$data['cert']);
		fclose($f);
		$f = fopen($key_path,'w');
		fwrite($f,$data['key']);
		fclose($f);

        return;
	}
}
