<?php
namespace Echoyl\Sa\Services\web;

use Echoyl\Sa\Services\WebMenuService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;

class UrlService
{
    /**
     * Undocumented function
     * 网页前端的url生成器 主要是 生成 固定模块的链接生成
     * @param [array] $menu 菜单
     * @param [int] $id 内容id
     * @return void
     */
    public static function create($menu, $id = 0, $cid = 0,$link = '')
    {
        if($link)
        {
            //如果有外链的话 直接返回外链
            return $link;
        }
        if ($id) {
            //如果有id的话那么就是详情
            $is_single_category = Arr::get($menu,'specs.is_single_category',0);
            $is_suffix_none = Arr::get($menu,'specs.is_suffix_none',0);
            $ms = new WebMenuService;
            //$selected_cids = $ms->getSelectedCid($menu);
            $selected_cids = [];
            $mid = $cid && !$is_single_category ? '/' . $cid : '';
            if (!empty($selected_cids)) {
                $mid = '/' . implode('_', $selected_cids);
            }
            
            if($is_single_category && $is_suffix_none)
            {
                $href = $menu['alias'] . $mid . '/' . $id;
            }else
            {
                $href = $menu['alias'] . $mid . '/' . $id . '.html';
            }
        } else {
            //没有的话 那么就是列表了
            $href = $menu['alias'];
        }
        return (new self)->url($href);
    }

    public function url($url,$query = [])
    {
        //加入多语言检测
        $lang = LangService::getLang();
        $url = $lang?implode('/',[$lang,$url]):$url;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return URL::to('/' . env('APP_PREFIX', '') . $url);
        //return url('/' . env('APP_PREFIX', '') . $url,$query);
    }

}
