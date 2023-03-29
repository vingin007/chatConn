<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateMessageTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键ID');
            $table->unsignedBigInteger('chat_id')->comment('聊天记录ID');
            $table->unsignedBigInteger('user_id')->comment('用户id');
            $table->text('content')->comment('内容');
            $table->tinyInteger('is_user')->default(0)->comment('是否是用户发送的消息');
            $table->enum('type', ['text', 'image', 'audio']);
            $table->string('store_name')->comment('存储名称')->default('');
            $table->text('url')->comment('7天链接')->nullable(true);
            $table->timestamps();
            $table->index('chat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message');
    }
}
