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
            $table->string('icon')->nullable()->after('weather');
            $table->float('feels_like', 5, 2)->nullable()->after('temperature');
            $table->float('temp_min', 5, 2)->nullable()->after('feels_like');
            $table->float('temp_max', 5, 2)->nullable()->after('temp_min');
            $table->integer('pressure')->nullable()->after('temp_max');
            $table->integer('humidity')->nullable()->after('pressure');
            $table->integer('visibility')->nullable()->after('humidity');
            $table->float('wind_speed', 8, 2)->nullable()->after('visibility');
            $table->integer('wind_deg')->nullable()->after('wind_speed');
            $table->integer('clouds')->nullable()->after('wind_deg');
            $table->integer('sunrise')->nullable()->after('clouds');
            $table->integer('sunset')->nullable()->after('sunrise');
            $table->string('country', 2)->nullable()->after('sunset');
            $table->integer('api_dt')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weather_records', function (Blueprint $table) {
            $table->dropColumn([
                'icon', 'feels_like', 'temp_min', 'temp_max', 'pressure', 
                'humidity', 'visibility', 'wind_speed', 'wind_deg', 'clouds',
                'sunrise', 'sunset', 'country', 'api_dt'
            ]);
        });
    }
};
