<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// HotPepper Gourmet API通信専用サービス
class HotpepperApiService
{
    // HotPepper API設定
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.hotpepper.api_key');
        $this->baseUrl = config('services.hotpepper.base_url');
    }

    /**
     *  天気連動グルメ推奨機能のメイン処理
     * 
     * 指定した座標（緯度経度）とジャンルコードでレストランを検索
     * HotPepper Gourmet API を呼び出して、最小限の店舗情報のみを返す
     * 
     * 【使用例】
     * - 晴れの東京駅周辺でお好み焼き屋を検索
     * - 雨の渋谷駅周辺でカフェを検索
     * 
     * @param float $lat 検索の中心となる緯度（例: 35.6812 = 東京駅）
     * @param float $lng 検索の中心となる経度（例: 139.7671 = 東京駅）
     * @param int $range 検索範囲 1=300m, 2=500m, 3=1000m（推奨）, 4=2000m, 5=3000m
     * @param int $count 取得するレストラン件数（1～100件、推奨は5件）
     * @param ?string $genre HotPepperジャンルコード（例: G017=お好み焼き、G004=和食）
     * @return array|null 成功時：レストラン配列、失敗時：null
     */
    public function searchRestaurantsByLocation(
        float $lat, 
        float $lng, 
        int $range = 3, 
        int $count = 5,
        ?string $genre = null
    ): ?array {
        try {
            // HotPepper API に送信するパラメーター組み立て
            $params = [
                'key' => $this->apiKey,     // APIキー
                'lat' => $lat,              // 緯度（座標）
                'lng' => $lng,              // 経度（座標）  
                'range' => $range,          // 検索範囲（1000m等）
                'count' => $count,          // 取得件数（5件等）
                'format' => 'json'          // JSON形式で取得
            ];

            // 天気によって決まったジャンルを追加
            if ($genre) {
                $params['genre'] = $genre;  // 例: G017（お好み焼き）
            }

            //  ここで「ジャンル + 座標でレストラン検索」が実行される！
            $response = Http::timeout(10)
                ->get($this->baseUrl . '/gourmet/v1/', $params);

            if ($response->successful()) {
                $data = $response->json();
                
                // HotPepper API特有のレスポンス構造をチェック
                if (isset($data['results']['shop'])) {
                    // 最小限の店舗情報のみ抽出
                    $restaurants = $this->extractMinimalRestaurantData($data['results']['shop']);
                    
                    Log::info('HotPepper API success', [
                        'count' => count($restaurants),
                        'lat' => $lat,
                        'lng' => $lng
                    ]);
                    return $restaurants;
                }
                
                Log::warning('HotPepper API no results', ['lat' => $lat, 'lng' => $lng]);
                return null;
            }

            Log::error('HotPepper API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'lat' => $lat,
                'lng' => $lng
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('HotPepper API exception', [
                'message' => $e->getMessage(),
                'lat' => $lat,
                'lng' => $lng
            ]);
            
            return null;
        }
    }

    /**
     * HotPepper APIレスポンスから最小限の店舗情報のみ抽出
     * 天気アプリの補足情報として必要最小限のデータのみ
     * 
     * @param array $shops HotPepper APIの店舗配列
     * @return array 最小限の店舗情報配列
     */
    private function extractMinimalRestaurantData(array $shops): array
    {
        $restaurants = [];
        
        foreach ($shops as $shop) {
            $restaurants[] = [
                'name' => $shop['name'] ?? '不明',              // 店舗名
                'genre' => $shop['genre']['name'] ?? '不明',    // ジャンル名
                'address' => $shop['address'] ?? '',           // 住所
                'station_name' => $shop['station_name'] ?? '', // 最寄り駅名
                'access' => $shop['mobile_access'] ?? $shop['pc_access'] ?? '', // アクセス情報
                'budget' => $shop['budget']['name'] ?? '',      // 予算帯
                'open' => $shop['open'] ?? '',                  // 営業時間
                'photo' => [
                    'mobile' => $shop['photo']['mobile']['s'] ?? '', // スマホ向け小サイズ画像
                    'pc' => $shop['photo']['pc']['s'] ?? '',         // PC向け小サイズ画像
                ],
                'urls' => [
                    'pc' => $shop['urls']['pc'] ?? '',          // PC向けURL
                ]
            ];
        }
        
        return $restaurants;
    }

    /**
     * HotPepper API の設定確認
     * 
     * @return bool APIキーが設定されているかどうか
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->baseUrl);
    }

    /**
     * HotPepper利用時に必要なクレジット情報を取得
     * ガイドライン準拠のため表示が必要
     * 
     * @return array クレジット表示用の情報
     */
    public function getCreditInfo(): array
    {
        return [
            'powered_by' => 'ホットペッパーグルメ',
            'logo_url' => 'http://webservice.recruit.co.jp/banner/hotpepper-s.gif',
            'link_url' => 'http://www.hotpepper.jp/',
            'text' => 'Powered by ホットペッパーグルメ Webサービス'
        ];
    }
}