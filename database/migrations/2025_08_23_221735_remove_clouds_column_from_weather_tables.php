<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // weather_recordsテーブルからcloudsカラムを削除
        Schema::table('weather_records', function (Blueprint $table) {
            $table->dropColumn('clouds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // cloudsカラムを復元
        Schema::table('weather_records', function (Blueprint $table) {
            $table->integer('clouds')->nullable()->after('wind_deg');
        });
    }
};
