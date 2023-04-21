<?php
namespace Echoyl\Sa\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use EasyWeChat\Factory;
use EasyWeChat\MiniApp\Application;
use EasyWeChat\OfficialAccount\Application as OfficialAccountApplication;
use Echoyl\Sa\Models\wechat\miniprogram\Account as MiniprogramAccount;
use Echoyl\Sa\Models\wechat\miniprogram\User as MiniprogramUser;
use Echoyl\Sa\Models\wechat\offiaccount\Account;
use Echoyl\Sa\Models\wechat\offiaccount\User;
use Echoyl\Sa\Models\wechat\Pay;
use Echoyl\Sa\Models\wechat\pay\Log as PayLog;
use Echoyl\Sa\Models\wechat\Sets;
use Echoyl\Sa\Models\wechat\Wx;
use Exception;

class WechatService
{
    public static function wxconfig($url,$config = [])
    {
        $app = self::getApp();
        if(env('APP_ENV') == 'local')
        {
            $wxconfig = json_encode([]);
        }else
        {
            $app->jssdk->setUrl($url?:env('APP_URL'));
            if(!empty($config))
            {
                $wxconfig = $app->jssdk->buildConfig($config,false,false,false,["wx-open-launch-weapp"]);
            }else
            {
                $wxconfig = $app->jssdk->buildConfig([
                    'getLocation'
                    ,'updateTimelineShareData'
                    ,'updateAppMessageShareData'
                    ,'onMenuShareTimeline'
                    ,'onMenuShareAppMessage'
                    ,"chooseImage"
                    ,"uploadImage"
                    ,"previewImage"
                    ,"getLocalImgData"
                    ,"downloadImage"
                    ,"chooseWXPay"
                ],false,false,false,["wx-open-launch-weapp"]);
            }
        }
        return $wxconfig;
    }

    public static function createQrcode($scene)
    {
        $app = self::getApp();
        $result = $app->qrcode->forever($scene);// 或者 $app->qrcode->forever("foo");
        if(isset($result['ticket']))
        {
            $url = $app->qrcode->url($result['ticket']);
            //将文件保存至本地
            $file_path = storage_path('app/public/qrcode/'.date("Ym"));
            if(!is_dir($file_path))
            {
                File::makeDirectory($file_path,0755,true);
            }
            $name = $scene.'.jpg';
            File::put($file_path.'/'.$name,file_get_contents($url));
            return 'qrcode/'.date("Ym").'/'.$name;
        }
        

        return false;
    }

