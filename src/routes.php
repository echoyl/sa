<?php

use Echoyl\Sa\Services\dev\DevService;
use Illuminate\Support\Facades\Route;
Route::group(['namespace'=>'\Echoyl\Sa\Http\Controllers','prefix' => env('APP_PREFIX', '')], function () {
    //图片处理显示
    Route::get('img/storage/{path}', 'ImageController@show')->where('path', '.*');
});

Route::group(['namespace'=>'\Echoyl\Sa\Http\Controllers\admin','prefix' => env('APP_PREFIX', '') . env('APP_ADMIN_PREFIX','')], function () {
    //默认暴露这些公用路由
    Route::middleware('api')->group(function () {
        Route::middleware(['echoyl.remember', 'auth:sanctum'])->group(function () {

            Route::get('setting', 'IndexController@setting');
            Route::group(['namespace' => 'dev', 'prefix' => 'dev'], function () {
                Route::get('menu/clearCache', 'MenuController@clearCache');//删除菜单缓存
            });
            
            Route::post('sms', 'SmsController@sms');

            //需要登录的路由
            Route::middleware(['echoyl.sa'])->group(function () {
                Route::any('currentUser', 'IndexController@currentUser');
                Route::any('notice', 'IndexController@notice');
                Route::any('clearNotice', 'IndexController@clearNotice');
                //Route::any('index/workplace', 'IndexController@workplace');
                Route::any('helper/pca', 'HelperController@pca');
                
                //用户修改信息
                //Route::any('index/user', 'IndexController@user'); 
                //所有权限及角色权限
                Route::any('perm/role/perms', 'perm\RoleController@perms'); 
                //需要检测权限的路由
                Route::middleware(['echoyl.permcheck'])->group(function () {
                    //上传文件
                    Route::post('uploader/index', 'UploaderController@index'); 
                    //视频相关
                    Route::post('uploader/video', 'UploaderController@video'); //上传视频文件路由
                    Route::post('uploader/createUploadVideo', 'UploaderController@createUploadVideo');
                    Route::post('uploader/refreshUploadVideo', 'UploaderController@refreshUploadVideo');
                    Route::post('uploader/getVideoUrl', 'UploaderController@getVideoUrl');
                    DevService::aliasRouteSystem();
                    //开发工具使用
                    Route::group(['namespace' => 'dev', 'prefix' => 'dev'], function () {
                        Route::any('setting', 'SettingController@setting');
                        Route::post('menu/moveTo', 'MenuController@moveTo');
                        Route::post('menu/copyTo', 'MenuController@copyTo');
                        Route::post('menu/tableConfig', 'MenuController@tableConfig');
                        Route::post('menu/formConfig', 'MenuController@formConfig');
                        Route::post('menu/otherConfig', 'MenuController@otherConfig');

                        //导出导入
                        Route::post('menu/export', 'MenuController@export');
                        Route::post('menu/import', 'MenuController@import');
                        //table排序
                        Route::post('menu/sortTableColumns', 'MenuController@sortTableColumns');
                        //table 编辑或新增单个列
                        Route::post('menu/editTableColumn', 'MenuController@editTableColumn');
                        //table 删除列
                        Route::post('menu/deleteTableColumn', 'MenuController@deleteTableColumn');

                        Route::resource('menu', 'MenuController');
                        Route::post('model/quickCreate', 'ModelController@quickCreate');
                        //导出导入
                        Route::post('model/export', 'ModelController@export');
                        Route::post('model/import', 'ModelController@import');
                        
                        Route::post('model/createModelSchema', 'ModelController@createModelSchema');
                        Route::post('model/createModelFile', 'ModelController@createModelFile');
                        Route::post('model/createControllerFile', 'ModelController@createControllerFile');
                        Route::post('model/copyToFolder', 'ModelController@copyToFolder');
                        Route::resource('model', 'ModelController');
                        Route::resource('relation', 'RelationController');
                    });
                    
                });
                //退出登录
                Route::any('index/logout', 'IndexController@logout'); 
            });
        });
        //登录
        Route::any('login', 'LoginController@index');
        //验证码
        Route::get('captcha', 'LoginController@captcha');
    });
});
