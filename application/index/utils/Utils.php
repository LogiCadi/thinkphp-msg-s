<?php
namespace app\index\utils;

use think\Log;
use think\Db;
use app\index\model\Index as IndexModel;

class Utils
{
    /**
     * HTTP请求（支持HTTP/HTTPS，支持GET/POST）
     */
    public static function http_request($url, $data = null)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        if ($data) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($curl);

        curl_close($curl);
        return json_decode($output, true);
    }

    /**
     * 获取小程序的access_token
     * 1.access_token的有效期是7200秒（两小时）
     * 2.获取access_token接口的调用频率限制为2000次/天
     */
    public static function getToken($appOriginId)
    {
        // 从数据库获得appid和appsecret
        $result = Db::table('think_xcx_info')->where('appOriginId', $appOriginId)->field('appid,appsecret')->find();
        
        // 获取token
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $result['appid'] . "&secret=" . $result['appsecret'];
        $curlOutput = Utils::http_request($url);

        return $curlOutput['access_token'];
    }

    /**
     * 发消息
     * @param array $post_data 发送的消息
     * @param string $file_path 图片文件路径
     */
    public static function send($post_data, $appOriginId, $file_path = null)
    {
        $data = json_encode($post_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // 从数据库获取最新的access_token
        $accessToken = Db::table('think_xcx_info')->where('appOriginId', $appOriginId)->find()['accesstoken'];
            
        // 发送消息
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $accessToken;
        $curlOutput = Utils::http_request($url, $data);

        $errcode = $curlOutput['errcode'];

        if ($errcode == 42001 || $errcode == 40001) {
            // access_token无效，需要重新获取
            $accessToken = Utils::getToken($appOriginId);
            Db::table('think_xcx_info')->where('appOriginId', $appOriginId)->update(['accesstoken' => $accessToken]);

            // 再次重试发送消息
            $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $accessToken;
            $curlOutput = Utils::http_request($url, $data);
        }

        if ($curlOutput['errcode'] == 0) {
            // 将发送的消息保存到数据库
            $index = new IndexModel();
            $saveData = [
                'checked' => 0,
                'ToUserName' => $appOriginId,
                'FromUserName' => $post_data['touser'],
                'CreateTime' => time() + 5,
                'MsgType' => $post_data['msgtype'],
                'cap' => 1,
            ];
            if (array_key_exists('image', $post_data)) {
                $saveData['Content'] = '[图片]';
                $saveData['MediaId'] = $post_data['image']['media_id'];
                $saveData['PicUrl'] = $file_path;
            } else {
                $saveData['Content'] = $post_data['text']['content'];
            }

            $index->insert($saveData);
        }

        return $curlOutput;
    }
}