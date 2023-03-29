<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateChatTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 创建 chat 表
        Schema::create('chat', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键ID');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->timestamps();
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat');
    }
}
