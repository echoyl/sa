<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class Content extends BaseField
{
    var $type;//增加type支持。如果是mdeditor 另外匹配图片
    public function encode($options = [])
    {
        $name = $this->name;
        $data = $this->config['data'];
        $val = $options['val'];
        $origin_val = $options['origin_val'];
        $this->type = $options['type'];
        
        $src_val = $this->getSrc($val);
        $origin_src_val = $this->getSrc($origin_val);

        $this->diffFileVal($src_val,$origin_src_val);

        $val = $this->replace($val);
        
        $data[$name] = $val;

        return $data;
    }

    /**
     * 获取src匹配次数，然后tmp替换为正式路径
     *
     * @param [type] $content
     * @return void
     */
    public function replace($content)
    {
        $matches = $this->getMatch($content);

        $replace = rtrim(env('APP_URL'),'/').'/storage/';

        $search = $replace.$this->tmp_prefix;

        foreach($matches as $src)
        {
            $content = str_replace($search,$replace,$content);
        }
        return $content;
    }

    /**
     * 获取内容中有src匹配的数据
     *
     * @param [type] $content
     * @return array
     */
    public function getMatch($content)
    {
        $patterns = [];
        if($this->type == 'mdEditor')
        {
            $patterns[] = '/\((.*)\)/i';
        }else
        {
            $patterns[] = '/<img[^>]*src=[\'"]([^\'"]+)[\'"]/i';
            //增加检测a标签的href 富文本编辑器现在支持传入文件
            $patterns[] = '/<a[^>]*href=[\'"]([^\'"]+)[\'"]/i';
            //增加source标签 的src
            $patterns[] = '/<source[^>]*src=[\'"]([^\'"]+)[\'"]/i';
        }

        $_matches = [];

        foreach($patterns as $pattern)
        {
            preg_match_all($pattern, $content, $matches);
            $_matches = array_merge($_matches,$matches[1]);
        }

        $mt = [];

        $prefix = rtrim(env('APP_URL'),'/').'/storage/';

        foreach($_matches as $src)
        {
            if(strpos($src,$prefix) !== false)
            {
                $mt[] = $src;
            }
        }
        return $mt;
    } 

    /**
     * 获取格式化的src数据
     *
     * @param [type] $content
     * @return array
     */
    public function getSrc($content)
    {
        $data = [];

        $matches = $this->getMatch($content);

        $prefix = rtrim(env('APP_URL'),'/').'/storage/';

        foreach($matches as $src)
        {
            $data[] = [
                'value'=>str_replace($prefix,'',$src),
            ];
        }

        return $data;
    }

}