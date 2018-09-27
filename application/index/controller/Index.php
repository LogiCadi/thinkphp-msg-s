<?php
namespace app\index\controller;

use think\Request;
use think\Controller;
use think\console\output\descriptor\Console;
use app\index\model\Index as IndexModel;
use app\index\model\User as UserModel;
use app\index\model\ReplySetting as ReplySettingModel;
use think\Db;
use app\index\utils\WXBizMsgCrypt;
use think\Log;
use think\Loader;
use app\lib\event\PushEvent;
use app\index\utils\Utils;

class Index extends Controller
{
    public function index()
    {
        # code...
        return view();
    }
    /**
     * 接收消息
     * 微信小程序消息接收
     */
    public function send()
    {
        // return request()->get('echostr');

        //提取xml文档中的内容以字符串格式赋给变量
        $xmlfile = file_get_contents("php://input");
        //将字符串转化为变量
        $xml = simplexml_load_string($xmlfile);
        
        // 将xml数据写入$data
        $data = [];
        foreach ($xml->children() as $child)  //遍历所有节点数据
        {
            $data[$child->getName()] = (string)$child;
        }

        if ($data['MsgType'] == 'event') {
            $data['Content'] = '[进入]';
        } else if ($data['MsgType'] == 'image') {
            $data['Content'] = '[图片]';
        }

        // 将用户发送过来的消息保存到数据库
        $index = new IndexModel();
        // 防止重复接收同一条消息
        $exists = [];
        if (array_key_exists('MsgId', $data)) {
            $exists = $index->where('MsgId', $data['MsgId'])->find();
        } else {
            $exists = $index->where('FromUserName', $data['FromUserName'])->where('CreateTime', $data['CreateTime'])->find();
        }
        if ($exists) {
            return 'success';
        }

        $index->insert($data);

        // 自动回复
        $replySetting = new ReplySettingModel();
        $autoSend = $replySetting->where('appOriginId', $data['ToUserName'])->select();

        $sendText = "";
        foreach ($autoSend as $key => $value) {
            if ($data['Content'] == $value['keyword']) {
                $sendText = $value['content'];
                break;
            }
        }

        if ($sendText) {
            $this->sendToUser($data['FromUserName'], $sendText, $data['ToUserName']);
        }

        // 推送新的用户消息
        $push = new PushEvent();
        $push->setUser()->setContent($data['ToUserName'] . ":" . $data['FromUserName'])->push();

        // 返回success给微信服务器
        return 'success';
    }

    /**
     * 回复文字信息
     * @param string $openid 用户的openid
     * @param string $sendText 客服发送的文字消息
     * @param string $appOriginId 小程序原始id
     */
    public function sendToUser($openid, $sendText, $appOriginId)
    {
        $sendText = str_replace('\n', "\n", $sendText);
        if (!empty($sendText)) {
            $obj = [
                "touser" => $openid,
                "msgtype" => "text",
                "text" => ["content" => $sendText],
            ];

            $result = Utils::send($obj, $appOriginId);

            return $result;
        }
    }

    /**
     * 获取此小程序对话的所有用户信息
     * @param string $appOriginId 小程序原始id
     */
    public function getUsers($appOriginId)
    {
        $result = Db::table('think_index a,think_user b')->where('a.FromUserName = b.openid')->where('a.ToUserName', $appOriginId)->select();

        $data = [];
        foreach ($result as $key => $value) {

            if (!array_key_exists($value['FromUserName'], $data)) {
                $data[$value['FromUserName']]['noCheck'] = 0;
                $data[$value['FromUserName']]['lastTime'] = 0;
                $data[$value['FromUserName']]['lastMsg'] = '';
                $data[$value['FromUserName']]['nickName'] = $value['nickName'];
                $data[$value['FromUserName']]['avatarUrl'] = $value['avatarUrl'];
                $data[$value['FromUserName']]['gender'] = $value['gender'];
                $data[$value['FromUserName']]['province'] = $value['province'];
                $data[$value['FromUserName']]['city'] = $value['city'];
            }

            $data[$value['FromUserName']]['noCheck'] += $value['checked'];

            if ($value['CreateTime'] > $data[$value['FromUserName']]['lastTime']) {
                $data[$value['FromUserName']]['lastTime'] = $value['CreateTime'];
                $data[$value['FromUserName']]['lastMsg'] = $value['Content'];
            }

        }
        array_multisort(array_column($data, 'lastTime'), SORT_DESC, $data);

        return $data;
    }

    /**
     * 进入后台，显示用户信息
     */
    public function admin($appOriginId)
    {
        return $this->fetch('admin');
    }

    /**
     * 获取用户聊天详情
     * @param string $openid 用户的openid
     */
    public function getContent($openid)
    {
        $index = new IndexModel();

        $index->where('FromUserName', $openid)->update(['checked' => 0]);

        $result = Db::table('think_index a,think_user b')->where('a.FromUserName', $openid)->where('b.openid', $openid)
            ->field('a.id,a.CreateTime,a.FromUserName,a.ToUserName,a.MsgType,a.Content,a.PicUrl,a.cap,b.nickName,b.avatarUrl')
            ->select();

        // $data = [];
        // foreach ($result as $key => $value) {
        //     $result[$key]['CreateTime'] = date("Y-m-d H:i:s", $value['CreateTime']);
        // }
        return $result;
    }

    /**
     * 保存用户信息
     * @param string $userInfo 用户信息
     */

    public function saveUser($userInfo)
    {
        $user = new UserModel();

        $existUser = $user->where("openid", $userInfo['openid'])->select();

        if ($existUser) {
            $user->where('openid', $userInfo['openid'])->update($userInfo);
        } else {
            $user->insert($userInfo);
        }

    }
    /**
     * 获取所有关联小程序的信息
     */
    public function getxcxInfo()
    {
        $index = new IndexModel();

        $result = Db::table('think_xcx_info')->select();

        for ($i = 0; $i < count($result); $i++) {

            $appOriginId = $result[$i]['appOriginId'];

            $result[$i]['noCheck'] = $index->where('ToUserName', $appOriginId)->sum('checked');
        }

        return $result;
    }
}
