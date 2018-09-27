<?php
namespace app\demo\controller;
 
 
use app\lib\event\PushEvent;
use think\Controller;
 
/**
 * 推送demo
 *
 * Class PushDemo
 * @package app\demo\controller
 */
class PushDemo extends Controller
{
    /**
     * 推送一个字符串
     */
    public function pushAString()
    {
        // $string = 'Man Always Remember Love Because Of Romance Only';
        // $string = input('msg') ? : $string;
      
        $push = new PushEvent();
        $push->setUser()->setContent('gh_fc6f55990311:oxHGW5FKUlEyH76csaHDqCe019wA')->push();
    }
 
    /**
     * 推送目标页
     *
     * @return \think\response\View
     */
    public function targetPage()
    {
        return view();
    }
}
