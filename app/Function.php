<?php

use Hyperf\Utils\ApplicationContext;
use HyperfExtension\Auth\AuthManager;

if (! function_exists('auth')) {
    /**
     * Auth认证辅助方法
     *
     */
    function auth(string $guard = 'api')
    {
        if (is_null($guard)) $guard = config('auth.default.guard');
        return make(AuthManager::class)->guard($guard);
    }
}
if (!function_exists('integerTime')) {
    function integerTime(string $interval)
    {
        list($numeric, $alpha) = sscanf($interval, "%d%[a-zA-Z]");
        $timestamps = time();
        $aa = 0;
        switch ($alpha) {
            case 'm':
                $time = date('i');
                switch ($numeric) {
                    case 5:
                        $aa = (intval($time / 5) + 1) * 5;
                        break;
                    case 15:
                        $aa = (intval($time / 15) + 1) * 15;
                        break;
                    case 30:
                        $aa = (intval($time / 30) + 1) * 30;
                        break;
                }
                $datetime = date("Y-m-d H:{$aa}:00");
                $timestamps = strtotime($datetime);
                break;
            case 'h':
                $time = date('H');
                switch ($numeric) {
                    case 1:
                        $aa = (intval($time / 1) + 1) * 1;
                        break;
                    case 2:
                        $aa = (intval($time / 2) + 1) * 2;
                        break;
                    case 4:
                        $aa = (intval($time / 3) + 1) * 3;
                        break;
                    case 6:
                        $aa = (intval($time / 6) + 1) * 6;
                        break;
                    case 8:
                        $aa = (intval($time / 8) + 1) * 8;
                        break;
                    case 12:
                        $aa = (intval($time / 12) + 1) * 12;
                        break;
                }
                $datetime = date("Y-m-d {$aa}:00:00");
                $timestamps = strtotime($datetime);
                break;
            case 'd':
                $time = date('d');
                switch ($numeric) {
                    case 1:
                        $aa = (intval($time / 1) + 1) * 1;
                        break;
                    case 2:
                        $aa = (intval($time / 2) + 1) * 2;
                        break;
                    case 3:
                        $aa = (intval($time / 3) + 1) * 3;
                        break;
                }
                $datetime = date("Y-m-{$aa}} 00:00:00");
                $timestamps = strtotime($datetime);
                break;
            case 'week':
                break;
            default:

        }
        return $timestamps;
    }
}
if (!function_exists('di')) {
    function di()
    {
        return ApplicationContext::getContainer();
    }
}
if (!function_exists('checkInputType')) {
    function checkInputType($input) {
        // 电子邮件正则表达式
        $emailPattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";

        // 手机号正则表达式（这里以中国大陆手机号为例，您可以根据需要调整）
        $phonePattern = "/^1[3-9]\d{9}$/";

        if (preg_match($emailPattern, $input)) {
            return "email";
        } elseif (preg_match($phonePattern, $input)) {
            return "phone";
        } else {
            return false;
        }
    }
}
