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
        Schema::table('weather_records', function (Blueprint $table) {
            $table->string('location_name')->nullable()->after('region_id')->comment('API取得の地域名（多言語対応）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weather_records', function (Blueprint $table) {
            $table->dropColumn('location_name');
        });
    }
};
