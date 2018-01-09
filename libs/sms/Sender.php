<?php
/**
 * 提供统一的对外发送的服务
 *
 * $sms = new \Sms\Sender();
 * $sms->send('15923297663', 'test');
 */
namespace Sms;

use \Thrift\SmsService;

class Sender
{

    // ====================
    // 外部接口
    // ====================

    /**
     * 发送短信
     * @param  $mobile string
     * @param  $msg    string
     * @param  $exinfo string
     * @return array
     */
    public function send($mobile, $msg, $isSplit = true)
    {
        $phone = [];
        if ($isSplit) {
            $phone = splitPhone($mobile);
        } else {
            $phone['phone']  = $mobile;
            $phone['c_code'] = '86';
        }

        return SmsService::instance()->send($phone['c_code'], $phone['phone'], $msg);
    }

    /**
     * 发送短信
     * @param  $mobile string
     * @param  $msg    string
     * @param  $exinfo string
     * @return array
     */
    public function send_old($mobile, $msg)
    {
        $mobile = substr(pure_num($mobile), 0, 11);
        $type   = cache('sms_sender_type');
        $last   = cache("sms_sender_last_{$mobile}");

        // 忽略10秒之内的重复发送
        if (time() - $last <= 60) {
            return ['res' => 10000, 'msg' => '请稍后再试'];
        }

        switch (true) {
            case ('internal' === $type):
                $sms = new \Sms\Internal();
                $res = $sms->send($mobile, $msg);
                cache('sms_sender_type', 'international');
                break;

            case ('international' === $type):
                $sms = new \Sms\International();
                $res = $sms->code('86')->send($mobile, $msg);
                cache('sms_sender_type', 'internal');
                break;

            default:
                $sms = new \Sms\Internal();
                $res = $sms->send($mobile, $msg);
                cache('sms_sender_type', 'international');
                break;
        }
        cache("sms_sender_last_{$mobile}", time(), 60); // 缓存60秒
        return $res;
    }
}
