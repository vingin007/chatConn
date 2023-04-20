<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class RemoveTranscribedSubtitleStoreNameAndTranslatedSubtitleVideoStoreNameFromOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trans_orders', function (Blueprint $table) {
            $table->dropColumn('transcribed_subtitle_store_name');
            $table->dropColumn('translated_subtitle_video_store_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trans_orders', function (Blueprint $table) {
            $table->string('transcribed_subtitle_store_name')->nullable();
            $table->string('translated_subtitle_video_store_name')->nullable();
        });
    }
}
