<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddVoiceDurationToMessageTable extends Migration
{
    public function up(): void
    {
        Schema::table('message', function (Blueprint $table) {
            $table->integer('voice_duration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message', function (Blueprint $table) {
            $table->dropColumn('voice_duration');
        });
    }
}
