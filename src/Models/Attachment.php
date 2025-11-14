<?php

namespace Echoyl\Sa\Models;

class Attachment extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'attachment';

    public $timestamps = false;

    public function getAttachment($ids)
    {
        if (! $ids) {
            return [];
        }
        if (! is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $data = $this->whereIn('id', $ids)->get()->toArray();
        $a_d = [];
        foreach ($data as $key => $val) {
            $thumb = $val['thumb_url'];
            if ($thumb) {
                $val['thumb_url'] = tomedia($thumb);
            } else {
                $val['thumb_url'] = './dist/style/images/'.$val['ext'].'.png';
            }
            $val['url'] = tomedia($val['url']);
            $a_d[$val['id']] = $val;
        }
        // 按照属性排列
        $ret = [];
        foreach ($ids as $val) {
            if (isset($a_d[$val])) {
                $ret[] = $a_d[$val];
            }
        }

        return $ret;
    }
}
