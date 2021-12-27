<?php
namespace Echoyl\Sa\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use EasyWeChat\Factory;
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

    public static function getPayment($cert_path = '')
    {
        $model = new Sets();
        $ss = new SetsService($model);
        $sets = $ss->getSet('wxconfig');
        $pay_set = $ss->getSet('wxpayconfig');
        $config = [
            // 必要配置
            'app_id'             => $sets['appid'],
            'mch_id'             => $pay_set['wxapp_mchid'],
            'key'                => $pay_set['wxapp_apikey'],   // API 密钥
        
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            // 'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
            // 'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
        
            'notify_url'         => env('APP_URL').'/wx/wxnotifys',     // 你也可以在下单时单独设置来想覆盖它
        ];
        if($cert_path != '')
        {
            $config['cert_path'] = $cert_path.'/cert.pem';
            $config['key_path'] = $cert_path.'/key.pem';
        }
        
        $app = Factory::payment($config);
        return $app;
    }


    public static function createWxaQrcode($scene,$page = '')
    {
        $app = self::getwxapp();
        $file_path = storage_path('app/public/qrcode');
        $name = md5($scene).'.png';

        if(file_exists($file_path.'/'.$name))
        {
            return 'qrcode/'.$name;
        }

        //d($scene);
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
            return false;
        }

        if($result)
        {
            //将文件保存至本地

            if(!is_dir($file_path))
            {
                File::makeDirectory($file_path,0755,true);
            }
            

            File::put($file_path.'/'.$name,$result);
            return 'qrcode/'.$name;
        }
        

        return false;
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

    public static function wxuserlist($nextid = null)
    {
        $app = self::getApp();
        $model = new Wx();
        $list = $app->user->list($nextid);

        if($list['count'] > 0)
        {
            foreach($list['data']['openid'] as $openid)
            {
                $user = $app->user->get($openid);
                $has = $model->where(['openid'=>$user['openid']])->first();
                $data = [
                    'subscribe'=>$user['subscribe'],
                    'openid'=>$user['openid'],
                    'nickname'=>$user['nickname'],
                    'sex'=>$user['sex'],
                    'city'=>$user['city'],
                    'province'=>$user['province'],
                    'country'=>$user['country'],
                    'headimgurl'=>$user['headimgurl'],
                    'subscribe_time'=>$user['subscribe_time'],
                    'unionid'=>$user['unionid'],
                    'subscribe_scene'=>$user['subscribe_scene']
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

        if($list['next_openid'])
        {
            self::wxuserlist($list['next_openid']);
        }
        return;
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
    public static function isSubscribe($user)
    {
        $app = self::getApp();

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
            $has = Wx::where(['openid'=>$user['openid']])->first();
            if($has && !$has['subscribe'])
            {
                Wx::where(['openid'=>$user['openid']])->update(['subscribe'=>1,'subscribe_time'=>time()]);
            }
            return true;
        }
        return;
    }

    public static function subscribe($openid,$flag = true,$user = false)
    {
        $has_user = Wx::where(['openid'=>$openid])->first();
        if($has_user)
        {
            //关注事件
            if($flag)
            {
                Wx::where(['openid'=>$openid])->update(['subscribe'=>1,'subscribe_time'=>time()]);
            }else
            {
                //取消关注
                Wx::where(['openid'=>$openid])->update(['subscribe'=>0]);
            }
        }else{
            self::wxUser($user);
        }
        return;
        
    }

    public static function wxUser($original)
    {
        if($original)
        {
            $has = Wx::where(['openid'=>$original['openid']])->first();
            if(!$has)
            {
                $wx_user = [
                    'nickname'=>$original['nickname'],
                    'openid'=>$original['openid'],
                    'headimgurl'=>$original['headimgurl'],
                    'sex'=>$original['sex'],
                    'city'=>$original['city'],
                    'province'=>$original['province'],
                    'country'=>$original['country'],
                    'unionid'=>$original['unionid']??'',
                    'subscribe'=>$original['subscribe']??0,
                    'status'=>1,
                    'created_at'=>date("Y-m-d H:i:s")
                ];
                Wx::insert($wx_user);
            }
        }
        return;
    }

}
