<?php

namespace App\Services;

use App\Services\HotpepperApiService;
use Illuminate\Support\Facades\Log;

// 天気連動グルメ推奨サービス（Phase 1: グルメのみ）
class TourismRecommendationService
{
    private HotpepperApiService $hotpepperService;

    public function __construct(HotpepperApiService $hotpepperService)
    {
        $this->hotpepperService = $hotpepperService;
    }

    /**
     * 天気に基づいて地域のグルメ推奨を取得
     * 
     * @param string $weather 天気概要（例: "晴れ", "雨", "曇り"）
     * @param float $lat 緯度
     * @param float $lng 経度
     * @param string $locale 言語コード（ja/en/zh）
     * @return array 推奨グルメ情報
     */
    public function getRestaurantRecommendationsByLocation(
        string $weather, 
        float $lat, 
        float $lng, 
        string $locale = 'ja'
    ): array {
        // HotPepper API が設定されていない場合は空の結果を返す
        if (!$this->hotpepperService->isConfigured()) {
            Log::warning('HotPepper API not configured');
            return $this->getEmptyRecommendation($locale);
        }

        try {
            // 天気に基づいてジャンルを決定
            $recommendedGenre = $this->getRecommendedGenreByWeather($weather);
            
            // HotPepper APIでレストラン検索（範囲を段階的に拡張）
            $restaurants = $this->searchRestaurantsWithFallback(
                $lat, 
                $lng, 
                $recommendedGenre['primary']
            );

            // 推奨理由とクレジット情報を含む結果を作成
            return [
                'has_recommendations' => !empty($restaurants),
                'weather_based_reason' => $this->getWeatherRecommendationReason($weather, $locale),
                'recommended_genre' => $recommendedGenre,
                'restaurants' => $restaurants ?? [],
                'credit' => $this->hotpepperService->getCreditInfo(),
                'search_params' => [
                    'lat' => $lat,
                    'lng' => $lng,
                    'weather' => $weather,
                    'genre' => $recommendedGenre['primary']
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Restaurant recommendation failed', [
                'weather' => $weather,
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyRecommendation($locale);
        }
    }

    /**
     * 天気に基づいて地域名でのグルメ推奨を取得（regionsテーブル連携用）
     * 
     * @param string $weather 天気概要
     * @param float $lat 地域の緯度
     * @param float $lng 地域の経度
     * @param string $regionName 地域名（例: "東京"）
     * @param string $locale 言語コード
     * @return array 推奨グルメ情報
     */
    public function getRestaurantRecommendationsByRegion(
        string $weather,
        float $lat,
        float $lng,
        string $regionName,
        string $locale = 'ja'
    ): array {
        // HotPepper API が設定されていない場合は空の結果を返す
        if (!$this->hotpepperService->isConfigured()) {
            Log::warning('HotPepper API not configured');
            return $this->getEmptyRecommendation($locale);
        }

        try {
            // 天気に基づいてジャンルを決定
            $recommendedGenre = $this->getRecommendedGenreByWeather($weather);
            
            // HotPepper APIで座標ベース検索（範囲を段階的に拡張）
            $restaurants = $this->searchRestaurantsWithFallback(
                $lat,
                $lng,
                $recommendedGenre['primary']
            );

            return [
                'has_recommendations' => !empty($restaurants),
                'weather_based_reason' => $this->getWeatherRecommendationReason($weather, $locale),
                'recommended_genre' => $recommendedGenre,
                'restaurants' => $restaurants ?? [],
                'credit' => $this->hotpepperService->getCreditInfo(),
                'search_params' => [
                    'region' => $regionName,
                    'lat' => $lat,
                    'lng' => $lng,
                    'weather' => $weather,
                    'genre' => $recommendedGenre['primary']
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Restaurant recommendation by region failed', [
                'weather' => $weather,
                'region' => $regionName,
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyRecommendation($locale);
        }
    }

    /**
     * 天気に基づいて推奨ジャンルを取得
     * 
     * @param string $weather 天気概要
     * @return array ジャンル情報
     */
    private function getRecommendedGenreByWeather(string $weather): array
    {
        $mapping = config('weather_recommendations.genre_mapping');
        
        // 天気キーワードでマッチング
        foreach ($mapping as $weatherType => $genreInfo) {
            if ($weatherType === 'default') continue;
            
            foreach ($genreInfo['keywords'] as $keyword) {
                if (stripos($weather, $keyword) !== false) {
                    return [
                        'weather_type' => $weatherType,
                        'primary' => $genreInfo['primary'],
                        'secondary' => $genreInfo['secondary'],
                        'reason' => $genreInfo['reason']
                    ];
                }
            }
        }

        // マッチしない場合はデフォルトを返す
        return [
            'weather_type' => 'default',
            'primary' => $mapping['default']['primary'],
            'secondary' => $mapping['default']['secondary'], 
            'reason' => $mapping['default']['reason']
        ];
    }

    /**
     * 天気に基づく推奨理由を取得
     * 
     * @param string $weather 天気概要
     * @param string $locale 言語コード
     * @return string 推奨理由
     */
    private function getWeatherRecommendationReason(string $weather, string $locale): string
    {
        $genre = $this->getRecommendedGenreByWeather($weather);
        return $genre['reason'][$locale] ?? $genre['reason']['ja'] ?? '';
    }


    /**
     * 段階的検索範囲拡張でレストランを検索
     * 
     * 1000m圏内で結果が少ない地域では3000m圏内まで拡張して検索
     * 金沢、熊本、北九州市などの地方都市の店舗密度に対応
     * 
     * @param float $lat 緯度
     * @param float $lng 経度
     * @param string $genreCode ジャンルコード
     * @return array|null レストラン配列
     */
    private function searchRestaurantsWithFallback(float $lat, float $lng, string $genreCode): ?array
    {
        // 第1段階: 1000m圏内で検索（range=3）
        $restaurants = $this->hotpepperService->searchRestaurantsByLocation($lat, $lng, 3, 5, $genreCode);
        
        if ($restaurants && count($restaurants) > 0) {
            Log::info('Restaurant search success at 1000m range', [
                'lat' => $lat,
                'lng' => $lng,
                'genre' => $genreCode,
                'count' => count($restaurants)
            ]);
            return $restaurants;
        }

        // 第2段階: 3000m圏内に拡張検索（range=5）
        Log::info('Expanding search to 3000m range', [
            'lat' => $lat,
            'lng' => $lng,
            'genre' => $genreCode
        ]);

        $restaurants = $this->hotpepperService->searchRestaurantsByLocation($lat, $lng, 5, 5, $genreCode);
        
        if ($restaurants && count($restaurants) > 0) {
            Log::info('Restaurant search success at 3000m range', [
                'lat' => $lat,
                'lng' => $lng,
                'genre' => $genreCode,
                'count' => count($restaurants)
            ]);
            return $restaurants;
        }

        Log::warning('No restaurants found even with fallback search', [
            'lat' => $lat,
            'lng' => $lng,
            'genre' => $genreCode
        ]);

        return null;
    }

    /**
     * 空の推奨結果を取得
     * 
     * @param string $locale 言語コード
     * @return array 空の推奨結果
     */
    private function getEmptyRecommendation(string $locale): array
    {
        $messages = [
            'ja' => 'グルメ情報を取得できませんでした',
            'en' => 'Restaurant information is not available',
            'zh' => '无法获取餐厅信息'
        ];

        return [
            'has_recommendations' => false,
            'weather_based_reason' => $messages[$locale] ?? $messages['ja'],
            'recommended_genre' => null,
            'restaurants' => [],
            'credit' => $this->hotpepperService->getCreditInfo(),
            'search_params' => null
        ];
    }
}