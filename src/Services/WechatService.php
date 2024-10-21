<?php

namespace Echoyl\Sa\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use EasyWeChat\Factory;
use EasyWeChat\MiniApp\Application;
use EasyWeChat\OfficialAccount\Application as OfficialAccountApplication;
use EasyWeChat\Pay\Application as PayApplication;
use Echoyl\Sa\Models\wechat\miniprogram\Account as MiniprogramAccount;
use Echoyl\Sa\Models\wechat\miniprogram\User as MiniprogramUser;
use Echoyl\Sa\Models\wechat\miniprogram\user\Bind;
use Echoyl\Sa\Models\wechat\offiaccount\Account;
use Echoyl\Sa\Models\wechat\offiaccount\User;
use Echoyl\Sa\Models\wechat\Pay;
use Echoyl\Sa\Models\wechat\pay\Log as PayLog;
use Exception;

class WechatService
{
    public static function wxconfig($url, $config = [])
    {
        $app = self::getApp();
        if (env('APP_ENV') == 'local') {
            $wxconfig = json_encode([]);
        } else {
            $app->jssdk->setUrl($url ?: env('APP_URL'));
            if (!empty($config)) {
                $wxconfig = $app->jssdk->buildConfig($config, false, false, false, ["wx-open-launch-weapp"]);
            } else {
                $wxconfig = $app->jssdk->buildConfig([
                    'getLocation', 'updateTimelineShareData', 'updateAppMessageShareData', 'onMenuShareTimeline', 'onMenuShareAppMessage', "chooseImage", "uploadImage", "previewImage", "getLocalImgData", "downloadImage", "chooseWXPay"
                ], false, false, false, ["wx-open-launch-weapp"]);
            }
        }
        return $wxconfig;
    }

    /**
     * Undocumented function
     *
     * @param [type] $id
     * @param string $appid
     * @return void
     */
    public static function getPayment($id, $appid = '')
    {
        $pay = (new Pay())->where(['id' => $id])->first();
        if (!$pay) {
            return [1, '请先配置支付信息'];
        }

        $config = [
            // 必要配置
            'app_id'             => $appid ?: $pay['appid'],
            'mch_id'             => $pay['mch_id'],
            'secret_key'                => $pay['apikey'],   // API 密钥

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            // 'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
            // 'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！

            'notify_url'         => env('APP_URL') . '/wx/wxnotifys',     // 你也可以在下单时单独设置来想覆盖它
        ];

        $cert_path = base_path('cert/cert_' . $pay['id'] . '.pem');
        $key_path = base_path('cert/key_' . $pay['id'] . '.pem');

        if (file_exists($cert_path)) {
            $config['private_key'] = $key_path;
            $config['certificate'] = $cert_path;
        }

        $app = new PayApplication($config);
        return [0, $app];
    }

    public static function createPayLog($money, $sn, $openid, $type = 'miniprogram')
    {
        $pay_log = [
            'money' => $money,
            'sn' => $sn,
            'openid' => $openid,
            'created_at' => now()
        ];
        if ($type == 'miniprogram') {
            $pay_log['miniprogram_user_openid'] = $openid;
        } else {
            $pay_log['offiaccount_user_openid'] = $openid;
        }
        $id = (new PayLog())->insertGetId($pay_log);
        $pay_log['id'] = $id;
        return $pay_log;
    }

