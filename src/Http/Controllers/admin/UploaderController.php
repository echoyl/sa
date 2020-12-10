<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
			return ['code'=>0,'msg'=>'success','data'=>['value'=>$res['data'],'src'=>tomedia($res['data'])]];
		}
	}
	
	public function video(Request $request)
    {
		$us = new UploadService;
		return $us->video($request);
    }
}
