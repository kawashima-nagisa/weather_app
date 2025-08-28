<?php

namespace App\Services;

use App\Services\TourismRecommendationService;
use Carbon\Carbon;

// メインビジネスロジック - キャッシュ判定とAPI/DB協調処理 + グルメ推奨統合
class WeatherService
{
    public function __construct(
        private WeatherApiService $apiService, // API通信担当
        private WeatherDbService $dbService,   // DB操作担当
        private TourismRecommendationService $tourismService // グルメ推奨担当
    ) {}

    // 地域一覧取得
    public function getAllRegions()
    {
        return $this->dbService->getAllRegions();
    }

    // 地域天気取得（キャッシュ優先 → API取得 → DB保存 + グルメ推奨）
    public function getRegionWeather(int $regionId): ?array
    {
        $locale = app()->getLocale();
        
        // キャッシュ確認：同一地域×同一日×同一言語
        $cachedWeather = $this->dbService->findRegionWeatherCache($regionId, $locale);
        
        if ($cachedWeather) {
            // キャッシュヒット：DBから取得
            $region = $this->dbService->getRegionById($regionId);
            $result = [
                'weather' => $cachedWeather,
                'region' => $region,
                'is_from_cache' => true,
                'retrieved_at' => $cachedWeather->created_at,
            ];
        } else {
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

            $result = [
                'weather' => $newWeatherRecord,
                'region' => $region,
                'is_from_cache' => false,
                'retrieved_at' => Carbon::now(),
            ];
        }

        // グルメ推奨を追加（天気情報取得のついで）
        $result['restaurant_recommendations'] = $this->tourismService->getRestaurantRecommendationsByRegion(
            $result['weather']->weather,
            $result['region']->lat,   // 緯度を追加
            $result['region']->lon,   // 経度を追加
            $result['region']->name,
            $locale
        );

        return $result;
    }

    // 現在地天気取得（座標範囲キャッシュ優先 → API取得 → DB保存 + グルメ推奨）
    public function getLocationWeather(float $lat, float $lon): ?array
    {
        $locale = app()->getLocale();
        
        // キャッシュ確認：座標範囲×同一日×同一言語（0.1度単位丸め）
        $cachedWeather = $this->dbService->findLocationWeatherCache($lat, $lon, $locale);
        
        if ($cachedWeather) {
            // キャッシュヒット：DBから取得
            $result = [
                'location_weather' => $cachedWeather,
                'is_from_cache' => true,
                'retrieved_at' => $cachedWeather->created_at,
            ];
        } else {
            // キャッシュミス：APIから新規取得
            $weatherData = $this->apiService->fetchWeatherByCoordinates($lat, $lon);
            if (!$weatherData) {
                return null;
            }

            // DB保存してキャッシュ作成
            $newLocationRecord = $this->dbService->saveLocationWeather($weatherData, $lat, $lon, $locale);

            $result = [
                'location_weather' => $newLocationRecord,
                'is_from_cache' => false,
                'retrieved_at' => Carbon::now(),
            ];
        }

        // グルメ推奨を追加（現在地天気取得のついで）
        $weatherInfo = $result['location_weather']->weather_data;
        $currentWeather = $weatherInfo['weather'][0]['description'] ?? '';
        
        $result['restaurant_recommendations'] = $this->tourismService->getRestaurantRecommendationsByLocation(
            $currentWeather,
            $lat,
            $lon,
            $locale
        );

        return $result;
    }

    // 地域の時間別予報取得（キャッシュ優先 → API取得 → DB保存）
    public function getRegionHourlyForecast(int $regionId): ?array
    {
        $locale = app()->getLocale();
        
        // キャッシュ確認：同一地域×同一言語の今後24時間分
        $cachedForecasts = $this->dbService->findRegionHourlyCache($regionId, $locale);
        
        if ($cachedForecasts) {
            // キャッシュヒット：DBから取得
            return $cachedForecasts;
        }

        // キャッシュミス：APIから新規取得
        $region = $this->dbService->getRegionById($regionId);
        if (!$region) {
            return null;
        }

        $forecastData = $this->apiService->fetchHourlyForecast($region->lat, $region->lon);
        if (!$forecastData || !isset($forecastData['hourly'])) {
            return null;
        }

        // DB保存してキャッシュ作成
        $this->dbService->saveRegionHourlyForecasts($forecastData['hourly'], $regionId, $locale);

        // 保存後の今後24時間分を再取得
        return $this->dbService->findRegionHourlyCache($regionId, $locale);
    }

    // 現在地の時間別予報取得（座標範囲キャッシュ優先 → API取得 → DB保存）
    public function getLocationHourlyForecast(float $lat, float $lon): ?array
    {
        $locale = app()->getLocale();
        
        // キャッシュ確認：座標範囲×同一言語の今後24時間分（0.1度単位丸め）
        $cachedForecasts = $this->dbService->findLocationHourlyCache($lat, $lon, $locale);
        
        if ($cachedForecasts) {
            // キャッシュヒット：DBから取得
            return $cachedForecasts;
        }

        // キャッシュミス：APIから新規取得
        $forecastData = $this->apiService->fetchHourlyForecast($lat, $lon);
        if (!$forecastData || !isset($forecastData['hourly'])) {
            return null;
        }

        // DB保存してキャッシュ作成
        $this->dbService->saveLocationHourlyForecasts($forecastData['hourly'], $lat, $lon, $locale);

        // 保存後の今後24時間分を再取得
        return $this->dbService->findLocationHourlyCache($lat, $lon, $locale);
    }
}