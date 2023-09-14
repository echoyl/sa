<?php
namespace Echoyl\Sa\Services;
use Illuminate\Support\Str;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliyunService
{
    public function getVideoUrl($vid)
    {
            $accessKeyId = env('ALI_accessKeyId');
            $accessKeySecret = env('ALI_accessKeySecret');
            AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
                            ->regionId('cn-hangzhou')
                            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                                ->product('vod')
                                // ->scheme('https') // https | http
                                ->version('2017-03-21')
                                ->action('GetPlayInfo')
                                ->method('POST')
                                ->host('vod.cn-shanghai.aliyuncs.com')
                                ->options([
                                                'query' => [
                                                'RegionId' => "cn-hangzhou",
                                                'VideoId' => $vid,
                                                ],
                                            ])
                                ->request();
            $url = $result->toArray();
            return [
                'play_url'=>$url['PlayInfoList']['PlayInfo'][0]['PlayURL'],
                'cover_url'=>$url['VideoBase']['CoverURL']??'',
                'title'=>$url['VideoBase']['Title']??'',
                'duration'=>$url['VideoBase']['Duration']??''
            ];
        } catch (ClientException $e) {
            //return $e->getErrorMessage().":".$e->getErrorCode();
        } catch (ServerException $e) {
            //return $e->getErrorMessage().":".$e->getErrorCode();
        }
        return '';
    }

    public function createUploadVideo($title,$filename,$qt_tpl = '7a1216f13a466a89dfc9546f1a4da4e6')
    {
        $accessKeyId = env('ALI_accessKeyId');
        $accessKeySecret = env('ALI_accessKeySecret');
        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
                        ->regionId('cn-hangzhou')
                        ->asDefaultClient();
        $ext = explode('.',$filename);
        $is_qt = 0;
        if(count($ext) > 0)
        {
            $ext = $ext[count($ext)-1];
            if($ext == 'MOV')
            {
                $is_qt = 1;
            }
    
        }
        
        try {
            if($is_qt)
            {
                $result = AlibabaCloud::rpc()
                    ->product('vod')
                    // ->scheme('https') // https | http
                    ->version('2017-03-21')
                    ->action('CreateUploadVideo')
                    ->method('POST')
                    ->host('vod.cn-shanghai.aliyuncs.com')
                    ->options([
                        'query' => [
                            'RegionId' => "cn-hangzhou",
                            'Title' => $title,
                            'FileName' => $filename,
                            'TemplateGroupId'=>$qt_tpl
                        ],
                    ])
                    ->request();
            }else
            {
                $result = AlibabaCloud::rpc()
                    ->product('vod')
                    // ->scheme('https') // https | http
                    ->version('2017-03-21')
                    ->action('CreateUploadVideo')
                    ->method('POST')
                    ->host('vod.cn-shanghai.aliyuncs.com')
                    ->options([
                        'query' => [
                            'RegionId' => "cn-hangzhou",
                            'Title' => $title,
                            'FileName' => $filename,
                        ],
                    ])
                    ->request();
            }
            
            $url = $result->toArray();
            return ['code'=>0,'data'=>$url];
        } catch (ClientException $e) {
            return ['code'=>1,'msg'=>$e->getErrorMessage()."-:-".$e->getErrorCode()];
        } catch (ServerException $e) {
            return ['code'=>1,'msg'=>$e->getErrorMessage().":".$e->getErrorCode()];
        }
        return '';
    }

    public function refreshUploadVideo($id)
    {
        $accessKeyId = env('ALI_accessKeyId');
        $accessKeySecret = env('ALI_accessKeySecret');
        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
                        ->regionId('cn-hangzhou')
                        ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                                ->product('vod')
                                // ->scheme('https') // https | http
                                ->version('2017-03-21')
                                ->action('RefreshUploadVideo')
                                ->method('POST')
                                ->host('vod.cn-shanghai.aliyuncs.com')
                                ->options([
                                                'query' => [
                                                'RegionId' => "cn-hangzhou",
                                                'VideoId' => $id,
                                                ],
                                            ])
                                ->request();
            $url = $result->toArray();
            return ['code'=>0,'data'=>$url];
        } catch (ClientException $e) {
            return ['code'=>1,'msg'=>$e->getErrorMessage()."-:-".$e->getErrorCode()];
        } catch (ServerException $e) {
            return ['code'=>1,'msg'=>$e->getErrorMessage().":".$e->getErrorCode()];
        }
    }

    public static function sendSMS($mobile,$data,$params)
    {
        //注册 SMS_8740008 登录 SMS_8740010 // 审核通过 SMS_183791211 审核失败 SMS_183796303
        if(env('APP_ENV') == 'local')
        {
            //return ['code'=>0,'msg'=>'发送成功'];
            //return ['code'=>0,'msg'=>'发送成功','codex'=>isset($data['code'])?$data['code']:''];
        }

        $tp = $params['id'];
        $SignName = $params['name'];

        // $tplArr = [
        //     'code'=>'SMS_109490162',
        //     'register'=>'SMS_109490162',
        //     'login'=>'SMS_137805222',
        //     'forget'=>'SMS_109490162',
        //     'youhui'=>'SMS_137673826',
        //     'bianjia'=>'SMS_137673826',
        //     'maiche'=>'SMS_215795268',
        //     'xunjia'=>'SMS_219737298',
        //     'chujia'=>'SMS_219752135',
        //     'xiadan'=>'SMS_166096705'
        // ];

        //$tp = isset($tplArr[$tp])?$tplArr[$tp]:$tp;

        $accessKeyId = env('ALI_accessKeyId');
        $accessKeySecret = env('ALI_accessKeySecret');
        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
                        ->regionId('cn-hangzhou') // replace regionId as you need
                        ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                                ->product('Dysmsapi')
                                // ->scheme('https') // https | http
                                ->version('2017-05-25')
                                ->action('SendSms')
                                ->method('POST')
                                ->host('dysmsapi.aliyuncs.com')
                                ->options([
                                                'query' => [
                                                'RegionId' => "default",
                                                'PhoneNumbers' => $mobile,
                                                'SignName' => $SignName,
                                                'TemplateCode' => $tp,
                                                'TemplateParam' => json_encode($data),
                                                ],
                                            ])
                                ->request();
            $result = $result->toArray();
            if($result['Code'] == 'OK')
            {
                return ['code'=>0,'msg'=>'发送成功'];
            }else
            {
                return ['code'=>1,'msg'=>$result['Message']];
            }
        } catch (ClientException $e) {
            return ['code'=>1,'msg'=>$e->getErrorMessage()];
        } catch (ServerException $e) {
            return ['code'=>1,'msg'=>$e->getErrorMessage()];
        }
    }

}
