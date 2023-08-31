<?php
namespace Echoyl\Sa\Models;

class Smslog extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'sms_log';
    public $timestamps = false;

    public static function checkCode($mobile,$code)
    {
        if($code == 6666 && env('APP_ENV') == 'local')
        {
            return true;
        }
        $data = self::where('mobile',$mobile)->orderBy('id','desc')->first();
        if(!$data || $data['status'] == 1 || $data['code'] != $code || strtotime($data['created_at']) + 1800 < time())
        {
            return false;
        }else
        {
            self::where('id',$data['id'])->update(['status'=>1]);
            return true;
        }
        
    }
}
