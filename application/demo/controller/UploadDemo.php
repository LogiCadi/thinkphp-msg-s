<?php
namespace app\demo\controller;



use think\Controller;
use think\Request;


class UploadDemo extends Controller
{
    public function index()
    {
        return view();
    }

    public function upload()
    {
       // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
            // 成功上传后 获取上传信息
            // 输出 jpg
                echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                echo $info->getSaveName();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
                echo $info->getFilename();
            } else {
            // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }

    public function getMediaId($file)
    {
        $file = '20180925\af88344f63ffe6656d18992c53aa53a9.jpg';

        $file_path = ROOT_PATH . 'public' . DS . 'uploads' . DS . $file;

        $data = array(
            'media' => new \CURLFile($file_path)
        );

        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=14_b2s8K9G2ySNg5C1bLRpeSSKOAm-MD5tAAsjHwSLqpCza4iYGfoB6-Ix7oLe_jnm8R1b4xjw_GqKr19Zofi88YocMaBKU_rHkbox1aCKPQ6GY1WH3BIMUAePgNgzVytobIfOibnK9Q1MkhHTIYKGdAJADPL&type=image';

        $curlOutput = $this->http_request($url, $data);

        return $curlOutput['media_id'];
    }

    
}
