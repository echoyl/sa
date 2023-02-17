<?php
/*
 * @Author: echoyl yliang_1987@126.com
 * @Date: 2023-02-03 09:55:21
 * @LastEditors: echoyl yliang_1987@126.com
 * @LastEditTime: 2023-02-14 11:25:16
 * @FilePath: \zhihuanpingtai\vendor\echoyl\sa\src\routes.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */
Route::group(['namespace' => '\Echoyl\Sa\Http\Controllers\admin', 'prefix' => env('APP_PREFIX', '') . env('APP_ADMIN_PREFIX','')], function () {

    //默认暴露这些公用路由
    Route::middleware('api')->group(function () {

        Route::middleware(['echoyl.remember', 'auth:sanctum', 'echoyl.sa'])->group(function () {


            Route::any('currentUser', 'IndexController@currentUser');
            Route::any('notice', 'IndexController@notice');
            Route::any('helper/pca', 'HelperController@pca');

            Route::group(['namespace' => 'posts'], function () {
                Route::resource('category', 'CategoryController'); //通用分类
            });
            //需要检测权限的路由
            Route::middleware(['echoyl.permcheck'])->group(function () {

                //系统通用路由
                Route::get('', 'IndexController@index');
                //Route::get('index/getmenus', 'IndexController@getMenus'); //后台获取左侧菜单路由
                Route::any('index/user', 'IndexController@user'); //用户修改信息
                Route::any('index/logout', 'IndexController@logout'); //退出登录

                

                // Route::any('attachment/addGroup', 'AttachmentController@addGroup'); //图片管理列表路由
                // Route::any('attachment/delGroup', 'AttachmentController@delGroup'); //图片管理列表路由
                // Route::resource('attachment', 'AttachmentController'); //图片管理列表路由

                //Route::resource('category', 'CategoryController'); //通用分类
                //Route::resource('posts', 'PostsController'); //内容Posts模块

                Route::post('uploader/index', 'UploaderController@index'); //上传文件路由
                Route::post('uploader/video', 'UploaderController@video'); //上传视频文件路由
                Route::post('uploader/createUploadVideo', 'UploaderController@createUploadVideo');
                Route::post('uploader/refreshUploadVideo', 'UploaderController@refreshUploadVideo');
                Route::post('uploader/getVideoUrl', 'UploaderController@getVideoUrl');

                Route::group(['namespace' => 'posts'], function () {
                    //文章模型的通用路由，自定义需要定义在这个路由前面
                    Route::resource('{path}/posts', 'PostsController');
                    Route::resource('{path}/category', 'CategoryController');
                });

                Route::group(['namespace' => 'perm', 'prefix' => 'perm'], function () {
                    Route::resource('role', 'RoleController');
                    Route::resource('user', 'UserController');
                    Route::resource('log', 'LogController');
                });
                Route::group(['namespace' => 'setting', 'prefix' => 'setting'], function () {
                    Route::any('base', 'SettingController@base');
                    Route::any('web', 'SettingController@web');
                });

                Route::group(['namespace' => 'wechat', 'prefix' => 'wechat'], function () {
                    Route::group(['namespace' => 'offiaccount', 'prefix' => 'offiaccount'], function () {
                        Route::resource('account', 'AccountController');

                        Route::post('user/syncUser', 'UserController@syncUser');
                        Route::get('user/_syncUser', 'UserController@_syncUser');
                        Route::resource('user', 'UserController');
                    });
                    Route::group(['namespace' => 'miniprogram', 'prefix' => 'miniprogram'], function () {
                        Route::resource('account', 'AccountController');
                        Route::resource('user', 'UserController');
                    });

                    Route::resource('pay', 'PayController');
                    

                    // Route::get('menu/sync', 'MenuController@sync');
                    // Route::post('menu/saveAndPub', 'MenuController@saveAndPub');
                    // Route::post('menu/pub', 'MenuController@pub');
                    // Route::resource('menu', 'MenuController');
                    // Route::get('wx/syncUser', 'WxController@syncUser');
                    // Route::any('wx/config', 'WxController@config');
                    // Route::any('wx/pay', 'WxController@pay');
                    // Route::resource('wx', 'WxController');
                    // Route::any('wxapp/config', 'WxappController@config');
                    // Route::resource('wxapp', 'WxappController');
                });
                Route::resource('tool', 'ToolController');
            });    
        });
        Route::any('login', 'LoginController@index');
        Route::get('captcha', 'LoginController@captcha');
    });
});
