<?php

namespace App\UseCases;

use App\Models\WeatherLocation;
use App\Services\WeatherApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LocationWeatherUsecase
{
    private WeatherApiService $weatherApiService;

    public function __construct(WeatherApiService $weatherApiService)
    {
        $this->weatherApiService = $weatherApiService;
    }

    /**
     * 現在地の天気情報を取得（座標範囲キャッシュ機能付き）
     */
    
    public function getWeatherByLocation(float $lat, float $lon): ?array
    {
        $latRounded = round($lat, 1);
        $lonRounded = round($lon, 1);
        $today = Carbon::today();

        // 既存のレコードをチェック
        $existingRecord = WeatherLocation::where('lat_rounded', $latRounded)
            ->where('lon_rounded', $lonRounded)
            ->where('date', $today)
            ->first();

        if ($existingRecord) {
            Log::info("現在地天気データをDBから取得", [
                'lat_rounded' => $latRounded, 
                'lon_rounded' => $lonRounded, 
                'date' => $today
            ]);
            return [
                'record' => $existingRecord,
                'is_from_cache' => true,
                'cached_at' => $existingRecord->created_at,
            ];
        }

        // APIから取得
        $weatherData = $this->weatherApiService->fetchWeatherData($lat, $lon);
        if (!$weatherData) {
            return null;
        }

        // DBに保存
        $weatherLocation = WeatherLocation::create([
            'lat_rounded' => $latRounded,
            'lon_rounded' => $lonRounded,
            'date' => $today,
            'location_name' => $weatherData['name'],
            'country' => $weatherData['country'],
            'weather_data' => $weatherData,
        ]);

        Log::info("現在地天気データをAPIから取得しDBに保存", [
            'lat_rounded' => $latRounded,
            'lon_rounded' => $lonRounded,
            'location_name' => $weatherData['name'],
            'date' => $today
        ]);
        
        return [
            'record' => $weatherLocation,
            'is_from_cache' => false,
            'fetched_at' => now(),
        ];
    }
}