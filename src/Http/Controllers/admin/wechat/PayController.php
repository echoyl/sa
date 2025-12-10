<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\Pay;

// customer namespace start

// customer namespace end

class PayController extends CrudController
{
    public function __construct()
    {
        $this->model = new Pay;

        $this->parse_columns = [];

    }

    // customer code start
    public function listData(&$data)
    {
        foreach ($data as $key => $val) {
            $data[$key]['cert'] = '';
            $data[$key]['key'] = '';
        }
    }

    public function postData(&$item)
    {
        if (isset($item['cert'])) {
            unset($item['cert']);
        }
        if (isset($item['key'])) {
            unset($item['key']);
        }

    }

    public function afterPost($id = 0)
    {
        // 写入文件
        $data = $this->model->where(['id' => $id])->first();

        $cert_path = base_path('cert/cert_'.$data['id'].'.pem');
        // d($cert_path);
        $key_path = base_path('cert/key_'.$data['id'].'.pem');
        if (file_exists($cert_path)) {
            // 写入文件

        }
        if ($data['cert'] || $data['key']) {
            $f = fopen($cert_path, 'w');
            fwrite($f, $data['cert']);
            fclose($f);
            $f = fopen($key_path, 'w');
            fwrite($f, $data['key']);
            fclose($f);
        }
        // 将cert 和key 清除
        $this->model->where(['id' => $id])->update(['cert' => '', 'key' => '']);

    }
    // customer code end
}
