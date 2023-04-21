<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddRealDurationToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trans_orders', function (Blueprint $table) {
            $table->unsignedInteger('real_duration')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trans_orders', function (Blueprint $table) {
            $table->dropColumn('real_duration');
        });
    }
}
