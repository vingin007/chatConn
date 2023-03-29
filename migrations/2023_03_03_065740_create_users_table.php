<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username');
            $table->string('password');
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('wechat_openid')->nullable();
            $table->string('telegram_id')->nullable();
            $table->dateTime('register_time')->useCurrent();
            $table->integer('quota')->default(0);
            $table->unsignedTinyInteger('level')->default(0);
            $table->tinyInteger('email_valid')->default(0);
            $table->dateTime('expire_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
