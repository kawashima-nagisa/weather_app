<?php

namespace App\Services;

use App\Services\TourismRecommendationService;
use App\Services\ToiletRecommendationService;
use Carbon\Carbon;

// メインビジネスロジック - キャッシュ判定とAPI/DB協調処理 + グルメ・トイレ推奨統合
class WeatherService
{
    public function __construct(
        private WeatherApiService $apiService, // API通信担当
        private WeatherDbService $dbService,   // DB操作担当
        private TourismRecommendationService $tourismService, // グルメ推奨担当
        private ToiletRecommendationService $toiletService   // トイレ推奨担当
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

        // 注意：地域選択時はトイレ推奨機能は提供しない（現在地のみ）

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

        // トイレ推奨を追加（現在地の天気連動）
        $weatherCondition = $weatherInfo['weather'][0]['main'] ?? 'Clear';
        $result['toilet_recommendations'] = $this->toiletService->getRecommendedToiletsByWeather(
            $lat,
            $lon,
            $weatherCondition,
            $locale
        );

        // 距離計算を追加（現在地基準）
        if (!empty($result['toilet_recommendations']['prioritized'])) {
            $result['toilet_recommendations']['prioritized'] = $this->toiletService->addDistanceToFacilities(
                $result['toilet_recommendations']['prioritized'],
                $lat,
                $lon
            );
        }
        
        // タブ表示用のby_type配列にも距離計算とソートを適用
        if (!empty($result['toilet_recommendations']['by_type'])) {
            foreach ($result['toilet_recommendations']['by_type'] as $type => $facilities) {
                // 距離計算を追加
                $facilitiesWithDistance = $this->toiletService->addDistanceToFacilities($facilities, $lat, $lon);
                
                // 距離順にソート（近い順）
                usort($facilitiesWithDistance, function ($a, $b) {
                    return ($a['distance_meters'] ?? PHP_FLOAT_MAX) <=> ($b['distance_meters'] ?? PHP_FLOAT_MAX);
                });
                
                $result['toilet_recommendations']['by_type'][$type] = $facilitiesWithDistance;
            }
        }

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

    /**
     * 天気レコードから天気状況を抽出
     * 
     * @param mixed $weatherRecord 天気レコード
     * @return string OpenWeatherMap天気コード（Clear, Rain, Snow等）
     */
    private function extractWeatherCondition($weatherRecord): string
    {
        // WeatherRecordモデルの場合
        if (is_object($weatherRecord) && isset($weatherRecord->weather_data)) {
            return $weatherRecord->weather_data['weather'][0]['main'] ?? 'Clear';
        }
        
        // weatherカラムの場合（地域天気）
        if (is_object($weatherRecord) && isset($weatherRecord->weather)) {
            // weatherカラムから天気状況を推測
            $weather = strtolower($weatherRecord->weather);
            if (str_contains($weather, '雨') || str_contains($weather, 'rain')) {
                return 'Rain';
            } elseif (str_contains($weather, '雪') || str_contains($weather, 'snow')) {
                return 'Snow';
            } elseif (str_contains($weather, '雷') || str_contains($weather, 'thunder')) {
                return 'Thunderstorm';
            } elseif (str_contains($weather, '曇') || str_contains($weather, 'cloud')) {
                return 'Clouds';
            }
        }
        
        // デフォルトは晴れ
        return 'Clear';
    }
}