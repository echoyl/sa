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
            $accessKeyId = env('ALI_accessKeyId_video');
            $accessKeySecret = env('ALI_accessKeySecret_video');
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
            return $url['PlayInfoList']['PlayInfo'][0]['PlayURL'];
        } catch (ClientException $e) {
            //return $e->getErrorMessage().":".$e->getErrorCode();
        } catch (ServerException $e) {
            //return $e->getErrorMessage().":".$e->getErrorCode();
        }
        return '';
    }

    public function createUploadVideo($title,$filename,$qt_tpl = '7a1216f13a466a89dfc9546f1a4da4e6')
    {
        $accessKeyId = env('ALI_accessKeyId_video');
        $accessKeySecret = env('ALI_accessKeySecret_video');
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
        $accessKeyId = env('ALI_accessKeyId_video');
        $accessKeySecret = env('ALI_accessKeySecret_video');
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

}
