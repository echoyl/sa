<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\Attachment;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class UploadService
{
    public $file_ext_arr = [
        'rar', 'doc', 'docx', 'xls', 'ppt', 'zip', 'pdf', 'xlsx','bin'
    ];
    public $image_ext_arr = [
        'jpg', 'jpeg', 'png', 'gif','ico'
    ];

    var $tmp_enable;

    public function __construct($tmp_enable = true)
    {
        $this->tmp_enable = $tmp_enable;
    }
    

    public function store(Request $request, $formname = 'file', $type = 0, $insert_db = false,$toSize = false)
    {
        if(!$request->hasFile($formname))
        {
            return ['code' => 1, 'msg' => '无上传数据'];
        }
        $file = $request->file($formname);

        if(!$file)
        {
            return ['code' => 1, 'msg' => '无上传数据'];
        }

        $ext = $file->extension();

        if (!in_array($ext, array_merge($this->file_ext_arr, $this->image_ext_arr))) {
            return ['code' => 1, 'msg' => '格式错误'];
        }

        if (in_array($ext, ['zip', 'bin'])) {
            $ext = $this->getUnknowName($file);
        } elseif ($ext == 'xlsx') {
            $ext = 'xls';
        }

        $upload_tmp_enable = config('sa.upload_tmp_enable',false);

        $is_image = in_array($ext, $this->image_ext_arr) ? 1 : 0;

        $file_type = $is_image == 1 ? 'images' : 'files';

        $folder_name = $file_type . '/' . date("Ym");

        $path = $file->store($upload_tmp_enable && $this->tmp_enable?'tmp/'.$folder_name:$folder_name);

        if ($is_image) {
            $new_path = storage_path('app/public/' . $path);

            $path_parts = pathinfo($new_path);
            $height = Image::make($new_path)->getHeight();
            $width = Image::make($new_path)->getWidth();
            //d($height,$width,$toSize);
            //$thumb_url = $folder_name . '/' . str_replace('.', '_thumb.', $path_parts['basename']);

            //$thumbnail_file_path = storage_path('app/public/' . $thumb_url);

            //Image::make($new_path)->resize(200, 200, function ($constraint) {$constraint->aspectRatio();})->save($thumbnail_file_path);
            //获取参数是否压缩原图
            if($toSize)
            {
                if(is_numeric($toSize))
                {
                    if($toSize < $height || $toSize < $width)
                    {
                        Image::make($new_path)->resize($toSize, $toSize, function ($constraint) {$constraint->aspectRatio();})->save($new_path);
                    }
                    
                }elseif(is_array($toSize) && isset($toSize[1]))
                {
                    if($toSize[1] < $height || $toSize[0] < $width)
                    {
                        Image::make($new_path)->resize($toSize[0], $toSize[1], function ($constraint) {$constraint->aspectRatio();})->save($new_path);
                    }
                    
                }
                
            }else
            {
                //没有size 那么默认图片最大为1200
                $max_size = config('sa.admin_upload_max_wh',1000);
                if($height > $max_size || $width > $max_size)
                {
                    Image::make($new_path)->resize($max_size, $max_size, function ($constraint) {$constraint->aspectRatio();})->save($new_path);
                }
            }

            $height = Image::make($new_path)->getHeight();
            $width = Image::make($new_path)->getWidth();
            //d($height,$width);
            $base_set = (new SetsService)->get('base');

            if (isset($base_set['image_water']) && $base_set['image_water'] && isset($base_set['image_water_url']) && $base_set['image_water_url']) {
                //图片已经开始上传需要增加水印

                //d(public_path());
                $img = Image::make($new_path);
                $width = $img->getWidth() / 15;
                $x = 15;
                $y = 15;
                //d($width);
                $img->insert(Image::make(storage_path('app/public/' . $base_set['image_water_url']))->resize($width, $width), 'bottom-right', $x, $y)->save($new_path);
            }

        } else {
            $thumb_url = '';
            $height = $width = 200;
			if (in_array($ext, ['chm'])) {
				$new_path = storage_path('app/public/' . $path);

				$path_parts = pathinfo($new_path);

				[$_filename,$oext] = explode('.',$path_parts['basename']);

				$thumb_url = $folder_name . '/' . implode('.',[$_filename,$ext]);
				$path = $thumb_url;

				rename($new_path,storage_path('app/public/' . $thumb_url));
			}
        }
        $attachment_id = 0;
        if ($insert_db) {
            //如果需要插入数据库中
            $data = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'url' => $path,
                'ext' => $ext,
                //'thumb_url' => $thumb_url,
                'created_at' => now(),
                'height' => $height,
                'width' => $width,
                'type' => $type,
                'group_id' => $request->input('groupId', 0),
            ];
            $attachment_id = Attachment::insertGetId($data);
        }

        return ['code' => 0, 'data' => $path, 'attachment_id' => $attachment_id];
    }

    public function front($formname = 'file', $ext_arr = ['jpg', 'jpeg', 'png', 'gif'], $sizelimit = 5, $thumb = false, $rewrite = false)
    {
        $file = request()->file($formname);

        if (!$file) {
            return ['code' => 1, 'msg' => '获取数据错误'];
        }

        $ext = $file->getClientOriginalExtension();

        if ($sizelimit > 0) {
            //$size = $file->size
            //检测文件大小每个文件不能超过5m
            if ($file->getSize() > $sizelimit * 1024 * 1024) {
                //大于5m返回错误
                return ['code' => 1, 'msg' => '单个文件不能超过5M'];
            }
        }

        if (!in_array($ext, $ext_arr)) {
            return ['code' => 1, 'msg' => '格式错误'];
        }
        if ($ext == 'zip') {
            $ext = $this->getUnknowName($file);

        } elseif ($ext == 'xlsx') {
            $ext = 'xls';
        }
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 1 : 0;

        $upload_tmp_enable = config('sa.upload_tmp_enable',false);

        $fileType = !$isImage ? 'files' : 'images';
        if ($isImage) {
            $filepath = 'user/' . $fileType . '/' . date("Ym");
            $filepath = $upload_tmp_enable && $this->tmp_enable?'tmp/'.$filepath:$filepath;
            $path = $file->store($filepath, 'public');
            $newPath = storage_path('app/public/' . $path);
        } else {
            $filename = uniqid() . '.' . $ext;
            $public_path = storage_path($upload_tmp_enable && $this->tmp_enable?'app/public/tmp/user/files/' . date("Ym"):'app/public/user/files/' . date("Ym"));
            $file->move($public_path, $filename);
            $path = 'user/files/' . date("Ym") . '/' . $filename;
            $newPath = $public_path . '/' . $filename;
        }
        

        //生成缩略图
        $thumb_url = $path;
        if ($isImage && $thumb) {
            $max_size = config('sa.user_upload_max_wh',800);//读取压缩图片配置信息
            $height = Image::make($newPath)->getHeight();
            $width = Image::make($newPath)->getWidth();
            //d($width,$height,$max_size,$newPath);
            //$path_parts = pathinfo($newPath);
            //$thumbnail_file_path = storage_path('app/public/user/' . $fileType . '/' . date('Ym') . '/' . str_replace('.', '_thumb.', $path_parts['basename']));
            if($height > $max_size || $width > $max_size)
            {
                Image::make($newPath)->resize($max_size, $max_size, function ($constraint) {$constraint->aspectRatio();})->save($newPath);
                //$thumb_url = 'user/' . $fileType . '/' . date('Ym') . '/' . str_replace('.', '_thumb.', $path_parts['basename']);
                //d($newPath);
            }else
            {
                //$thumb_url = 'user/' . $fileType . '/' . date('Ym') . '/' . $path_parts['basename'];
            }
            
            // Image::make($newPath)->resize(800, 800, function ($constraint) {$constraint->aspectRatio();})->save($thumbnail_file_path);
            // $thumb_url = 'user/' . $fileType . '/' . date('Ym') . '/' . str_replace('.', '_thumb.', $path_parts['basename']);
            $height = Image::make($newPath)->getHeight();
            $width = Image::make($newPath)->getWidth();
            //检测图片如果大小还大于500kb 再次压缩图片至 600*600

            if ($rewrite) {
                //删除原始图片
                //@unlink($newPath);
            }

        } else {
            $thumb_url = '';
            $height = $width = 200;
        }

        //新增功能 将图片插入到数据库，统一管理用户上传的图片
        //前台上传图片不插入数据库中

        $base_set = (new SetsService)->get('base');

        if (isset($base_set['image_water']) && $base_set['image_water'] && isset($base_set['image_water_url']) && $base_set['image_water_url']) {
            //图片已经开始上传需要增加水印

            //d(public_path());
            $img = Image::make($newPath);
            $width = $img->getWidth() / 15;
            $x = 15;
            $y = 15;
            //d($width);
            $img->insert(Image::make(storage_path('app/public/' . $base_set['image_water_url']))->resize($width, $width), 'bottom-right', $x, $y)->save($newPath);
        }

        return ['code' => 0, 'msg' => '上传成功', 'data' => ['value' => $path, 'src' => tomedia($path), 'thumb_url' => $thumb_url]];
    }

    public function getUnknowName($file)
    {
        $filename = $file->getClientOriginalName();
        $pathinfo = pathinfo($filename);
        $extension = $pathinfo['extension'];
        $key = [
            'pptx' => 'ppt',
            'xlsx' => 'xls',
            'docx' => 'doc',
			'chm'=>'chm'
        ];
        return isset($key[$extension]) ? $key[$extension] : 'zip';
    }

    public function base64Image($base64_image_content, $path)
    {
        $m = preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result);

        if (!$m) {
            $result = ['', '', 'jpg'];
        }
        //图片后缀
        $type = $result[2];
        if (!in_array($type, ['png', 'jpg', 'jpeg', 'gif'])) {
            return ['code' => 1, 'msg' => '图片格式有误！'];
        }
        //保存位置--图片名
        $image_name = date('His') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT) . "." . $type;
        //$path= 'Uploads/image/';
        $wenjianjia = $path . '/' . date('Ym') . '/';
        $sapath = $path;
        $path = storage_path('app/public/' . $wenjianjia);
        mkdirs($path);
        $image_url = $path . $image_name;
        $image_value = $wenjianjia . $image_name;