    public static function wxJsapi($pay_log, $pay_id, $title = '支付', $app_id = '')
    {
        //$msg_body = '{"tran_cd":"1192","acc_mod":"01","prod_cd":"1151","biz_cd":"0000007","mcht_cd":"996180418866693","tran_dt_tm":"20200904143149","order_id":"PGC20200904143149E43505","sys_order_id":"202009040196060","tran_order_id":"10001866202009040196060","resp_cd":"00","tran_amt":"1","sett_dt":"20200609","qr_code_info":{"wx_jsapi":"{\"appId\":\"wx2421b1c4370ec43b\",\"timeStamp\":\"1599201115\",\"package\":\"prepay_id\\u003dwx04143155547501cb1eacfffa8582310000\",\"signType\":\"RSA\",\"nonceStr\":\"3d6d9afe47394464ba262cbf2e600072\",\"paySign\":\"Yyhir9cY5tRLeFJ/oGsMi47UOMgSqTzsgHr+KApJU1paeWPIaUF1aSixKHMkZxjPRwOrBKzetDQ4rqR9ZE+xVMoouhpeWLhitYBQ7iG0HtJNcsbeJNatPfX2t7xxIYYklgq15FzqZ1yLt8kB8pLlQBRvViKOB9Vq+IgwWS6RtYxZCQl6+IxL2mdTnZ2uafAGnfVX8Dl34WEVZ7bG/9c0pKnQGzF8TSMVRafY5ZnmT3d5LX892+C4LjWPKAH8SjgAjpgRvepPLUfLAoV+ChkaNouzr+43PTyrJf23KGz9omnR6+L2yeXAF8GMe6TInCqP1t86FrKIr6kUZTjD3TNM9Q\\u003d\\u003d\"}"}}';
        //return ['code'=>0,'msg'=>'','data'=>json_decode($msg_body,true)];


        [$code, $app] = self::getPayment($pay_id, $app_id);

        if ($code) {
            return [$code, $app];
        }

        // $par = [
        //     'body' => $title,
        //     'out_trade_no' => $pay_log['sn'],
        //     'total_fee' => $pay_log['money'],
        //     //'spbill_create_ip' => '123.12.12.123', // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
        //     'notify_url' => env('APP_URL').'/wx/wxnotifys', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
        //     'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
        //     'openid' => $pay_log['openid']
        // ];
        $app_id = $app->getConfig()->get('app_id');
        $mch_id = $app->getConfig()->get('mch_id');
        $notify_url = $app->getConfig()->get('notify_url');
        $par = [
            "mchid" => $mch_id, // <---- 请修改为您的商户号
            "out_trade_no" => $pay_log['sn'],
            "appid" => $app_id, // <---- 请修改为服务号的 appid
            "description" => $title,
            "notify_url" => $notify_url,
            "amount" => [
                "total" => intval($pay_log['money']),
                "currency" => "CNY"
            ],
            "payer" => [
                "openid" => $pay_log['openid'] // <---- 请修改为服务号下单用户的 openid
            ]
        ];

        Log::channel('daily')->info('pay_msg_body:', $par);

        //发起订单
        try{
            $result = $app->getClient()->postJson("v3/pay/transactions/jsapi",$par)->toArray();
        }catch(Exception $e)
        {
            return [1, '请求失败:' . $e->getMessage()];
        }
        
        
        //$result = $app->order->unify($par);
        Log::channel('daily')->info('pay_msg_result:', ['result'=>$result]);
        $jssdk = $app->getUtils();

        if (isset($result['prepay_id']) && $result['prepay_id']) {
            //buildSdkConfig - 网页js获取
            $config = $jssdk->buildMiniAppConfig($result['prepay_id'], $app_id);

            Log::channel('daily')->info('sdkConfig:', $config);

            return [0, $config];
        } else {
            return [1, '支付调用失败'];
        }
    }


    /**
     * 生成小程序二维码
     *
     * @param [type] $scene
     * @param \EasyWeChat\MiniApp\Application $app
     * @param string $page
     * @param string $path
     * @return void
     */
    public static function createWxaQrcode($scene, $app, $page = '',$path = '')
    {
        if(is_array($scene))
        {
            $scene = http_build_query($scene);
        }
        $post = ['scene'=>$scene];

        if ($page) 
        {
            $post['page'] = $page;
        }

        try {
            $response = $app->getClient()->postJson('/wxa/getwxacodeunlimit', $post);
            if($path)
            {
                $response->saveAs($path);
                return [0,'success'];
            }else
            {
                return [0,$response];
            }
            
        }catch (Exception $e) {

            return [10,$e->getMessage()];
        }
    }

    public function wxnotify()
    {
        echo 'success';
        exit;
    }

