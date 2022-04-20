<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Echoyl\Sa\Services\AliyunService;
use Echoyl\Sa\Services\UploadService;

class UploaderController extends Controller
{
	public function index(Request $request)
	{
		$us = new UploadService();
		$type = $request->input('isFile',0);
		$res = $us->store($request,'file',$type?1:0,true);
		if($res['code'])
		{
			return $res;
		}else
		{
			return ['code'=>0,'msg'=>'上传成功','data'=>['value'=>$res['data'],'url'=>$res['data'],'src'=>tomedia($res['data'])]];
		}
	}
	
	public function video(Request $request)
    {
		$us = new UploadService;
		return $us->video($request);
    }

	public function createUploadVideo()
    {
        $as = new AliyunService;
        //d(webapi_request('title'),webapi_request('name'));
        $ret = $as->createUploadVideo(request('title'),request('name'));
		if(!$ret['code'])
		{
			return $ret;
		}else
		{
			return ['code'=>0,'msg'=>$ret['msg'],'data'=>''];
		}
        
    }

    public function refreshUploadVideo()
    {
        $as = new AliyunService;
        //d(webapi_request('title'),webapi_request('name'));
        $ret = $as->refreshUploadVideo(request('VideoId'));
        return $ret;
    }

	public function getVideoUrl()
    {
        $as = new AliyunService;
        //d(webapi_request('title'),webapi_request('name'));
		$id = request('VideoId');
		if(strpos($id,'http') !== false || strpos($id,'https') !== false)
        {
            return ['code'=>0,'msg'=>'','data'=>$id];
        }
        return ['code'=>0,'msg'=>'','data'=>$as->getVideoUrl($id)];
    }

}
