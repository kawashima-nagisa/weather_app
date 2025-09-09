<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Places API通信専用サービス
 * 
 * 現在地周辺のトイレ情報を取得するためのサービス
 * - 公共トイレ、コンビニ、スーパー、レストラン、ガソリンスタンドを検索対象
 * - 効率的なAPI利用のための検索範囲制御
 * - 必要最小限の施設情報のみ抽出
 */
class GooglePlacesApiService
{
    // Google Places API設定
    private string $apiKey;
    private string $baseUrl;
    
    public function __construct()
    {
        $this->apiKey = config('services.google_places.api_key');
        $this->baseUrl = config('services.google_places.base_url');
    }

    /**
     * 現在地周辺のトイレがある施設を検索
     * 
     * 検索対象施設：
     * - 公共トイレ（public_toilet）
     * - コンビニエンスストア（convenience_store）
     * - スーパーマーケット（supermarket）
     * - レストラン（restaurant）
     * - ガソリンスタンド（gas_station）
     * 
     * @param float $lat 検索の中心となる緯度
     * @param float $lng 検索の中心となる経度
     * @param int $radius 検索半径（メートル、最大50000）
     * @param string $type 施設タイプ（上記5種類のいずれか）
     * @param string $language 結果の言語（ja, en, zh）
     * @return array|null 成功時：施設配列、失敗時：null
     */
    public function searchNearbyToilets(
        float $lat,
        float $lng,
        int $radius = 1000,
        string $type = 'convenience_store',
        string $language = 'ja'
    ): ?array {
        try {
            // Google Places API Nearby Search のパラメーター
            $params = [
                'location' => $lat . ',' . $lng,
                'radius' => min($radius, 50000), // Google Places API の最大値
                'type' => $type,
                'language' => $this->mapLanguageCode($language),
                'key' => $this->apiKey
            ];

            // Google Places API Nearby Search を呼び出し
            $response = Http::timeout(10)
                ->get($this->baseUrl . '/maps/api/place/nearbysearch/json', $params);

            if ($response->successful()) {
                $data = $response->json();
                
                // API レスポンスの状態チェック
                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    // 必要最小限の施設情報のみ抽出
                    $facilities = $this->extractMinimalFacilityData($data['results'], $type);
                    
                    Log::info('Google Places API success', [
                        'type' => $type,
                        'count' => count($facilities),
                        'lat' => $lat,
                        'lng' => $lng,
                        'radius' => $radius
                    ]);
                    
                    return $facilities;
                }
                
                // ZERO_RESULTS や REQUEST_DENIED など
                Log::warning('Google Places API no results', [
                    'status' => $data['status'],
                    'type' => $type,
                    'lat' => $lat,
                    'lng' => $lng
                ]);
                
                return null;
            }

            Log::error('Google Places API HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'type' => $type,
                'lat' => $lat,
                'lng' => $lng
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Google Places API exception', [
                'message' => $e->getMessage(),
                'type' => $type,
                'lat' => $lat,
                'lng' => $lng
            ]);
            
            return null;
        }
    }

    /**
     * Places APIレスポンスから必要最小限の施設情報のみ抽出
     * 
     * @param array $places Google Places APIの施設配列
     * @param string $type 施設タイプ
     * @return array 最小限の施設情報配列
     */
    private function extractMinimalFacilityData(array $places, string $type): array
    {
        $facilities = [];
        
        foreach ($places as $place) {
            $facilities[] = [
                'place_id' => $place['place_id'] ?? '',
                'name' => $place['name'] ?? '不明',
                'type' => $type,
                'type_display' => $this->getTypeDisplayName($type),
                'vicinity' => $place['vicinity'] ?? '',
                'rating' => $place['rating'] ?? null,
                'user_ratings_total' => $place['user_ratings_total'] ?? 0,
                'price_level' => $place['price_level'] ?? null,
                'opening_hours' => [
                    'open_now' => $place['opening_hours']['open_now'] ?? null,
                ],
                'geometry' => [
                    'lat' => $place['geometry']['location']['lat'] ?? null,
                    'lng' => $place['geometry']['location']['lng'] ?? null,
                ],
                'photos' => !empty($place['photos']) ? [
                    'photo_reference' => $place['photos'][0]['photo_reference'] ?? '',
                    'width' => $place['photos'][0]['width'] ?? 0,
                    'height' => $place['photos'][0]['height'] ?? 0,
                ] : null,
            ];
        }
        
        return $facilities;
    }

    /**
     * 施設タイプの表示名を取得
     * 
     * @param string $type Google Places APIの施設タイプ
     * @return string 表示用の施設名
     */
    private function getTypeDisplayName(string $type): string
    {
        $typeNames = [
            'public_toilet' => '公共トイレ',
            'convenience_store' => 'コンビニ',
            'supermarket' => 'スーパー',
            'restaurant' => 'レストラン',
            'gas_station' => 'ガソリンスタンド',
        ];

        return $typeNames[$type] ?? $type;
    }

    /**
     * Laravel言語コードをGoogle Places API用にマッピング
     * 
     * @param string $locale Laravel言語コード（ja, en, zh）
     * @return string Google Places API用言語コード
     */
    private function mapLanguageCode(string $locale): string
    {
        $languageMap = [
            'ja' => 'ja',
            'en' => 'en',
            'zh' => 'zh-CN',
        ];

        return $languageMap[$locale] ?? 'ja';
    }

    /**
     * 複数の施設タイプで段階的に検索
     * 
     * 検索優先度：
     * 1. 公共トイレ
     * 2. コンビニ
     * 3. スーパー、レストラン、ガソリンスタンド
     * 
     * @param float $lat 緯度
     * @param float $lng 経度
     * @param int $radius 検索半径
     * @param string $language 言語
     * @return array 全施設タイプの検索結果
     */
    public function searchAllToiletFacilities(
        float $lat,
        float $lng,
        int $radius = 1000,
        string $language = 'ja'
    ): array {
        $allFacilities = [];
        
        // 検索対象の施設タイプ（優先度順）
        $facilityTypes = [
            'public_toilet',    // 最優先：公共トイレ
            'convenience_store', // 高優先：コンビニ
            'supermarket',      // 中優先：スーパー
            'restaurant',       // 低優先：レストラン
            'gas_station',      // 低優先：ガソリンスタンド
        ];

        foreach ($facilityTypes as $type) {
            $facilities = $this->searchNearbyToilets($lat, $lng, $radius, $type, $language);
            
            if ($facilities) {
                $allFacilities[$type] = $facilities;
            } else {
                $allFacilities[$type] = [];
            }
            
            // API レート制限対策：200ms間隔
            usleep(200000);
        }

        return $allFacilities;
    }

    /**
     * Google Places API設定確認
     * 
     * @return bool APIキーが設定されているかどうか
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->baseUrl);
    }

    /**
     * Google Places API利用時の制限情報を取得
     * 
     * @return array 制限やコスト情報
     */
    public function getApiLimitInfo(): array
    {
        return [
            'nearby_search_cost' => '$0.032 per request',
            'monthly_free_quota' => '$200 credit',
            'max_radius' => 50000, // メートル
            'max_results_per_request' => 20,
            'rate_limit' => 'No specific limit',
            'required_attribution' => 'Google Places API attribution required'
        ];
    }
}