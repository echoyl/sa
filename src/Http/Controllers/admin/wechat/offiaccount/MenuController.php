<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat\offiaccount;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\offiaccount\Menu;
use Echoyl\Sa\Services\WechatService;

// customer namespace start

// customer namespace end
/**
 * @property \App\Services\echoyl\AdminAppService $service
 */
class MenuController extends CrudController
{
    public function __construct()
    {
        parent::__construct();

        $this->model = new Menu;
        $this->search_config = [
            [
                'name' => 'wechat_offiaccount_id',
                'columns' => [
                    'wechat_offiaccount_id',
                ],
                'where_type' => '=',
            ],
        ];
        // customer construct start

        // customer construct end
    }

    // customer code start
    /**
     * 发布自定义菜单
     *
     * @param  int  $id
     * @return void
     */
    public function pub($id = 0)
    {
        $id = $id ?: request('id');
        $data = $this->model->where(['id' => $id])->first();
        $content = json_decode($data['content'], true);
        foreach ($content as $key => $val) {
            if (isset($val['uid'])) {
                unset($val['uid']);
            }
            if (isset($val['sub_button'])) {
                foreach ($val['sub_button'] as $k => $v) {
                    if (isset($v['uid'])) {
                        unset($v['uid']);
                    }
                    $val['sub_button'][$k] = $v;
                }
            }
            $content[$key] = $val;
        }
        $ret = WechatService::createMenu($content, $data['wechat_offiaccount_id']);
        if (! $ret['code']) {
            $this->model->where([['id', '!=', $id]])->update(['open' => 0]);
            $this->model->where(['id' => $id])->update(['open' => 1]);
        } else {
            return $this->fail([$ret['code'], $ret['msg']]);
        }

        return $this->success($ret['msg']);
    }

    /**
     * 同步拉取自定义菜单
     *
     * @return void
     */
    public function sync()
    {
        set_time_limit(0);
        $wechat_offiaccount_id = request('wechat_offiaccount_id');
        $menu = WechatService::getMenu($wechat_offiaccount_id);
        if ($menu['code']) {
            return $this->fail([$menu['code'], $menu['msg']]);
        }

        $content = [];
        $i = 1;
        foreach ($menu['data'] as $key => $button) {
            $button['uid'] = $i++;
            if (isset($button['sub_button'])) {
                foreach ($button['sub_button'] as $k => $val) {
                    $val['uid'] = $i++;
                    $button['sub_button'][$k] = $val;
                }
            }
            $content[] = $button;
        }
        $this->model->insert([
            'open' => 0,
            'content' => json_encode($content),
            'title' => '当前同步后菜单',
            'created_at' => now(),
            'wechat_offiaccount_id' => $wechat_offiaccount_id,
        ]);

        return $this->success('拉取成功');
    }
    // customer code end
}
