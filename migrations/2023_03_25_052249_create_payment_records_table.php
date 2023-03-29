<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreatePaymentRecordsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_records', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('支付流水ID');
            $table->timestamp('payment_time')->comment('支付时间');
            $table->decimal('payment_amount', 10, 2)->comment('支付金额');
            $table->string('payment_order_no')->nullable()->comment('支付订单号');
            $table->unsignedBigInteger('user_id')->nullable()->default(0)->comment('支付用户ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
}
