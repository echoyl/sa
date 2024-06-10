<?php
namespace Echoyl\Sa\Services\dev\crud;

use Echoyl\Sa\Services\dev\crud\fields\Content;
use Echoyl\Sa\Services\dev\crud\fields\Json;
use Echoyl\Sa\Services\dev\crud\fields\Pca;
use Echoyl\Sa\Services\dev\crud\fields\Price;
use Echoyl\Sa\Services\dev\crud\fields\Upload;

/**
 * crud字段的渲染
 * @property \Echoyl\Sa\Services\dev\crud\item\Pca $pca
 */
class CrudService
{
    var $config;
    var $items;
    public function __construct($config)
    {
        $this->config = $config;
        $this->items = [
            'pca'=>Pca::class,
            'json'=>Json::class,
            'image'=>Upload::class,
            'file'=>Upload::class,
            'tinyEditor'=>Content::class,
            'price'=>Price::class,
            'mprice'=>Price::class,
            'mmprice'=>Price::class,
        ];
    }

    public function make($name,$options = [])
    {
        $method = $options['encode']?'encode':'decode';

        //检测字段是否设置
        $fieldname = $this->config['col']['name'];
        $post = $this->config['data'];

        $originData = $post['originData']??[];

        $isset = isset($post[$fieldname])?true:false;
        $val = $isset ? $post[$fieldname] : $this->config['col']['default'];//当前字段的值

        $origin_val = $originData[$fieldname]??false;

        $cls = $this->getClass($name);

        if(!$cls)
        {
            //没有该字段的规则 直接返回data原样数据
            return $post;
        }

        $options = array_merge([
            'isset'=>$isset,
            'val'=>$val,
            'type'=>$name,
            'origin_val'=>$origin_val
        ],$options);
        
        $data = $cls->$method($options);

        return $data;
    }

    public function search($m,$name,$options = [])
    {
        $cls = $this->getClass($name);
        if(!$cls || !method_exists($cls,'search'))
        {
            //没有该字段的规则 直接返回data原样数据
            return $m;
        }

        $m = $cls->search($m,$options);

        return $m;
    }

    /**
     * 获取字段方法
     *
     * @param [type] $name
     * @return BaseField
     */
    public function getClass($name)
    {
        if(!isset($this->items[$name]))
        {
            return false;
        }

        return new $this->items[$name]($this->config);

    }
}