    public static function getwxapp()
    {
        $model = new Sets();
        $ss = new SetsService($model);
        $sets = $ss->getSet('wxappconfig');
        $config = [
            'app_id' => $sets['appid'],
            'secret' => $sets['secret'],

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            'log' => [
                'level' => 'debug',
                'file' => storage_path('logs/wechat.log'),
            ],
        ];

        $app = Factory::miniProgram($config);
        return $app;
    }
    //$scopes = ['snsapi_base']
    public static function getApp($scopes = ['snsapi_userinfo'])
    {
        $model = new Sets();
        $ss = new SetsService($model);
        $sets = $ss->getSet('wxconfig');
        //d($sets);
        $config = [
            // 必要配置
            'app_id' => $sets['appid'],
            'secret' => $sets['secret'],
            'token' => $sets['token'],
            'aes_key' => $sets['encodingaeskey'],
            'oauth' => [
                'scopes'   => $scopes,
                'callback' => '/'.env('APP_PREFIX','').'wx/auth',
            ],
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        return $app;
    }

    public static function getPayment($id,$appid = '')
    {
        $pay = (new Pay())->where(['id'=>$id])->first();
        if(!$pay)
        {
            return [1,'请先配置支付信息'];
        }

        $config = [
            // 必要配置
            'app_id'             => $appid ? : $pay['appid'],
            'mch_id'             => $pay['mch_id'],
            'key'                => $pay['apikey'],   // API 密钥
        
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            // 'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
            // 'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
        
            'notify_url'         => env('APP_URL').'/wx/wxnotifys',     // 你也可以在下单时单独设置来想覆盖它
        ];

        $cert_path = base_path('cert/cert_'.$pay['id'].'.pem');
        $key_path = base_path('cert/key_'.$pay['id'].'.pem');

        if(file_exists($cert_path))
        {
            $config['cert_path'] = $cert_path;
            $config['key_path'] = $key_path;
        }
        
        $app = Factory::payment($config);
        return [0,$app];
    }

    public static function createPayLog($money,$sn,$openid,$type = 'miniprogram')
    {
        $pay_log = [
            'money'=>$money,
            'sn'=>$sn,
            'openid'=>$openid,
            'created_at'=>now()
        ];
        if($type == 'miniprogram')
        {
            $pay_log['miniprogram_user_openid'] = $openid;
        }else
        {
            $pay_log['offiaccount_user_openid'] = $openid;
        }
        $id = (new PayLog())->insertGetId($pay_log);
        $pay_log['id'] = $id;
        return $pay_log;
    }

    public static function wxJsapi($pay_log,$pay_id,$title = '支付',$app_id = '')
    {
        //$msg_body = '{"tran_cd":"1192","acc_mod":"01","prod_cd":"1151","biz_cd":"0000007","mcht_cd":"996180418866693","tran_dt_tm":"20200904143149","order_id":"PGC20200904143149E43505","sys_order_id":"202009040196060","tran_order_id":"10001866202009040196060","resp_cd":"00","tran_amt":"1","sett_dt":"20200609","qr_code_info":{"wx_jsapi":"{\"appId\":\"wx2421b1c4370ec43b\",\"timeStamp\":\"1599201115\",\"package\":\"prepay_id\\u003dwx04143155547501cb1eacfffa8582310000\",\"signType\":\"RSA\",\"nonceStr\":\"3d6d9afe47394464ba262cbf2e600072\",\"paySign\":\"Yyhir9cY5tRLeFJ/oGsMi47UOMgSqTzsgHr+KApJU1paeWPIaUF1aSixKHMkZxjPRwOrBKzetDQ4rqR9ZE+xVMoouhpeWLhitYBQ7iG0HtJNcsbeJNatPfX2t7xxIYYklgq15FzqZ1yLt8kB8pLlQBRvViKOB9Vq+IgwWS6RtYxZCQl6+IxL2mdTnZ2uafAGnfVX8Dl34WEVZ7bG/9c0pKnQGzF8TSMVRafY5ZnmT3d5LX892+C4LjWPKAH8SjgAjpgRvepPLUfLAoV+ChkaNouzr+43PTyrJf23KGz9omnR6+L2yeXAF8GMe6TInCqP1t86FrKIr6kUZTjD3TNM9Q\\u003d\\u003d\"}"}}';
        //return ['code'=>0,'msg'=>'','data'=>json_decode($msg_body,true)];


        [$code,$app] = self::getPayment($pay_id,$app_id);

        if($code)
        {
            return [$code,$app];
        }

        $par = [
            'body' => $title,
            'out_trade_no' => $pay_log['sn'],
            'total_fee' => $pay_log['money'],
            //'spbill_create_ip' => '123.12.12.123', // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
            'notify_url' => env('APP_URL').'/wx/wxnotifys', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $pay_log['openid']
        ];

        Log::channel('daily')->info('pay_msg_body:',$par);

        $result = $app->order->unify($par);
        Log::channel('daily')->info('pay_msg_result:',$result);
        $jssdk = $app->jssdk;
        if($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK')
        {
            $config = $jssdk->sdkConfig($result['prepay_id']);

            Log::channel('daily')->info('sdkConfig:',$config);

            return [0,$config];
        }else
        {
            return [1,'支付调用失败:'.$result['return_msg']];
        }
    }


    public static function createWxaQrcode($scene,$page = '',$app)
    {
        if($page)
        {
            $result = $app->app_code->getUnlimit($scene,['page'=>$page]);
        }else
        {
            $result = $app->app_code->getUnlimit($scene);
        }
        //d($result);
        
        if(is_array($result) && isset($result['errcode']))
        {
            Log::channel('wechatOffiaccount')->info('createWxaQrcode:',$result);
            return [1,$result['errmsg']];
        }

        if($result)
        {
            return [0,$result];
        }
        

        return [1,'生成错误'];;
    }

    public function wxnotify()
    {
        echo 'success';exit;
    }

    public static function createMenu($content)
    {
        $app = self::getApp();
        
        try{
            $res = $app->menu->create($content);
        }catch(Exception $e)
        {
            return ['code'=>1,'msg'=>$e->getMessage()];
        }
        if($res['errcode'] == 0 && $res['errmsg'] == 'ok')
        {
            return ['code'=>0,'msg'=>'菜单已发布生效'];
        }else
        {
            return ['code'=>1,'msg'=>'菜单发布失败，原因:'.$res['errmsg']];
        }
    }

    public static function getOffiaccount($account_id = 0,$params = [])
    {
        if($account_id)
        {
            $account = (new Account())->where(['id'=>$account_id,'state'=>'enable'])->first();
        }else
        {
            $account = (new Account())->where(['state'=>'enable'])->orderBy('id','desc')->first();
        }
        if(!$account)
        {
            return [1,'请先配置或开启公众号'];
        }

        $account_id = $account['id'];
        
        $config = [
            // 必要配置
            'app_id' => $account['appid'],
            'secret' => $account['secret'],
            'token' => $account['token'],
            'aes_key' => $account['encodingaeskey'],
            'oauth' => [
                'scopes'   => $params['scopes']??['snsapi_userinfo'],
                'callback' => '/'.env('APP_PREFIX','').'wx/auth',
            ],
            'response_type' => 'array',
        ];
        $app = new OfficialAccountApplication($config);
        return [0,$app];
    }

    public static function getMiniprogram($account)
    {
        $config = [
            'app_id' => $account['appid'],
            'secret' => $account['secret'],

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            'log' => [
                'level' => 'debug',
                'file' => storage_path('logs/wechat.log'),
            ],
        ];

        $app = new Application($config);
        return $app;
    }

    public static function getMiniprogramAccountConfig($account_id = 0)
    {
        if($account_id)
        {
            $account = (new MiniprogramAccount())->where(['id'=>$account_id,'state'=>'enable'])->first();
        }else
        {
            $account = (new MiniprogramAccount())->where(['state'=>'enable'])->orderBy('id','desc')->first();
        }
        if(!$account)
        {
            return false;
        }

        return $account;

    }

    public static function wxuserlist($nextid = null,$account_id)
    {
        [$code,$app] = self::getOffiaccount($account_id);

        if($code)
        {
            return [$code,$app];
        }

        $model = new User();
        $list = $app->user->list($nextid);
        //Log::channel('daily')->info('list:',$list);

        if($list['count'] > 0)
        {
            $openids = array_chunk($list['data']['openid'],100);
            foreach($openids as $_openids)
            {
                $users = $app->user->select($_openids);
                //Log::channel('daily')->info('users:',$users); 
                if(!isset($users['user_info_list']))
                {
                    continue;
                }
                foreach($users['user_info_list'] as $user)
                {
                    $has = $model->where(['openid'=>$user['openid'],'appid'=>$app->config->app_id])->first();
                    $data = [
                        'subscribe'=>$user['subscribe'],
                        'openid'=>$user['openid'],
                        //微信接口不返回以下信息了
                        //'nickname'=>$user['nickname'],
                        //'gender'=>$user['sex'],
                        //'city'=>$user['city'],
                        //'province'=>$user['province'],
                        //'country'=>$user['country'],
                        //'avatar'=>$user['headimgurl'],
                        'subscribe_time'=>$user['subscribe_time'],
                        'unionid'=>$user['unionid']??'',
                        'subscribe_scene'=>$user['subscribe_scene'],
                        'appid'=>$app->config->app_id
                    ];
                    if($has){
                        $model->where(['id'=>$has['id']])->update($data);
                    }else
                    {
                        $data['created_at'] = now();
                        $model->insert($data);
                    }
                }
            }
        }

        if($list['next_openid'])
        {
            self::wxuserlist($list['next_openid'],$account_id);
        }
        return ['code'=>0,'msg'=>'同步完成'];
    }

    public static function getMenu()
    {
        $app = self::getApp();
        try{
            $res = $app->menu->current();
            return ['code'=>0,'data'=>$res];
        }catch(Exception $e)
        {
            return ['code'=>1,'msg'=>$e->getMessage()];
        }
        return;
    }

    /**
     * 检测是否关注公众号 关注的话 更新状态
     *
     * @param [type] $user
     * @return void
     */
    public static function isSubscribe($user,$app)
    {
        //$app = self::getApp();
        $model = new User();
        if(env('APP_ENV') == 'local')
        {
            return true;
        }

        if($user)
        {
            $user = $app->user->get($user['id']);
        }else
        {
            return;
        }

        if($user && $user['subscribe'] == 1)
        {
            $has = $model->where(['openid'=>$user['openid'],'appid'=>$app->config->app_id])->first();
            if($has && !$has['subscribe'])
            {
                $model->where(['openid'=>$user['openid']])->update(['subscribe'=>1,'subscribe_time'=>time()]);
            }
            return true;
        }
        return;
    }

    public static function subscribe($openid,$flag = true,$user = false,$app)
    {
        $model = new User();
        $has_user = $model->where(['openid'=>$openid,'appid'=>$app->config->app_id])->first();
        if($has_user)
        {
            //关注事件
            if($flag)
            {
                $model->where(['id'=>$has_user['id']])->update(['subscribe'=>1,'subscribe_time'=>time()]);
            }else
            {
                //取消关注
                $model->where(['id'=>$has_user['id']])->update(['subscribe'=>0]);
            }
        }else{
            self::offiaccountUser($user,$app->config->app_id);
        }
        return;
        
    }

    /**
     * 更新或新增小程序用户信息
     * 
     */
    public static function miniprogramUser($original,$app_id = 0)
    {

        $model = new MiniprogramUser();

        $has = $model->where(['openid'=>$original['openid'],'appid'=>$app_id])->first();
        $data = [
            //'nickname'=>$original['nickName'],
            'openid'=>$original['openid'],
            'nickname'=>$original['openid'],
            //'avatar'=>$original['avatarUrl'],
            //'gender'=>$original['gender'],
            //'city'=>$original['city'],
            //'province'=>$original['province'],
            //'country'=>$original['country'],
            'unionid'=>$original['unionid']??'',
            'last_used_at'=>now(),
            'appid'=>$app_id
        ];
        if($has)
        {
            //更新 - 更新的话只更新最后使用时间了
            $update = [
                'last_used_at'=>now(),
            ];
            $model->where(['id'=>$has['id']])->update($update);
            $id = $has['id'];
        }else
        {
            //新增
            $data['created_at'] = now();
            $id = $model->insertGetId($data);
        }
        $data['id'] = $id;
        return $data;
    }

    /**
     * 更新小程序用户  头像及昵称
     *
     * @param [type] $id
     * @param [type] $data
     * @return void
     */
    public static function miniprogramUserUpdate($id,$data)
    {
        $model = new MiniprogramUser();
        $model->where(['id'=>$id])->update($data);
        return;
    }

    public static function miniprogramUserMobile($openid,$mobile)
    {
        $model = new MiniprogramUser();
        $has = $model->where(['openid'=>$openid])->first();
        if($has)
        {
            $model->where(['id'=>$has['id']])->update(['mobile'=>$mobile]);
        }
        return;
    }

    public static function offiaccountUser($original,$app_id = '')
    {
        $model = new User();
        if($original)
        {
            $data = [
                'openid'=>$original['openid'],
                'gender'=>$original['sex'],
                'city'=>$original['city'],
                'province'=>$original['province'],
                'country'=>$original['country'],
                'unionid'=>$original['unionid']??'',
                'subscribe'=>$original['subscribe']??0,
                'subscribe_time'=>$original['subscribe_time']??0,
                'created_at'=>date("Y-m-d H:i:s"),
                'appid'=>$app_id
            ];
            if($original['nickname'])
            {
                $data['nickname'] = $original['nickname'];
            }
            if($original['headimgurl'])
            {
                $data['avatar'] = $original['headimgurl'];
            }
            $has = $model->where(['openid'=>$original['openid'],'appid'=>$app_id])->first();
            if(!$has)
            {
                $data['created_at'] = now();
                $id = $model->insertGetId($data);
            }else
            {
                //更新
                $model->where(['id'=>$has['id']])->update($data);
                $id = $has['id'];
            }
            $data['id'] = $id;
            return $data;
        }
        return false;
    }

    /**
     * 微信公众号发送订阅模板消息
     *
     * @param [type] $data
     * @param [type] $app
     * @return void
     */
    public static function sendMessage($data,$app)
    {
        $ret = $app->template_message->send($data);
        Log::channel('wechatOffiaccount')->info('template_message:',$ret);
        return;
    }
    /**
     * 发送多条模板消息
     *
     * @param [type] $data
     * @param [type] $app
     * @return void
     */
    public static function sendMessages($data,$app)
    {
        foreach($data as $val)
        {
            self::sendMessage($val,$app);
        }
        return;
    }

    public static function payRefund($pay_log_id,$tran_amt,$app)
    {
        $model = new PayLog();
        $pay_log = $model->where(['id'=>$pay_log_id])->first();
        if(!$pay_log)
        {
            return [1,'订单未发起支付，不能进行退款操作'];
        }
        if($pay_log['state'] != 1)
        {
            return [1,'订单未支付，不能进行退款操作'];
        }

        if($pay_log['refund_state'] == 1)
        {
            return [1,'订单已退款'];
        }

        [$code,$refund] = self::payRefundQuery($pay_log['sn'],$app);
        if(!$code)
        {
            $model->where(['id'=>$pay_log['id']])->update([
                'state'=>2,
                'refund_at'=>now(),
                'refund_out_sn'=>$refund['refund_id_0'],
                'refund_sn'=>$refund['out_refund_no_0'],
                'refund_money'=>$tran_amt
            ]);
            return [0,'退款成功'];
        }

        $rf = $pay_log['sn'].'TK'.rand(10,99);
        $result = $app->refund->byOutTradeNumber($pay_log['sn'],$rf,$pay_log['money'],$tran_amt);

        Log::channel('wechatOffiaccount')->info('tuikuan_result:',$result);
        if($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK' && $result['result_code'] == 'SUCCESS')
        {
            $model->where(['id'=>$pay_log['id']])->update([
                'state'=>2,
                'refund_at'=>now(),
                'refund_out_sn'=>$result['refund_id'],
                'refund_sn'=>$rf,
                'refund_money'=>$tran_amt
            ]);
            return [0,'退款成功'];
        }else
        {
            return [1,$result['err_code_des']??'未知原因'];
        }

    }

    public static function payRefundQuery($sn,$app)
    {
        $result = $app->refund->queryByOutTradeNumber($sn);
        Log::channel('wechatOffiaccount')->info('tuikuan_query_result:',$result);
        if($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK' && $result['result_code'] == 'SUCCESS')
        {
            return [0,$result];
        }else
        {
            return [1,'fail'];;
        }
    }

}
