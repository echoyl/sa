<?php

namespace Echoyl\Sa\Services\dev\crud;

/**
 * 后台crud基础类 都走这个
 *
 * @method mixed encode() 渲染数据到数据库中
 * @method mixed decode() 渲染数据到前端页面
 */
interface CrudInterface
{
    public function encode($options);

    public function decode($options);
}
