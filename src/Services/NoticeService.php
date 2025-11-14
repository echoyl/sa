<?php

namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\perm\Notice;

class NoticeService
{
    public static function add($notice)
    {
        $model = new Notice;
        $model->insert($notice);

    }

    public static function notification($notice, $user_ids = [])
    {
        $notice['type'] = 'notification';
        if (! empty($user_ids)) {
            foreach ($user_ids as $user_id) {
                $notice['user_id'] = $user_id;
                self::add($notice);
            }
        } else {
            self::add($notice);
        }

    }

    // type:notification | message | event
    public static function get()
    {

        $model = new Notice;
        $user = AdminService::user();
        $list = $model->where(['user_id' => $user['id'], 'read' => 0])->orderBy('id', 'desc')->get()->toArray();

        $data = [];

        foreach ($list as $val) {
            $data[] = [
                'datetime' => $val['created_at'],
                'id' => $val['id'],
                'title' => $val['title'],
                'type' => $val['type'],
                'read' => $val['read'] == 1 ? true : false,
            ];
        }

        return $data;
    }

    public static function clear($id = 0, $type = 'notification')
    {
        $model = new Notice;
        if ($id) {
            $model->where(['id' => $id])->update(['read' => 1]);
        } else {
            $model->where(['type' => $type])->update(['read' => 1]);
        }

    }
}
