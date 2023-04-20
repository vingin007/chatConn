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
if (!function_exists('dtw_distance')) {
    function dtw_distance($s1, $s2) {
        $s1_len = strlen($s1);
        $s2_len = strlen($s2);
        $matrix = [];

        for ($i = 0; $i <= $s1_len; ++$i) {
            $matrix[$i] = [];
            for ($j = 0; $j <= $s2_len; ++$j) {
                $matrix[$i][$j] = INF;
            }
        }

        $matrix[0][0] = 0;

        for ($i = 1; $i <= $s1_len; ++$i) {
            for ($j = 1; $j <= $s2_len; ++$j) {
                $cost = abs(ord($s1[$i - 1]) - ord($s2[$j - 1]));
                $matrix[$i][$j] = $cost + min(
                        $matrix[$i - 1][$j],
                        $matrix[$i][$j - 1],
                        $matrix[$i - 1][$j - 1]
                    );
            }
        }

        return $matrix[$s1_len][$s2_len];
    }
}

if (!function_exists('levenshtein_distance')) {
    function levenshtein_distance($s1, $s2) {
        $s1_len = strlen($s1);
        $s2_len = strlen($s2);
        $matrix = [];

        for ($i = 0; $i <= $s1_len; ++$i) {
            $matrix[$i] = [];
            for ($j = 0; $j <= $s2_len; ++$j) {
                if ($i == 0) {
                    $matrix[$i][$j] = $j;
                } elseif ($j == 0) {
                    $matrix[$i][$j] = $i;
                } else {
                    $matrix[$i][$j] = 0;
                }
            }
        }

        for ($i = 1; $i <= $s1_len; ++$i) {
            for ($j = 1; $j <= $s2_len; ++$j) {
                $cost = ($s1[$i - 1] == $s2[$j - 1]) ? 0 : 1;
                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,
                    $matrix[$i][$j - 1] + 1,
                    $matrix[$i - 1][$j - 1] + $cost
                );
            }
        }

        return $matrix[$s1_len][$s2_len];
    }
}
if (!function_exists('secondsToTimecode')) {
    function secondsToTimecode($seconds)
    {
        $hours = floor(bcdiv($seconds, 3600, 6));
        $minutes = floor(bcdiv(bcmod($seconds, 3600), 60, 6));
        $seconds_int = floor(bcmod($seconds, 60));
        $milliseconds = bcmul(bcsub($seconds, floor($seconds), 6), 1000, 0);

        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $seconds_int, $milliseconds);
    }


}