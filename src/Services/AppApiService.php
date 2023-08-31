<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Services\WechatService;
use Laravel\Sanctum\PersonalAccessToken;
use Echoyl\Sa\Constracts\SaServiceInterface;
class AppApiService implements SaServiceInterface
{
    var $userModel;
    var $app_name = '';
    var $check_insert_user = true;

    public function baseSet($name = '')
    {
        $ss = new SetsService();
        return $ss->getBase($name);
    }

    /**
     * 获取小程序账号信息
     */
    public function wechatMiniprogramAccount($type = 'user')
    {
        $ss = new SetsService();
        $key = $type == 'user'?'user_miniprogram_account_id':'merch_miniprogram_account_id';
        //$id = $ss->get('base.'.$key);
        $id = $this->baseSet($key);

        if(is_array($id))
        {
            $id = $id['id'];
        }

        return WechatService::getMiniprogramAccountConfig($id);
    }

    public function wechatMiniprogramApp($type = 'user')
    {
        $account = $this->wechatMiniprogramAccount($type);
        if(!$account)
        {
            return [1,'请先配置小程序'];
        }

        return [0,WechatService::getMiniprogram($account)];
    }

    public function wechatOffiaccountApp()
    {
        $ss = new SetsService();
        $id = $this->baseSet('offiaccount_account_id');

        if(is_array($id))
        {
            $id = $id['id'];
        }

        return WechatService::getOffiaccount($id);
    }

    public function updateUserMobile($user,$mobile)
    {
        $model = $this->userModel;
        $user_id = $user['id'];
        $has = $model->where(['id'=>$user_id])->first();

        if($has && $has['mobile'])
        {
            //已有手机号码暂时不支持修改手机号码
            return;
        }

        $mobile_user = $model->where(['mobile'=>$mobile])->first();

        if($mobile_user)
        {
            if($mobile_user['id'] != $user['id'])
            {
                //删除当前用户 将之前的用户绑定到当前用户 需要退出登录
                $openid = $user['wechatMiniprogramUser']['openid'];
                $model->where(['id'=>$mobile_user['id']])->update(['wechat_miniprogram_openid'=>$openid]);
                PersonalAccessToken::where(['name'=>'wechat_miniprogram','tokenable_id'=>$user['id']])->update(['tokenable_id'=>$mobile_user['id']]);
                $model->where(['id'=>$user['id']])->delete();
            }
        }else
        {
            if($has)
            {
                $user = [
                    'mobile'=>$mobile
                ];
                $model->where(['id'=>$user_id])->update($user);
            }
        }

        
        return;
    }


    public static function apiUser($type = 'wechat_miniprogram')
    {
        $user = request()->user();
        //return $user;
        if($user && $user->currentAccessToken()->name == $type)
        {
            return $user;
        }else
        {
            return false;
        }
    }

    /**
     * Undocumented function
     *
     * @param string $type 获取用户信息类型
     * @param string $from 第三方登录的类型 默认微信小程序
     * @return void
     */
    public function user($type = 'customer',$from = 'wechat_miniprogram')
    {
        $user = self::apiUser($from);
        if(!$user)
        {
            return false;
        }
        $ret = false;
        if($type == 'customer')
        {
            $model = $this->userModel;

            $user_data = $model->where(['wechat_miniprogram_openid'=>$user['openid']])->first();
    
            if($user_data)
            {
                $ret = $user_data->toArray();
            }
        }else
        {
            // $login = (new Login())->where(['openid'=>$user['openid']])->with(['shop.provinceName','shop.areaName','shop.cityName'])->first();

            // if($login)
            // {
            //     $ret = $login['shop'];
            // }
        }

        if($ret)
        {
            $ret['wechatMiniprogramUser'] = $user;
        }
        return $ret;
    }

    /**
     * 检测用户表是否已经插入小程序用户
     */

    public function checkUser($miniprogramUser,$type = 'customer',$share_code = '')
    {
        if(!$this->check_insert_user)
        {
            //如果不需要插入用户
            return;
        }
        $model = $this->userModel;

        $has = $model->where(['wechat_miniprogram_openid'=>$miniprogramUser['openid']])->first();

        if(!$has)
        {
            $user = [
                //'name'=>$miniprogramUser['nickname'],
                //'avatar'=>$miniprogramUser['avatar'],
                'created_at'=>now(),
                'wechat_miniprogram_openid'=>$miniprogramUser['openid'],
                'last_used_at'=>now(),
            ];
            //读取share信息
            $code = urldecode($share_code);
            $code = explode('&',$code);
            $keys = [];
            foreach($code as $item)
            {
                $item = explode('=',$item);
                if(count($item) == 2)
                {
                    $keys[$item[0]] = $item[1];
                }
            }
            if(isset($keys['invitation_code']))
            {
                // $shop = (new Shop())->where(['id'=>$keys['invitation_code']])->first();
                // if($shop)
                // {
                //     $user['share_from_shop_id'] = $shop['id'];
                // }
            }
            

            $model->insert($user);
        }
        return;

    }


    public function getUnpaidOrder($sn)
    {
        //return (new Order())->where(['sn'=>$sn,'status'=>4])->first();
    }

    /**
     * 支付订单 修改订单状态
     *
     * @param [type] $order_id
     * @param [type] $wechat_pay_log_id
     * @return void
     */
    public function payOrder($order_id,$wechat_pay_log_id)
    {
        // $model = new Order();
        // $order = $model->where(['id'=>$order_id])->first();
        // if(!$order || $order['status'] != 4)
        // {
        //     return false;
        // }

        // $model->where(['id'=>$order['id']])->update(['status'=>0,'wechat_pay_log_id'=>$wechat_pay_log_id]);
        // //发送模板消息
        // event(new PayOrderEvent($order_id));
        // return true;
    }

}