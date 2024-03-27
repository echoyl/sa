<?php

namespace Echoyl\Sa\Services\admin;

use Echoyl\Sa\Models\socket\Log;
use Echoyl\Sa\Services\AdminService;
use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

class SocketService
{
    public static function getModel()
    {
        if(Schema::hasTable('socket_log'))
        {
            return new Log;
        }
        return false;
    }
    /**
     * 和admin绑定
     *
     * @param [type] $client_id
     * @return void
     */
    public static function bind($client_id,$token)
    {
        $accessToken = PersonalAccessToken::findToken($token);
        //这里先不检测token是否过期
        if(!$accessToken || $accessToken['name'] != 'admin')
        {
            return;
        }

        $token_id = $accessToken['id'];
        $usr_id = $accessToken['tokenable_id'];

        $model = self::getModel();

        if(!$model)
        {
            return;
        }

        $get = $model->where(['token_id' => $token_id])->first();
        if ($get) {
            //已连接过更新 client_id
            $model->where(['id' => $get['id']])->update(['client_id' => $client_id, 'state' => 1]);
        } else {
            $model->insert([
                'client_id' => $client_id, 
                'state' => 1, 
                'user_id' => $usr_id, 
                'token_id' => $token_id, 
                'created_at' => now()
            ]);
        }

        //绑定成功后发送消息
        Gateway::sendToClient($client_id, json_encode(['type' => 'info', 'data' => ['message'=>'bind success']]));

        return true;
    }

    public static function logout($client_id)
    {
        $model = self::getModel();

        if(!$model)
        {
            return;
        }

        $model->where(['client_id'=>$client_id])->delete();
        return;
    }

    public static function sendToToken($token_id,$message)
    {
        return self::send($token_id,$message,'token');
    }

    public static function sendToUser($user_id,$message)
    {
        return self::send($user_id,$message);
    }

    /**
     * 查询绑定的client_id 发送socket消息
     *
     * @param integer $id token_id 或者 user_id
     * @param [type] $message 发送的消息信息
     * @param 'user' | 'token' $type
     * @return void
     */
    public static function send($id = 0 ,$message,$type = 'user')
    {
        $model = self::getModel();

        if(!$model)
        {
            return;
        }

        if(!in_array($type,['user','token']))
        {
            return;
        }

        $where = [];

        if($type == 'user')
        {
            $where = ['user_id'=>$id];
        }elseif($type == 'token')
        {
            $where = ['token_id'=>$id];
        }

        $logs = $model->where($where)->get()->toArray();

        foreach($logs as $log)
        {
            $client_id = $log['client_id'];
            if(Gateway::isOnline($client_id))
            {
                Gateway::sendToClient($client_id, json_encode($message));
            }
        }
        return;
    }

    
}
