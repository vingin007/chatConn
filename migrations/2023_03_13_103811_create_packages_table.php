<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreatePackagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名称');
            $table->integer('quota')->comment('发言条数');
            $table->integer('duration')->comment('有效时长（天）');
            $table->tinyInteger('status')->comment('状态');
            $table->decimal('price', 8, 2)->comment('价格');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
}