    public static function createMenu($content, $wechat_offiaccount_id)
    {
        try {
            $app = self::getOffiaccountApp($wechat_offiaccount_id);
        } catch (Exception $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }

        $api = $app->getClient();
        try {
            //Log::channel('daily')->info('menus:',['data'=>['button'=>$content]]);
            $res = $api->postJson('cgi-bin/menu/create', ['button' => $content]);
        } catch (Exception $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
        if ($res['errcode'] == 0 && $res['errmsg'] == 'ok') {
            return ['code' => 0, 'msg' => '菜单已发布生效'];
        } else {
            return ['code' => 1, 'msg' => '菜单发布失败，原因:' . $res['errmsg']];
        }
    }

    public static function getOffiaccount($account_id)
    {
        if ($account_id) {
            $account = (new Account())->where(['id' => $account_id, 'state' => 1])->first();
        } else {
            $account = (new Account())->where(['state' => 1])->orderBy('id', 'desc')->first();
        }
        if (!$account) {
            return [1,'请先配置或开启公众号'];
        }
        return [0,$account];
    }

    /**
     * 通过账号id获取app
     *
     * @param integer $account_id
     * @param array $params
     * @return \EasyWeChat\OfficialAccount\Application
     */
    public static function getOffiaccountApp($account_id = 0, $params = []): OfficialAccountApplication
    {
        [$code,$account] = self::getOffiaccount($account_id);

        if($code)
        {
            throw new Exception($account);
        }
        
        
        $account_id = $account['id'];

        $config = [
            // 必要配置
            'app_id' => $account['appid'],
            'secret' => $account['secret'],
            'token' => $account['token'],
            //'aes_key' => $account['encodingaeskey'],
            'oauth' => [
                'scopes'   => $params['scopes'] ?? ['snsapi_userinfo'],
                'callback' => '/' . env('APP_PREFIX', '') . 'wx/auth',
                'redirect_url' => url('wx/auth')
            ],
            //'response_type' => 'array',
        ];
        $app = new OfficialAccountApplication($config);
        return $app;
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
        if ($account_id) {
            $account = (new MiniprogramAccount())->where(['id' => $account_id, 'state' => 1])->first();
        } else {
            $account = (new MiniprogramAccount())->where(['state' => 1])->orderBy('id', 'desc')->first();
        }
        if (!$account) {
            return false;
        }

        return $account;
    }

    public static function getMenu($wechat_offiaccount_id)
    {
        try {
            $app = self::getOffiaccountApp($wechat_offiaccount_id);
        } catch (Exception $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }

        $api = $app->getClient();
        try {
            //Log::channel('daily')->info('menus:',['data'=>['button'=>$content]]);
            $res = $api->get('cgi-bin/menu/get');
        } catch (Exception $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
        $menu = json_decode($res->getContent(), true);
        return ['code' => 0, 'data' => isset($menu['menu'])?$menu['menu']['button']:[]];
    }

    /**
     * 检测是否关注公众号 关注的话 更新状态
     *
     * @param [type] $user
     * @return void
     */
    public static function isSubscribe($user, $app)
    {
        //$app = self::getApp();
        $model = new User();
        if (env('APP_ENV') == 'local') {
            return true;
        }

        if ($user) {
            $user = $app->user->get($user['id']);
        } else {
            return;
        }

        if ($user && $user['subscribe'] == 1) {
            $has = $model->where(['openid' => $user['openid'], 'appid' => $app->config->app_id])->first();
            if ($has && !$has['subscribe']) {
                $model->where(['openid' => $user['openid']])->update(['subscribe' => 1, 'subscribe_time' => now()]);
            }
            return true;
        }
        return;
    }

    /**
     * Undocumented function
     *
     * @param [type] $openid
     * @param boolean $flag
     * @param boolean $user
     * @param \EasyWeChat\OfficialAccount\Application $app
     * @return void
     */
    public static function subscribe($openid, $flag = true, $user = false, $app)
    {
        $model = new User();
        $config = $app->getConfig();
        $app_id = $config->get('app_id');
        $has_user = $model->where(['openid' => $openid, 'appid' => $app_id])->first();
        if ($has_user) {
            //关注事件
            if ($flag) {
                $model->where(['id' => $has_user['id']])->update(['subscribe' => 1, 'subscribe_at' => now()]);
            } else {
                //取消关注
                $model->where(['id' => $has_user['id']])->update(['subscribe' => 0]);
            }
        } else {
            self::offiaccountUser($user, $app_id);
        }
        return;
    }

    public static function miniprogramUserBind($openid, $user_id)
    {
        $model = (new Bind());
        $has = $model->where(['openid' => $openid])->first();
        if ($has) {
            $model->where(['id' => $has['id']])->update(['user_id' => $user_id]);
        } else {
            $data = ['openid' => $openid, 'user_id' => $user_id];
            $model->insert($data);
        }
        return;
    }

    public static function miniprogramUserUnBind($user_id)
    {
        $model = (new Bind());
        $model->where(['user_id' => $user_id])->delete();
        return;
    }

    /**
     * 更新或新增小程序用户信息
     * 
     */
    public static function miniprogramUser($original, $app_id = 0)
    {

        $model = new MiniprogramUser();

        $has = $model->with(['bind'])->where(['openid' => $original['openid'], 'appid' => $app_id])->first();
        $data = [
            //'nickname'=>$original['nickName'],
            'openid' => $original['openid'],
            //'nickname'=>$original['openid'],
            //'avatar'=>$original['avatarUrl'],
            //'gender'=>$original['gender'],
            //'city'=>$original['city'],
            //'province'=>$original['province'],
            //'country'=>$original['country'],
            'unionid' => $original['unionid'] ?? '',
            'last_used_at' => now(),
            'appid' => $app_id,
            'state'=>1
        ];
        if ($has) {
            //更新 - 更新的话只更新最后使用时间了
            // $update = [
            //     'last_used_at'=>now(),
            // ];
            unset($data['nickname']);
            $model->where(['id' => $has['id']])->update($data);
            $id = $has['id'];
            return $has;
        } else {
            //新增
            $data['created_at'] = now();
            $id = $model->insertGetId($data);
            $data['bind'] = false;
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
    public static function miniprogramUserUpdate($id, $data)
    {
        $model = new MiniprogramUser();
        $model->where(['id' => $id])->update($data);
        return;
    }

    public static function miniprogramUserMobile($openid, $mobile)
    {
        $model = new MiniprogramUser();
        $has = $model->where(['openid' => $openid])->first();
        if ($has) {
            $model->where(['id' => $has['id']])->update(['mobile' => $mobile]);
        }
        return;
    }

    /**
     * Undocumented function
     *
     * @param [type] $openid
     * @param \EasyWeChat\OfficialAccount\Application $app
     * @return void
     */
    public static function getOffiaccountUser($openid, $app)
    {
        $api = $app->getClient();

        $response = $api->get('/cgi-bin/user/info', [
            'openid' => $openid
        ]);

        return $response->toArray();
    }

    public static function offiaccountUser($original, $app_id = '')
    {
        $model = new User();
        if ($original) {
            $data = [
                'openid' => $original['openid'],
                'gender' => $original['sex'],
                'city' => $original['city'],
                'province' => $original['province'],
                'country' => $original['country'],
                'unionid' => $original['unionid'] ?? '',
                //'subscribe' => $original['subscribe'] ?? 0,
                'created_at' => date("Y-m-d H:i:s"),
                'appid' => $app_id,
                'state'=>1
            ];
            if(isset($original['subscribe']))
            {
                $data['subscribe'] = $original['subscribe']?1:0;
            }
            if (isset($original['subscribe_time'])) {
                $data['subscribe_at'] = date("Y-m-d H:i:s", $original['subscribe_time']);
            }
            $data['nickname'] = $original['nickname'] ?: $original['openid'];
            if ($original['headimgurl']) {
                $data['avatar'] = $original['headimgurl'];
            }
            $has = $model->where(['openid' => $original['openid'], 'appid' => $app_id])->first();
            if (!$has) {
                $data['created_at'] = now();
                $id = $model->insertGetId($data);
            } else {
                //更新
                $model->where(['id' => $has['id']])->update($data);
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
    public static function sendMessage($data, $app)
    {
        //$ret = $app->template_message->send($data);

        $api = $app->getClient();

        foreach ($data['data'] as $key => $val) {
            $data['data'][$key] = ['value' => $val];
        }

        $response = $api->postJson('/cgi-bin/message/template/send', $data);

        $ret = $response->toArray();

        Log::channel('daily')->info('wechat template_message:', $ret);
        return;
    }
    /**
     * 发送多条模板消息
     *
     * @param [type] $data
     * @param [type] $app
     * @return void
     */
    public static function sendMessages($data, $app)
    {
        foreach ($data as $val) {
            self::sendMessage($val, $app);
        }
        return;
    }

    public static function payRefund($pay_log_id, $tran_amt, $app)
    {
        $model = new PayLog();
        $pay_log = $model->where(['id' => $pay_log_id])->first();
        if (!$pay_log) {
            return [1, '订单未发起支付，不能进行退款操作'];
        }
        if ($pay_log['state'] != 1) {
            return [1, '订单未支付，不能进行退款操作'];
        }

        if ($pay_log['refund_state'] == 1) {
            return [1, '订单已退款'];
        }

        [$code, $refund] = self::payRefundQuery($pay_log['sn'], $app);
        if (!$code) {
            $model->where(['id' => $pay_log['id']])->update([
                'state' => 2,
                'refund_at' => now(),
                'refund_out_sn' => $refund['refund_id_0'],
                'refund_sn' => $refund['out_refund_no_0'],
                'refund_money' => $tran_amt
            ]);
            return [0, '退款成功'];
        }

        $rf = $pay_log['sn'] . 'TK' . rand(10, 99);
        $result = $app->refund->byOutTradeNumber($pay_log['sn'], $rf, $pay_log['money'], $tran_amt);

        Log::channel('daily')->info('wechat tuikuan_result:', $result);
        if ($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK' && $result['result_code'] == 'SUCCESS') {
            $model->where(['id' => $pay_log['id']])->update([
                'state' => 2,
                'refund_at' => now(),
                'refund_out_sn' => $result['refund_id'],
                'refund_sn' => $rf,
                'refund_money' => $tran_amt
            ]);
            return [0, '退款成功'];
        } else {
            return [1, $result['err_code_des'] ?? '未知原因'];
        }
    }

    public static function payRefundQuery($sn, $app)
    {
        $result = $app->refund->queryByOutTradeNumber($sn);
        Log::channel('daily')->info('wechat tuikuan_query_result:', $result);
        if ($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK' && $result['result_code'] == 'SUCCESS') {
            return [0, $result];
        } else {
            return [1, 'fail'];;
        }
    }
}