//             $this->mkdirs($image_url);
        //解码
        $decode = base64_decode(str_replace($result[1], '', $base64_image_content));
        if (file_put_contents($image_url, $decode)) {

            //在这里压缩下图片
            Image::make($image_url)->resize(1000, 1000, function ($constraint) {$constraint->aspectRatio();})->save($image_url);

            $data['code'] = 0;
            $data['url'] = $image_value;
            $data['msg'] = '保存成功！';
        } else {
            $data['code'] = 1;
            $data['url'] = '';
            $data['msg'] = '图片保存失败！';
        }

        return $data;
    }

    public function qrcode($bg, $num = 0, $userid = 0)
    {

        // $width = $img->getWidth()/15;
        // $x = 15;
        // $y = 15;
        //d($width);
        //$num = User::count();

        $filename = 'qrcode/user_' . $userid . '.png';

        $newPath = storage_path('app/public/' . $filename);

        if (file_exists($newPath)) {
            $filemtime = filemtime($newPath);
            if ($filemtime + 3600 > time()) {
                return $filename;
            }
        }
        $url = url('qrcode', ['fromid' => $userid]);
        $qrcode = storage_path('app/public/qrcode.png');
        QrCode::format('png')->size(280)->margin(0)->generate($url, $qrcode);

        $qrbg = storage_path('app/public/' . $bg);

        $img = Image::make($qrbg);

        $img->insert(Image::make($qrcode), 'top-left', 34, 106)->text('text', 107, 42, function ($font) {
            $font->file(public_path('font/msyhbd.ttc'));
            $font->color('#333333');
            $font->size(18);
            $font->align('left');
        })->text('已有' . $num . '人加入', 107, 68, function ($font) {
            $font->file(public_path('font/msyhbd.ttc'));
            $font->color('#b2b2b2');
            $font->size(14);
            $font->align('left');
        })->save($newPath);

        return $filename;
    }

    public function userQrcode($bg, $userid = 0)
    {

        // $width = $img->getWidth()/15;
        // $x = 15;
        // $y = 15;
        //d($width);
        //$num = User::count();

        $filename = 'qrcode/user_shangjia_' . $userid . '.png';

        $newPath = storage_path('app/public/' . $filename);

        if (file_exists($newPath)) {
            return $filename; //不再重新生成
            $filemtime = filemtime($newPath);
            if ($filemtime + 3600 > time()) {
                return $filename;
            }
        }
        $url = url('qrcode', ['fromid' => $userid]);
        $qrcode = storage_path('app/public/' . $filename);
        QrCode::format('png')->size(280)->margin(0)->generate($url, $qrcode);

        return $filename;
    }

    public function video(Request $request)
    {
        set_time_limit(0);
        $result = ['code' => 1, 'msg' => '请拍摄视频上传'];
        $file = $request->file('file');
        if (!$file) {
            return $result;
        }
        $ext = $file->extension();
        if (!in_array($ext, ['mp4', 'qt'])) {
            $result['msg'] = '视频格式不正确:' . $ext;
            return $result;
        }
        $qt = $ext == 'qt' ? true : false;
        $size = $file->getSize();
        if ($size > 50 * 1024 * 1024) {
            return ['code' => 1, 'msg' => '视频大小不能超过50M'];
        }
        //d($file->getRealPath());
        //d(app_path());

        try {
            //先存入缓存
            $path = $file->store('');
            $newPath = storage_path('app/public/' . $path);
            require_once app_path() . DIRECTORY_SEPARATOR . 'Libs' . DIRECTORY_SEPARATOR . 'voduploadsdk' . DIRECTORY_SEPARATOR . 'Autoloader.php';
            $uploader = new \AliyunVodUploader(env('ALI_accessKeyId_video'), env('ALI_accessKeySecret_video'));
            $uploadVideoRequest = new \UploadVideoRequest($newPath, $file->getClientOriginalName());
            //$uploadVideoRequest->setCateId(1);
            //$uploadVideoRequest->setCoverURL("http://xxxx.jpg");
            //$uploadVideoRequest->setTags('test1,test2');
            //$uploadVideoRequest->setStorageLocation('outin-xx.oss-cn-beijing.aliyuncs.com');
            if ($qt) {
                $uploadVideoRequest->setTemplateGroupId('7a1216f13a466a89dfc9546f1a4da4e6');
            }

            $userData = array(
                "MessageCallback" => array("CallbackURL" => "https://demo.sample.com/ProcessMessageCallback"),
                "Extend" => array("localId" => "xxx", "test" => "www"),
            );
            //$uploadVideoRequest->setUserData(json_encode($userData));
            $videoId = $uploader->uploadLocalVideo($uploadVideoRequest);
            unlink($newPath);
            $result = ['code' => 0, 'msg' => '上传成功，获取播放链接需要等待一点时间', 'data' => $videoId, 'video_url' => '-1'];
            ob_end_clean();
            return $result;
        } catch (\Exception $e) {
            $result = ['code' => 1, 'msg' => $e->getMessage()];
            ob_end_clean();
            return $result;
        }

    }

    public function isImage($file)
    {
        if(!is_file($file))
        {
            return false;
        }
        $pathinfo = pathinfo($file);
        $ext = $pathinfo['extension']??'';

        return in_array($ext, $this->image_ext_arr);
    }

    /**
     * 是否需要压缩
     *
     * @param [type] $file 文件路径
     * @param array $size 图片尺寸 像素
     * @param integer $quality 图片大小限制 kb
     * @return boolean
     */
    public function shouldBeCompressed($file,$size = [1000,1000],$quality = 512)
    {
        if(!$this->isImage($file))
        {
            return false;
        }
        $filesize = filesize($file);

        //修改为大于某个大小后直接是需要压缩
        if($filesize < $quality * 1024)
        {
            return false;
        }

        $height = Image::make($file)->getHeight();
        $width = Image::make($file)->getWidth();

        if($height <= $size[1] && $width <= $size[0])
        {
            return false;
        }

        //图片大小小于设定值 或者 长宽都小于设定值时 都不压缩
        return true;

    }

    /**
     * resize图片尺寸
     *
     * @param [type] $file 图片路径
     * @param [number[],number] $toSize 改变后的大小 
     * @return void
     */
    public function resizeImage($file,$toSize)
    {
        if(is_numeric($toSize))
        {
            $toWitdh = $toHeight = $toSize;
            
        }elseif(is_array($toSize) && isset($toSize[1]))
        {
            $toWitdh = $toSize[0];
            $toHeight = $toSize[1];
        }
        Image::make($file)->resize($toWitdh, $toHeight, function ($constraint) {$constraint->aspectRatio();})->save($file);
        return;
    }

    public function compressImagesInDir($dir)
    {
        $compressed = [];
        $list = scandir($dir);
        foreach($list as $file)
        {
            if ($file != "." && $file != "..") {
                //检测是否是image
                $filepath = $dir.'/'.$file;
                if($this->shouldBeCompressed($filepath))
                {
                    //压缩图片
                    $this->resizeImage($filepath,1000);
                    $compressed[] = $file;
                }
            }
        }
        return $compressed;
    }

}
