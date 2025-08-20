<?php

namespace App\UseCases;

use App\Models\Region;
use App\Models\WeatherRecord;
use App\Services\WeatherApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RegionWeatherUsecase
{
    private WeatherApiService $weatherApiService;

    public function __construct(WeatherApiService $weatherApiService)
    {
        $this->weatherApiService = $weatherApiService;
    }

    /**
     * 指定地域の天気情報を取得（キャッシュ機能付き）
     */
    public function getWeatherForRegion(int $regionId): ?array
    {
        $region = Region::find($regionId);
        if (!$region) {
            return null;
        }

        $today = Carbon::today();

        // 既存のレコードをチェック
        $existingRecord = WeatherRecord::where('region_id', $regionId)
            ->where('date', $today)
            ->first();

        if ($existingRecord) {
            Log::info("天気データをDBから取得", ['region' => $region->name, 'date' => $today]);
            return [
                'record' => $existingRecord,
                'is_from_cache' => true,
                'cached_at' => $existingRecord->created_at,
            ];
        }

        // APIから取得
        $weatherData = $this->weatherApiService->fetchWeatherData($region->lat, $region->lon, $region->name);
        if (!$weatherData) {
            return null;
        }

        // DBに保存
        $weatherRecord = WeatherRecord::create([
            'region_id' => $regionId,
            'weather' => $weatherData['weather'],
            'icon' => $weatherData['icon'],
            'temperature' => $weatherData['temperature'],
            'feels_like' => $weatherData['feels_like'],
            'temp_min' => $weatherData['temp_min'],
            'temp_max' => $weatherData['temp_max'],
            'pressure' => $weatherData['pressure'],
            'humidity' => $weatherData['humidity'],
            'visibility' => $weatherData['visibility'],
            'wind_speed' => $weatherData['wind_speed'],
            'wind_deg' => $weatherData['wind_deg'],
            'clouds' => $weatherData['clouds'],
            'sunrise' => $weatherData['sunrise'],
            'sunset' => $weatherData['sunset'],
            'country' => $weatherData['country'],
            'api_dt' => $weatherData['api_dt'],
            'date' => $today,
        ]);

        Log::info("天気データをAPIから取得しDBに保存", ['region' => $region->name, 'date' => $today]);
        return [
            'record' => $weatherRecord,
            'is_from_cache' => false,
            'fetched_at' => now(),
        ];
    }

    /**
     * 全地域一覧を取得
     */
    public function getAllRegions()
    {
        return Region::orderBy('name')->get();
    }
}