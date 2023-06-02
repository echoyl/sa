<?php
use Illuminate\Support\Facades\Route;
Route::group(['namespace'=>'\Echoyl\Sa\Http\Controllers','prefix' => env('APP_PREFIX', '')], function () {

    Route::get('img/storage/{path}', 'ImageController@show')->where('path', '.*');
});

Route::group(['namespace'=>'\Echoyl\Sa\Http\Controllers\admin','prefix' => env('APP_PREFIX', '') . env('APP_ADMIN_PREFIX','')], function () {

    //默认暴露这些公用路由
    Route::middleware('api')->group(function () {

        Route::middleware(['echoyl.remember', 'auth:sanctum', 'echoyl.sa'])->group(function () {


            Route::any('currentUser', 'IndexController@currentUser');
            Route::any('notice', 'IndexController@notice');
            Route::any('clearNotice', 'IndexController@clearNotice');
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

                // Route::group(['namespace' => 'posts'], function () {
                //     //文章模型的通用路由，自定义需要定义在这个路由前面 将这个路由删除，优先执行导致后面覆盖后的路由不生效了
                //     Route::resource('{path}/posts', 'PostsController');
                //     Route::resource('{path}/category', 'CategoryController');
                // });

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

                /**
                 * 开发工具使用
                 */
                Route::group(['namespace' => 'dev', 'prefix' => 'dev'], function () {
                    Route::resource('menu', 'MenuController');
                    Route::post('model/createModelSchema', 'ModelController@createModelSchema');
                    Route::post('model/createModelFile', 'ModelController@createModelFile');
                    Route::post('model/createControllerFile', 'ModelController@createControllerFile');
                    Route::post('model/copyToFolder', 'ModelController@copyToFolder');
                    Route::resource('model', 'ModelController');
                    Route::resource('relation', 'RelationController');
                });
                Route::resource('tool', 'ToolController');
                
            });
            Route::any('index/logout', 'IndexController@logout'); //退出登录
        });
        Route::any('login', 'LoginController@index');
        Route::get('captcha', 'LoginController@captcha');
    });
});
