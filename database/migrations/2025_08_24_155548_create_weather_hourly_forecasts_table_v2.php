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
        // テーブルが既に存在する場合はスキップ
        if (!Schema::hasTable('weather_hourly_forecasts')) {
            Schema::create('weather_hourly_forecasts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('region_id')->nullable(); // 地域選択用（weather_records連携）
                $table->decimal('lat_rounded', 4, 1)->nullable(); // 現在地用（weather_locations連携）
                $table->decimal('lon_rounded', 4, 1)->nullable(); // 現在地用（weather_locations連携）
                $table->timestamp('forecast_time'); // 予報時刻（UTC）
                $table->float('temperature'); // 気温（摂氏）
                $table->string('weather'); // 天気概要
                $table->string('icon', 10)->nullable(); // 天気アイコンID
                $table->float('pop')->nullable(); // 降水確率（0-1）
                $table->date('date'); // キャッシュ用日付
                $table->string('locale', 2); // 言語コード（ja/en/zh）
                $table->timestamps();

                // ユニーク制約（地域選択用）
                $table->unique(['region_id', 'forecast_time', 'locale'], 'unique_region_hourly');
                
                // ユニーク制約（現在地用）
                $table->unique(['lat_rounded', 'lon_rounded', 'forecast_time', 'locale'], 'unique_location_hourly');

                // インデックス（パフォーマンス向上用）
                $table->index(['region_id', 'date', 'locale'], 'idx_region_date_locale');
                $table->index(['lat_rounded', 'lon_rounded', 'date', 'locale'], 'idx_location_date_locale');
                $table->index('forecast_time', 'idx_forecast_time');

                // 外部キー制約
                $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_hourly_forecasts');
    }
};
