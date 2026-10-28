## 版本

v1.3.8 2026/10/26

## 安装

```bash

$ laravel new example-app

$ cd example-app

#修改配置文件中 ./.env

#APP_NAME=你的项目名称如：deadmin 必须英文，生成表名使用该名称作为前缀
#DB_DATABASE=你的数据库名称如：deadmin 并创建该数据库配置数据库账号及密码
#修改文件系统类型
#FILESYSTEM_DISK=public

#修改数据库配置文件 表前缀 ./config/database.php
# 'connections.mysql.prefix'=>'la_'

$ composer require echoyl/sa

#迁移前端静态文件 及 配置文件
$ php artisan deadmin:publish --force

#如果使用的laravel版本 >= 11，还需要额外运行下列命令
$ php artisan vendor:publish --tag=sanctum-migrations

#生成基础的数据文件
$ php artisan migrate

#生成文件文件夹超链
$ php artisan storage:link

```

## 更新

```bash
#更新拓展包
$ composer update echoyl/sa
#发布前端静态文件(强制移除旧文件)
$ php artisan deadmin:publish --update

```

因为 composer 镜像同步速度比较慢 可以手动拉取 [包源码 github 地址](https://github.com/echoyl/sa) 后覆盖 echoyl/sa 文件夹后执行上面的
php artisan deadmin:publish --update 命令覆盖 dist 文件

## 路由缓存

后台菜单对应的路由是运行时根据 `dev_menu` 数据动态生成的。为提高性能，包会把菜单树编译成纯数组缓存到 `bootstrap/cache/sa-routes.php`，后续请求只需 `include` 这个文件再注册路由，不再查库、不再遍历菜单树。

```bash
php artisan sa:route cache                 # 重建动态路由缓存
php artisan sa:route cache --refresh       # 先从数据库刷新菜单/模型数据，再重建
php artisan sa:route cache --laravel       # 同时执行 route:cache（整站路由缓存，启动最快）
php artisan sa:route clear                 # 清理缓存
```

说明：

- 后台新增/修改/删除菜单、生成控制器文件时会自动清理缓存，下一次请求自动重建，无需手动操作
- 部署推荐执行 `php artisan sa:route cache --refresh --laravel`
- 如果执行过 `route:cache`，菜单变更时包会自动 `route:clear` 保证新菜单不 404；需要恢复极致性能时再执行一次 `php artisan sa:route cache --laravel`
- 缓存失效依赖共享缓存里的代次号（`sa_route_cache_generation`）：多机部署时一台机器改菜单，其它机器也会判定缓存过期并各自重建，不会读到旧路由
- `bootstrap/cache/sa-routes.php` 是**节点本地文件**，多机/容器环境建议部署时统一执行一次本命令；如果目录不可写不会报错中断，只会退化成每次请求重新构建路由（并写 warning 日志），可用 `php artisan sa:route cache` 检查是否写入成功
- 不想使用文件缓存可设置 `SA_ROUTE_CACHE=false`（不推荐）

## 服务器配置

需要在 `nginx` 服务器或 web 服务器中配置 rewrite

```nginx
# laravel配置
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
# 默认图片转发 根据配置图片size大小返回图片 可不配置
location /img {
    try_files $uri $uri/ /index.php?$query_string;
}
#后台前端配置
location /antadmin/ {
    # 用于配合 browserHistory使用
    try_files $uri $uri/index.html /antadmin/index.html;
}
```

## 其它

安装已有的功能表，按需安装。数据只安装基础表，实现简化功能，如需要更多功能，请自行安装。

```bash
php artisan deadmin:table                # 显示帮助
php artisan deadmin:table pca            # 省市区
php artisan deadmin:table workflow       # 工作流程
php artisan deadmin:table web            # 网站菜单
php artisan deadmin:table wechat --all                    # 微信全部
php artisan deadmin:table wechat --miniprogram            # 只装小程序
php artisan deadmin:table wechat --offiaccount            # 只装公众号
php artisan deadmin:table wechat --pay                    # 只装支付
php artisan deadmin:table pca --force                     # 强制重建
```

## 前端

安装完毕后 直接访问 `http://域名/antadmin`

默认账号密码 admin 123456

## 修改前端

拉取 [deadmindev](https://github.com/echoyl/deadmindev.git) 这个前端仓库后

```bash
tyarn
npm run start
```
