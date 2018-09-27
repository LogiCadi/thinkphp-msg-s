<?php
namespace app\index\controller;

use think\Controller;
use app\lib\request\CurlFunction;
use app\index\utils\Utils;
use think\Db;
use think\Log;

class Upload extends Controller
{
    /**
     * 图片上传至服务器本地
     */
    public function upload($appOriginId, $openid)
    {
       // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');
        
        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $info = $file->validate(['size' => 2097152, 'ext' => 'jpg,png,gif'])->move(ROOT_PATH . 'web' . DS . 'uploads');
            if ($info) {

                $file_path = ROOT_PATH . 'web' . DS . 'uploads' . DS . $info->getSaveName();
                // 1.上传至微信临时素材服务器，获得media_id
                $mediaId = $this->getMediaId($file_path, $appOriginId);

                // 2.根据mediaId获取图片url
                // $picUrl = $this->getPicUrl($appOriginId, $mediaId);

                // 删除文件(备用)
                // unlink($file_path);
                // 2.发送图片给用户
                return $this->sendImgToUser($openid, $mediaId, $appOriginId, 'uploads' . DS . $info->getSaveName());
            } else {
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }

    /**
     * 获取微信素材media_id
     * @param string $file 上传的图片文件相对位置
     * @param string @appOriginId
     */
    public function getMediaId($file_path, $appOriginId)
    {
        $data = array(
            'media' => new \CURLFile($file_path)
        );

        $accessToken = Db::table('think_xcx_info')->where('appOriginId', $appOriginId)->find()['accesstoken'];

        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $accessToken . '&type=image';
        $curlOutput = Utils::http_request($url, $data);

        if (array_key_exists('errcode', $curlOutput)) {
            $errcode = $curlOutput['errcode'];
            if ($errcode == 42001 || $errcode == 40001) {
                // access_token无效，需要重新获取
                $accessToken = Utils::getToken($appOriginId);
                Db::table('think_xcx_info')->where('appOriginId', $appOriginId)->update(['accesstoken' => $accessToken]);

                // 再次重试发送
                $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $accessToken . '&type=image';
                $curlOutput = Utils::http_request($url, $data);
            }
        }

        if (array_key_exists('errcode', $curlOutput)) {
            // 仍然报错
            Log::record($curlOutput);
        } else {
            return $curlOutput['media_id'];
        }

    }

    /**
     * 根据meida_id获取图片picUrl
     */
    public function getPicUrl($appOriginId, $mediaId)
    {
        $accessToken = Db::table('think_xcx_info')->where('appOriginId', $appOriginId)->find()['accesstoken'];
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token=' . $accessToken . '&media_id=' . $mediaId;
       
        $curlOutput = Utils::http_request($url);

        if (array_key_exists('errcode', $curlOutput)) {
            $errcode = $curlOutput['errcode'];
            if ($errcode == 42001 || $errcode == 40001) {
                // access_token无效，需要重新获取
                $accessToken = Utils::getToken($appOriginId);
                Db::table('think_xcx_info')->where('appOriginId', $appOriginId)->update(['accesstoken' => $accessToken]);

                // 再次重试发送
                $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $accessToken . '&type=image';
                $curlOutput = Utils::http_request($url, $data);
            }
        }

        if (array_key_exists('errcode', $curlOutput)) {
            // 仍然报错
            Log::record($curlOutput);
        } else {
            // return $curlOutput['media_id'];
            Log::record($curlOutput);
        }
    }

    /**
     * 发送图片给用户
     * @param string $mediaId 
     * @param string $file_path 图片路径
     */
    public function sendImgToUser($openid, $mediaId, $appOriginId, $file_path = null)
    {
        $obj = [
            "touser" => $openid,
            "msgtype" => "image",
            "image" => ["media_id" => $mediaId],
        ];

        $result = Utils::send($obj, $appOriginId, $file_path);

        return $result;
    }

}
