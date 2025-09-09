<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * トイレ推奨サービス
 * 
 * 天気に応じたトイレ推奨ロジックを提供
 * - 雨天時：屋内施設優先（コンビニ、スーパー、レストラン）
 * - 晴天時：公共トイレ、ガソリンスタンドも含む全施設対象
 * - 段階的検索：500m → 1km → 2kmで範囲拡張
 */
class ToiletRecommendationService
{
    private GooglePlacesApiService $placesService;

    public function __construct(GooglePlacesApiService $placesService)
    {
        $this->placesService = $placesService;
    }

    /**
     * 天気に応じたトイレ施設推奨機能のメイン処理
     * 
     * 天気状況に応じて推奨する施設タイプを変更：
     * - 雨・雪：屋内施設優先（コンビニ、スーパー、レストラン）
     * - 晴れ・曇り：全施設対象（公共トイレ、ガソリンスタンドも含む）
     * 
     * @param float $lat 緯度
     * @param float $lng 経度
     * @param string $weatherCondition 天気状況（Clear, Clouds, Rain, Snow等）
     * @param string $language 言語設定（ja, en, zh）
     * @return array 推奨トイレ施設情報
     */
    public function getRecommendedToiletsByWeather(
        float $lat,
        float $lng,
        string $weatherCondition,
        string $language = 'ja'
    ): array {
        // 天気に応じた推奨施設タイプを決定
        $facilityTypes = $this->getFacilityTypesByWeather($weatherCondition);
        
        Log::info('Toilet recommendation started', [
            'weather' => $weatherCondition,
            'facility_types' => $facilityTypes,
            'lat' => $lat,
            'lng' => $lng
        ]);

        // 段階的検索：500m → 1km → 2km
        $searchRadiuses = [500, 1000, 2000];
        $allRecommendations = [];

        foreach ($searchRadiuses as $radius) {
            $recommendations = $this->searchToiletFacilitiesWithPriority(
                $lat, 
                $lng, 
                $facilityTypes, 
                $radius, 
                $language
            );

            if (!empty($recommendations['prioritized'])) {
                $allRecommendations = $recommendations;
                $allRecommendations['search_radius'] = $radius;
                break; // 結果が見つかったら検索終了
            }
        }

        // 検索結果がない場合は最大範囲で全施設検索
        if (empty($allRecommendations)) {
            $allRecommendations = $this->searchAllTypesAsFallback($lat, $lng, 3000, $language);
            $allRecommendations['search_radius'] = 3000;
            $allRecommendations['is_fallback'] = true;
        }

        $allRecommendations['weather_condition'] = $weatherCondition;
        $allRecommendations['recommendation_reason'] = $this->getRecommendationReason($weatherCondition, $language);

        return $allRecommendations;
    }

    /**
     * 天気状況に応じた推奨施設タイプを取得
     * 
     * @param string $weatherCondition OpenWeatherMap天気コード
     * @return array 推奨施設タイプ配列（優先度順）
     */
    private function getFacilityTypesByWeather(string $weatherCondition): array
    {
        // 雨・雪などの悪天候：屋内施設優先
        if (in_array($weatherCondition, ['Rain', 'Drizzle', 'Snow', 'Sleet', 'Thunderstorm'])) {
            return [
                'convenience_store', // 最優先：コンビニ（24時間営業多数）
                'supermarket',       // 高優先：スーパー（大型トイレ）
                'restaurant',        // 中優先：レストラン（店舗による）
            ];
        }

        // 晴れ・曇りなどの良天候：全施設対象
        return [
            'convenience_store', // 最優先：コンビニ
            'public_toilet',     // 高優先：公共トイレ
            'supermarket',       // 中優先：スーパー
            'gas_station',       // 中優先：ガソリンスタンド
            'restaurant',        // 低優先：レストラン
        ];
    }

    /**
     * 優先度に基づく段階的施設検索
     * 
     * @param float $lat 緯度
     * @param float $lng 経度
     * @param array $facilityTypes 施設タイプ配列（優先度順）
     * @param int $radius 検索半径
     * @param string $language 言語
     * @return array 優先度別検索結果
     */
    private function searchToiletFacilitiesWithPriority(
        float $lat,
        float $lng,
        array $facilityTypes,
        int $radius,
        string $language
    ): array {
        $results = [
            'prioritized' => [],
            'by_type' => [],
            'total_count' => 0
        ];

        foreach ($facilityTypes as $index => $type) {
            $facilities = $this->placesService->searchNearbyToilets($lat, $lng, $radius, $type, $language);
            
            if ($facilities) {
                // 優先度情報を各施設に追加
                $facilitiesWithPriority = array_map(function ($facility) use ($index) {
                    $facility['priority'] = $index + 1; // 1が最高優先度
                    return $facility;
                }, $facilities);

                $results['by_type'][$type] = $facilitiesWithPriority;
                $results['prioritized'] = array_merge($results['prioritized'], $facilitiesWithPriority);
                $results['total_count'] += count($facilitiesWithPriority);
            }

            // API レート制限対策
            usleep(200000);
        }

        // 距離ソートは WeatherService で距離計算後に実行

        return $results;
    }

