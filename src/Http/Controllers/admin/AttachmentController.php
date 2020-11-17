<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Echoyl\Sa\Models\Attachment;
use Echoyl\Sa\Models\AttachmentGroup;


class AttachmentController extends Controller
{
    //
	var $model;
    public function __construct(Attachment $model)
	{
		$this->model = $model;

	}

    public function index(Request $request)
    {
        $psize = $request->input('limit',28);
		$page = $request->input('page',1);
		$isFile = $request->input('isFile',0);
		$groupId = $request->input('groupId',0);
		$where = ['type'=>0,'group_id'=>$groupId];
		if($isFile == 1)
		{
			//读取文件
			$where = ['type'=>1];
		}

		//先读取文件夹分类
		$groups = (new AttachmentGroup)->where(['parent_id'=>$groupId])->get();
		//再读取父级文件夹
		$parentGroup = (new AttachmentGroup)->parentInfo($groupId);
		$count = $this->model->where($where)->count();
		$list = $this->model->where($where)->orderBy('id','desc')->offset(($page-1)*$psize)->limit($psize)->get()->toArray();
		
		foreach($list as $key=>$val)
		{
			//检测宽高看是长方形还是正方形
			if($val['width'] > $val['height'])
			{
				$list[$key]['height'] = intval(82*$val['height']/$val['width']).'px';
				$list[$key]['width'] = '82px';
			}else
			{
				$list[$key]['width'] = intval(82*$val['width']/$val['height']).'px';
				$list[$key]['height'] = '82px';
			}
			$list[$key]['size'] = formatSize($val['size']);
		}

		return ['code'=>0,'msg'=>'','count'=>$count,'data'=>$list,'groups'=>$groups,'parentGroup'=>$parentGroup];	

    }

    public function show(Request $request)
    {
        return $this->post($request);
    }

    public function store(Request $request)
    {
        return $this->post($request);
	}
	
    public function post(Request $request)
    {
		$id = $request->input('id',0);
		$item = $this->model->where(['id'=>$id])->first();
		if (!empty($item)) {

		}else
		{
			$item = [];
		}
		$cates = '';
		$item['categoryarr'] = json_encode($cates);
		$type = $request->input('actype');
		
		if ($request->isMethod('post')) {
			$post = $request->all();
			switch($type)
			{
				case 'status':
					$data = [
						'status'=>intval($post['status']),
					];
				break;
				case 'displayorder':
					$data = [
						'displayorder'=>intval($post['displayorder']),
					];
				break;
				default:
					$data = [
						'name'=>$post['name'],
					];
			}
            //var_dump($data);exit;
			if (!empty($id)) {
				$this->model->where(['id'=>$id])->update($data);
			}
			 else {
				 $data['created_at'] = date("Y-m-d H:i:s");
				$id = $this->model->insert($data);
			}
			return ['code'=>0,'msg'=>''];
		}

		return ['code'=>0,'msg'=>'','data'=>$item];
    }

	public function destroy(Request $request)
	{
		$ids = $request->input('ids','');
		if (!empty($ids)) {
			$ids = explode('.',$ids);
			$items = $this->model->whereIn('id',$ids)->get();
			foreach($items as $val)
			{
				//删除图片文件
				@unlink(storage_path('app/public/'.$val['url']));
				@unlink(storage_path('app/public/'.$val['thumb_url']));
				$val->delete();
			}
			return ['code'=>0,'msg'=>'success'];
		}
		return ['code'=>1,'msg'=>'参数错误'];
	}
	
	public function addGroup(Request $request)
	{
		$id = $request->input('id',0);
		$model = new AttachmentGroup;
		if ($request->isMethod('post')) {
			$data = [];
			$data['title'] = $request->input('name','');
			if (!empty($id)) {
				$model->where(['id'=>$id])->update($data);
			}else 
			{
				$data['created_at'] = date("Y-m-d H:i:s");
				$data['parent_id'] = $request->input('parent_id',0);
				$model->insert($data);
			}
			return ['code'=>0,'msg'=>''];
		}

		return ['code'=>1,'msg'=>'err'];
	}
	public function delGroup(Request $request)
	{
		$ids = $request->input('ids','');
		if (!empty($ids)) {
			$model = new AttachmentGroup;
			$ids = explode('.',$ids);
			$items = $model->whereIn('id',$ids)->get();
			foreach($items as $val)
			{
				//删除文件夹
				$val->delete();
			}
			return ['code'=>0,'msg'=>'success'];
		}
		return ['code'=>1,'msg'=>'参数错误'];
	}
}
