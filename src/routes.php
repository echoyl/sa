<?php
Route::group(['namespace' => '\Echoyl\Sa\Http\Controllers\admin','prefix'=>env('APP_PREFIX','').'sadmin'], function(){

	//默认暴露这些公用路由 
	Route::middleware('api')->group(function(){

		Route::middleware(['auth:sanctum','echoyl.sa'])->group(function(){
			//系统通用路由
			Route::get('', 'IndexController@index');
			Route::get('index/getmenus', 'IndexController@getMenus');//后台获取左侧菜单路由
			Route::any('index/user', 'IndexController@user');//用户修改信息
			Route::get('index/logout', 'IndexController@logout');//退出登录
			

			Route::any('attachment/addGroup', 'AttachmentController@addGroup');//图片管理列表路由
			Route::any('attachment/delGroup', 'AttachmentController@delGroup');//图片管理列表路由
			Route::resource('attachment', 'AttachmentController');//图片管理列表路由
			
			Route::resource('category', 'CategoryController');//通用分类
			Route::resource('posts', 'PostsController');//内容Posts模块
			
			
			Route::post('uploader/index', 'UploaderController@index');//上传文件路由
			Route::post('uploader/video', 'UploaderController@video');//上传视频文件路由

			Route::group(['namespace' => 'perm', 'prefix' => 'perm'], function(){
                Route::resource('role', 'RoleController');
				Route::resource('user', 'UserController');
				Route::resource('log', 'LogController');
			});
			Route::group(['namespace' => 'setting', 'prefix' => 'setting'], function(){
                Route::any('sets/base', 'SetsController@base');
			});

			Route::group(['namespace' => 'wechat', 'prefix' => 'wechat'], function(){
                Route::any('sets/wxappconfig', 'SetsController@wxappconfig');
                Route::any('sets/wxconfig', 'SetsController@wxconfig');
				Route::any('sets/wxpayconfig', 'SetsController@wxpayconfig');
				
				Route::get('menu/sync', 'MenuController@sync');
				Route::post('menu/saveAndPub', 'MenuController@saveAndPub');
				Route::post('menu/pub', 'MenuController@pub');
				Route::resource('menu', 'MenuController');
				Route::get('wx/syncUser', 'WxController@syncUser');
				Route::resource('wx', 'WxController');
				Route::resource('wxapp', 'WxappController');
			});
			Route::resource('tool', 'ToolController');

		});
		Route::any('login','LoginController@index');
		Route::get('captcha', 'LoginController@captcha');
	});
});
?>

