<?php
namespace Echoyl\Sa\Constracts;

/**
 * Undocumented interface
 * @function
 */
interface SaServiceInterface
{
    public function wechatMiniprogramAccount($type);
    public function wechatMiniprogramApp($type = 'user');
    public function updateUserMobile($user_id,$mobile);
    public function checkUser($user,$type = '');
    public function user($type = '');
    public function getUnpaidOrder($order_sn);
    public function payOrder($id,$log_id);
}