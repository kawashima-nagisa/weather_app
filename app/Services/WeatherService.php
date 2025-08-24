<?php

namespace App\Services;

use Carbon\Carbon;

// メインビジネスロジック - キャッシュ判定とAPI/DB協調処理
class WeatherService
{
    public function __construct(
        private WeatherApiService $apiService, // API通信担当
        private WeatherDbService $dbService   // DB操作担当
    ) {}

    // 地域一覧取得
    public function getAllRegions()
    {
        return $this->dbService->getAllRegions();
    }

    // 地域天気取得（キャッシュ優先 → API取得 → DB保存）
    public function getRegionWeather(int $regionId): ?array
    {
        $locale = app()->getLocale();
        
        // キャッシュ確認：同一地域×同一日×同一言語
        $cachedWeather = $this->dbService->findRegionWeatherCache($regionId, $locale);
        
        if ($cachedWeather) {
            // キャッシュヒット：DBから取得
            $region = $this->dbService->getRegionById($regionId);
            return [
                'weather' => $cachedWeather,
                'region' => $region,
                'is_from_cache' => true,
                'retrieved_at' => $cachedWeather->created_at,
            ];
        }

        // キャッシュミス：APIから新規取得
        $region = $this->dbService->getRegionById($regionId);
        if (!$region) {
            return null;
        }

        $weatherData = $this->apiService->fetchWeatherByCoordinates($region->lat, $region->lon);
        if (!$weatherData) {
            return null;
        }

        // DB保存してキャッシュ作成
        $newWeatherRecord = $this->dbService->saveRegionWeather($weatherData, $regionId, $locale);

        return [
            'weather' => $newWeatherRecord,
            'region' => $region,
            'is_from_cache' => false,
            'retrieved_at' => Carbon::now(),
        ];
    }

    // 現在地天気取得（座標範囲キャッシュ優先 → API取得 → DB保存）
    public function getLocationWeather(float $lat, float $lon): ?array
    {
        $locale = app()->getLocale();
        
        // キャッシュ確認：座標範囲×同一日×同一言語（0.1度単位丸め）
        $cachedWeather = $this->dbService->findLocationWeatherCache($lat, $lon, $locale);
        
        if ($cachedWeather) {
            // キャッシュヒット：DBから取得
            return [
                'location_weather' => $cachedWeather,
                'is_from_cache' => true,
                'retrieved_at' => $cachedWeather->created_at,
            ];
        }

        // キャッシュミス：APIから新規取得
        $weatherData = $this->apiService->fetchWeatherByCoordinates($lat, $lon);
        if (!$weatherData) {
            return null;
        }

        // DB保存してキャッシュ作成
        $newLocationRecord = $this->dbService->saveLocationWeather($weatherData, $lat, $lon, $locale);

        return [
            'location_weather' => $newLocationRecord,
            'is_from_cache' => false,
            'retrieved_at' => Carbon::now(),
        ];
    }
}