    /**
     * フォールバック：全施設タイプで検索
     * 
     * @param float $lat 緯度
     * @param float $lng 経度
     * @param int $radius 検索半径
     * @param string $language 言語
     * @return array 全タイプ検索結果
     */
    private function searchAllTypesAsFallback(float $lat, float $lng, int $radius, string $language): array
    {
        $allTypes = ['convenience_store', 'public_toilet', 'supermarket', 'restaurant', 'gas_station'];
        
        return $this->searchToiletFacilitiesWithPriority($lat, $lng, $allTypes, $radius, $language);
    }

    /**
     * 天気に応じた推奨理由を取得
     * 
     * @param string $weatherCondition 天気状況
     * @param string $language 言語
     * @return string 推奨理由
     */
    private function getRecommendationReason(string $weatherCondition, string $language): string
    {
        $reasons = [
            'ja' => [
                'rainy' => '雨天のため屋内施設を優先して表示しています',
                'snowy' => '雪のため屋内施設を優先して表示しています',
                'stormy' => '悪天候のため屋内施設を優先して表示しています',
                'clear' => '晴天のため公共トイレも含めて表示しています',
                'default' => '現在の天気に適したトイレ施設を表示しています'
            ],
            'en' => [
                'rainy' => 'Indoor facilities are prioritized due to rainy weather',
                'snowy' => 'Indoor facilities are prioritized due to snowy weather',
                'stormy' => 'Indoor facilities are prioritized due to bad weather',
                'clear' => 'Public toilets are also included due to clear weather',
                'default' => 'Showing toilet facilities suitable for current weather'
            ],
            'zh' => [
                'rainy' => '因雨天优先显示室内设施',
                'snowy' => '因雪天优先显示室内设施',
                'stormy' => '因恶劣天气优先显示室内设施',
                'clear' => '因晴天也包含公共厕所',
                'default' => '显示适合当前天气的厕所设施'
            ]
        ];

        $langReasons = $reasons[$language] ?? $reasons['ja'];

        if (in_array($weatherCondition, ['Rain', 'Drizzle'])) {
            return $langReasons['rainy'];
        } elseif ($weatherCondition === 'Snow') {
            return $langReasons['snowy'];
        } elseif (in_array($weatherCondition, ['Thunderstorm', 'Squall', 'Tornado'])) {
            return $langReasons['stormy'];
        } elseif ($weatherCondition === 'Clear') {
            return $langReasons['clear'];
        }

        return $langReasons['default'];
    }

    /**
     * トイレ施設情報に距離計算を追加
     * 
     * @param array $facilities 施設配列
     * @param float $userLat ユーザー緯度
     * @param float $userLng ユーザー経度
     * @return array 距離情報付き施設配列
     */
    public function addDistanceToFacilities(array $facilities, float $userLat, float $userLng): array
    {
        foreach ($facilities as &$facility) {
            if (isset($facility['geometry']['lat']) && isset($facility['geometry']['lng'])) {
                $facility['distance_meters'] = $this->calculateDistance(
                    $userLat,
                    $userLng,
                    $facility['geometry']['lat'],
                    $facility['geometry']['lng']
                );
                $facility['distance_display'] = $this->formatDistance($facility['distance_meters']);
            }
        }

        return $facilities;
    }

    /**
     * 2点間の距離を計算（ハバーサイン公式）
     * 
     * @param float $lat1 地点1の緯度
     * @param float $lng1 地点1の経度
     * @param float $lat2 地点2の緯度
     * @param float $lng2 地点2の経度
     * @return float 距離（メートル）
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // 地球の半径（メートル）

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * 距離を表示用文字列に変換
     * 
     * @param float $meters 距離（メートル）
     * @return string 表示用距離文字列
     */
    private function formatDistance(float $meters): string
    {
        if ($meters >= 1000) {
            return number_format($meters / 1000, 1) . 'km';
        }

        return number_format($meters) . 'm';
    }

    /**
     * 営業時間に基づく施設フィルタリング
     * 
     * @param array $facilities 施設配列
     * @param bool $openNowOnly 営業中のみに絞り込むかどうか
     * @return array フィルタリング済み施設配列
     */
    public function filterByOpeningHours(array $facilities, bool $openNowOnly = false): array
    {
        if (!$openNowOnly) {
            return $facilities;
        }

        return array_filter($facilities, function ($facility) {
            return $facility['opening_hours']['open_now'] ?? false;
        });
    }
}