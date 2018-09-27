<?php
namespace app\index\controller;

use think\Request;
use think\Controller;
use think\console\output\descriptor\Console;
use app\index\model\ReplySetting as ReplySettingModel;
use think\Db;
use app\index\utils\WXBizMsgCrypt;
use think\Log;
use think\Loader;

class Setting extends Controller
{

    /**
     * 获取自动回复设置
     */
    public function getReplySetting($appOriginId)
    {
        $replySetting = new ReplySettingModel();

        $result = $replySetting->where('appOriginId', $appOriginId)->select();

        return $result;
    }
    /**
     * 获取自动回复设置
     * @param $data 修改后的自动回复设置
     */
    public function settingUpdate($data)
    {
        $replySetting = new ReplySettingModel();

        $appOriginId = $data[0]['appOriginId'];
        
        $replySetting->where('appOriginId', $appOriginId)->delete();
        $replySetting->saveAll($data);

    }
}
