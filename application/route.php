<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;

Route::get([
    // 后台消息系统
    'admin/:appOriginId$' => 'index/Index/admin',
    // 获取所有关联小程序的信息
    'admin/getxcxInfo$' => 'index/Index/getxcxInfo',
    // 按小程序原始id（appOriginId）获得所有用户信息
    'admin/getUsers/:appOriginId$' => 'index/Index/getUsers',
    // 根据openid获取该用户的聊天详情
    'admin/content/:openid$' => 'index/Index/getContent',
    // 获取自动回复设置
    'admin/getReplySetting/:appOriginId$' => 'index/Setting/getReplySetting',
]);

Route::post([
    // 微信小程序消息接收
    'admin/send$' => 'index/Index/send', 
    // 人工发送消息给用户
    'admin/sendmsg$' => 'index/Index/sendToUser', 
    // 修改自动回复设置
    'admin/settingUpdate$' => 'index/Setting/settingUpdate', 
    // 小程序端用户授权，后台保存用户详细信息
    'admin/saveUser$' => 'index/Index/saveUser', 
    // 图片上传
    'admin/upload$' => 'index/Upload/upload',
]);


return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]' => [
        ':id' => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

];
