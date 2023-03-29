<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
           $table->string('order_no', 32)->primary();
            $table->unsignedBigInteger('user_id');
            $table->enum('payment_method', ['wechat', 'alipay']);
            $table->string('payment_qrcode')->nullable();
            $table->boolean('paid')->default(false);
            $table->dateTime('paid_time')->nullable();
            $table->bigInteger('package_id')->comment('发言包id');
            $table->string('package_name')->comment('发言包名称');
            $table->unsignedInteger('package_quota')->comment('加量包发言条数');
            $table->unsignedInteger('package_duration')->comment('加量包有效期，单位：天');
            $table->unsignedDecimal('amount', 10, 2)->comment('购买价格');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->tinyInteger('status')->comment('状态')->default(1);
            $table->timestamps();
            $table->index('user_id');
            $table->index('package_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
}
