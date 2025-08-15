<?php

namespace App\Services;

use App\Models\Region;
use App\Models\WeatherRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openweather.api_key');
        $this->baseUrl = config('services.openweather.base_url');
    }

    /**
     * 指定地域の天気情報を取得（キャッシュ機能付き）
     */
    public function getWeatherForRegion(int $regionId): ?WeatherRecord
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
            return $existingRecord;
        }

        // APIから取得
        $weatherData = $this->fetchWeatherFromApi($region);
        if (!$weatherData) {
            return null;
        }

        // DBに保存
        $weatherRecord = WeatherRecord::create([
            'region_id' => $regionId,
            'weather' => $weatherData['weather'],
            'temperature' => $weatherData['temperature'],
            'date' => $today,
        ]);

        Log::info("天気データをAPIから取得しDBに保存", ['region' => $region->name, 'date' => $today]);
        return $weatherRecord;
    }

    /**
     * OpenWeatherMap APIから天気データを取得
     */
    private function fetchWeatherFromApi(Region $region): ?array
    {
        try {
            $response = Http::get($this->baseUrl . '/weather', [
                'lat' => $region->lat,
                'lon' => $region->lon,
                'appid' => $this->apiKey,
                'units' => 'metric', // 摂氏温度
                'lang' => 'ja', // 日本語
            ]);

            if ($response->failed()) {
                Log::error("OpenWeatherMap API エラー", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            return [
                'weather' => $data['weather'][0]['description'] ?? '不明',
                'temperature' => round($data['main']['temp'], 1),
            ];
        } catch (\Exception $e) {
            Log::error("天気API取得エラー", [
                'region' => $region->name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 全地域一覧を取得
     */
    public function getAllRegions()
    {
        return Region::orderBy('name')->get();
    }
}