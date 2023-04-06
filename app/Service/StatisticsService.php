<?php

namespace App\Service;

use Carbon\Carbon;
use Hyperf\DbConnection\Db;

class StatisticsService
{

    public function user7daysCount()
    {
        $new_users = [];
        $new_real_name_users = [];
        $new_paid_users = [];

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);
            $day_new_users = Db::table('users')
                ->whereDate('created_at', $date)
                ->count();

            $day_new_real_name_users = Db::table('users')
                ->whereDate('bind_time', $date)
                ->count();

            $day_new_paid_users = Db::table('users')
                ->whereDate('paid_time', $date)
                ->count();

            $new_users[] = $day_new_users;
            $new_real_name_users[] = $day_new_real_name_users;
            $new_paid_users[] = $day_new_paid_users;
        }

        $result = [
            [
                'name' => '新增用户',
                'type' => 'line',
                'stack' => '总量',
                'data' => $new_users,
            ],
            [
                'name' => '新增实名用户',
                'type' => 'line',
                'stack' => '总量',
                'data' => $new_real_name_users,
            ],
            [
                'name' => '新增付费用户',
                'type' => 'line',
                'stack' => '总量',
                'data' => $new_paid_users,
            ],
        ];

        return $result;
    }

    public function order7daysCount()
    {
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);
            $day_orders = Db::table('orders')
                ->whereDate('created_at', $date)
                ->count();

            $day_sales = Db::table('orders')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $day_basic_orders = Db::table('orders')
                ->where('package_id', 2)
                ->whereDate('created_at', $date)
                ->count();

            $day_basic_sales = Db::table('orders')
                ->where('package_id', 2)
                ->whereDate('created_at', $date)
                ->sum('amount');

            $day_premium_orders = Db::table('orders')
                ->where('package_id', 1)
                ->whereDate('created_at', $date)
                ->count();

            $day_premium_sales = Db::table('orders')
                ->where('package_id', 1)
                ->whereDate('created_at', $date)
                ->sum('amount');

            $orders[] = $day_orders;
            $sales[] = $day_sales;
            $basic_orders[] = $day_basic_orders;
            $basic_sales[] = $day_basic_sales;
            $premium_orders[] = $day_premium_orders;
            $premium_sales[] = $day_premium_sales;
        }
        $result = [
            [
                'name' => '订单数',
                'type' => 'line',
                'stack' => '总量',
                'data' => $orders,
            ],
            [
                'name' => '销售额',
                'type' => 'line',
                'stack' => '总量',
                'data' => $sales,
            ],
            [
                'name' => '基础版订单数',
                'type' => 'line',
                'stack' => '总量',
                'data' => $basic_orders,
            ],
            [
                'name' => '基础版销售额',
                'type' => 'line',
                'stack' => '总量',
                'data' => $basic_sales,
            ],
            [
                'name' => '高级版订单数',
                'type' => 'line',
                'stack' => '总量',
                'data' => $premium_orders,
            ],
            [
                'name' => '高级版销售额',
                'type' => 'line',
                'stack' => '总量',
                'data' => $premium_sales,
            ],
        ];
        return $result;
    }



    public function gpt7daysCount()
    {
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);

            $day_gpt_consumption = Db::table('message')
                ->where(function ($query) {
                    $query->where('is_user', 1)->where('type', 'audio');
                })
                ->orWhere(function ($query) {
                    $query->where('is_user', 0);
                })
                ->whereDate('created_at', $date)
                ->count();

            $day_text_to_voice_consumption = Db::table('message')
                ->where('type', 'audio')
                ->whereDate('created_at', $date)
                ->count();

            $day_voice_to_text_consumption = Db::table('message')
                ->where('type', 'audio')
                ->whereDate('created_at', $date)
                ->count();

            $day_storage_cost = Db::table('message')
                    ->whereNotNull('voice_duration')
                    ->whereDate('created_at', $date)
                    ->sum('voice_duration');
            $gpt_consumption[] = $day_gpt_consumption;
            $text_to_voice_consumption[] = $day_text_to_voice_consumption;
            $voice_to_text_consumption[] = $day_voice_to_text_consumption;
            $storage_cost[] = $day_storage_cost;
        }
        $result = [
            [
                'name' => 'GPT 消耗',
                'type' => 'line',
                'stack' => '总量',
                'data' => $gpt_consumption,
            ],
            [
                'name' => '语音转文字消耗',
                'type' => 'line',
                'stack' => '总量',
                'data' => $voice_to_text_consumption,
            ],
            [
                'name' => '文字转语音消耗',
                'type' => 'line',
                'stack' => '总量',
                'data' => $text_to_voice_consumption,
            ],
            [
                'name' => '存储费用',
                'type' => 'line',
                'stack' => '总量',
                'data' => $storage_cost,
            ],
        ];
        return $result;
    }

    public function getAllMessageStatistics()
    {
        $result = [];

        $accumulated_gpt_consumption = Db::table('message')
            ->where(function ($query) {
                $query->where('is_user', 1)->where('type', 'audio');
            })
            ->orWhere(function ($query) {
                $query->where('is_user', 0);
            })
            ->count();

        $accumulated_voice_to_text_consumption = Db::table('message')
            ->where('type', 'audio')
            ->count();

        $accumulated_text_to_voice_consumption = Db::table('message')
            ->where('type', 'audio')
            ->count();

        $accumulated_storage_cost = Db::table('message')
            ->whereNotNull('voice_duration')
            ->sum('voice_duration');

        $data = [
            $accumulated_gpt_consumption,
            $accumulated_voice_to_text_consumption,
            $accumulated_text_to_voice_consumption,
            round($accumulated_storage_cost, 2),
        ];
        $result[] = [
            'data' => $data,
            'type' => 'bar',
        ];
        return $result;
    }
    public function getAllOrderStatistics()
    {
        $result = [];

        $accumulated_orders = Db::table('orders')
            ->count();

        $accumulated_sales = Db::table('orders')
            ->sum('amount');

        $accumulated_basic_orders = Db::table('orders')
            ->where('package_id', 2)
            ->count();

        $accumulated_basic_sales = Db::table('orders')
            ->where('package_id', 2)
            ->sum('amount');

        $accumulated_premium_orders = Db::table('orders')
            ->where('package_id', 1)
            ->count();

        $accumulated_premium_sales = Db::table('orders')
            ->where('package_id', 1)
            ->sum('amount');
        $result = [];
        $result[] = [
            'name' => '销售额',
            'type' => 'bar',
            'data' => [$accumulated_basic_sales, $accumulated_premium_sales,$accumulated_sales],
        ];
        $result[] = [
            'name' => '销量',
            'type' => 'bar',
            'data' => [$accumulated_basic_orders, $accumulated_premium_orders,$accumulated_orders],
        ];

        return $result;
    }
    public function getAllUserStatistics()
    {
        $result = [];

        $accumulated_users = Db::table('users')->whereNull('bind_time')->count();

        $accumulated_real_name_users = Db::table('users')
            ->whereNotNull('bind_time')
            ->count();

        $accumulated_paid_users = Db::table('users')
            ->whereNotNull('paid_time')
            ->count();
        $result = [
            [
                'name' => '未认证用户',
                'value' => $accumulated_users,
            ],
            [
                'name' => '已实名用户',
                'value' => $accumulated_real_name_users,
            ],
            [
                'name' => '已付费用户',
                'value' => $accumulated_paid_users,
            ],
        ];
        return $result;
    }

}
