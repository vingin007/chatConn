<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTransOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trans_orders', function (Blueprint $table) {
            $table->string('order_no',20)->primary();
            $table->unsignedBigInteger('user_id');
            $table->bigInteger('payment_method_id');
            $table->string('original_video_id');
            $table->string('original_video_store_name');
            $table->string('transcribed_video_store_name')->nullable();
            $table->string('translated_subtitle_video_store_name')->nullable();
            $table->string('transcribed_subtitle_store_name')->nullable();
            $table->string('translated_subtitle_store_name')->nullable();
            $table->timestamp('paid_time');
            $table->tinyInteger('status')->comment('状态')->default(0);
            $table->double('video_duration');
            $table->double('order_amount');
            $table->unsignedBigInteger('video_size');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trans_orders');
    }
}
