<?php

namespace Echoyl\Sa\Services\admin;

use Echoyl\Sa\Models\socket\Log;
use Exception;
use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\Log as FacadesLog;
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
    public static function bind($client_id,$token,$remember = 1)
    {
        $accessToken = PersonalAccessToken::findToken($token);
        //FacadesLog::channel('daily')->info('findToken',['token'=>$accessToken]);
        //这里先不检测token是否过期
        if(!$accessToken)
        {
            return;
        }
        
        $accessToken = $accessToken->toArray();
        if($accessToken['name'] != 'admin')
        {
            return;
        }

        //是否过期,由于前端只要有token都会bind一次，所以这里需要检测一下是否过期的token
        //默认记住登录有效期为3天 否则为1天
        $expiration = $remember?config('sanctum.expiration_remember',60*12*6):config('sanctum.expiration',24 * 60);
        //FacadesLog::channel('daily')->info('expiration',['token'=>$accessToken,'exp'=>$expiration * 60,'time'=>time(),'exptime'=>strtotime($accessToken['created_at']) + $expiration * 60]);
        if(strtotime($accessToken['created_at']) + $expiration * 60 < time())
        {
            //过期了
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

    public static function logoutByToken($token_id)
    {
        return self::logout($token_id,'token');
    }

    public static function logoutByClient($client_id)
    {
        return self::logout($client_id);
    }

    public static function logout($id,$type = 'client')
    {
        $model = self::getModel();

        if(!$model)
        {
            return;
        }

        if($type == 'token')
        {
            $where = ['token_id'=>$id];
        }else
        {
            $where = ['client_id'=>$id];
        }

        $client_data = $model->where($where)->first();

        if(!$client_data)
        {
            return;
        }

        $model->where(['id'=>$client_data->id])->delete();
        //发送ws消息推送
        Gateway::sendToClient($client_data->client_id, json_encode(['type' => 'info', 'data' => ['message'=>'logout']]));
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

    public static function sendToClients($client_ids,$message)
    {
        foreach($client_ids as $client_id)
        {
            self::sendToClient($client_id,$message);
        }
        return;
    }

    public static function sendToClient($client_id,$message)
    {
        try{
            if(Gateway::isOnline($client_id))
            {
                Gateway::sendToClient($client_id, json_encode($message));
            }
        }catch(Exception $e)
        {
            return;
        }
        
        return;
    }

    /**
     * 查询绑定的client_id 发送socket消息
     *
     * @param integer $id token_id 或者 user_id
     * @param [type] $message 发送的消息信息
     * @param 'user' | 'token' $type
     * @return void
     */
    public static function send($id,$message,$type = 'user')
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

        $client_ids = $model->where($where)->pluck('client_id')->toArray();

        self::sendToClients($client_ids,$message);

        return;
    }
}
