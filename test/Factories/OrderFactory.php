<?php
namespace HyperfTest\Factories;

use App\Model\Order;
use App\Model\Package;
use App\Model\User;
use Carbon\Carbon;

class OrderFactory
{
    public static function new(User $user, Package $package, $amount = null, $status = Order::STATUS_UNPAID, $expiredAt = null)
    {
        $order = new Order();
        $order->order_no = uniqid();
        $order->user_id = $user->id;
        $order->paid = 0;
        $order->package_id = $package->id;
        $order->package_name = $package->name;
        $order->package_quota = $package->quota;
        $order->package_duration = $package->duration;
        $order->amount = $amount ?? $package->price;
        $order->expired_at = $expiredAt ?? Carbon::now()->addDays(30);
        $order->status = $status;
        $order->save();
        return $order;
    }
}
