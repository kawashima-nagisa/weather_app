<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

// OpenWeatherMap API通信専用サービス
class WeatherApiService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        // 設定ファイルからAPI情報取得
        $this->apiKey = config('services.openweather.api_key');
        $this->baseUrl = config('services.openweather.base_url');
    }

    // 座標から天気データを取得
    public function fetchWeatherByCoordinates(float $lat, float $lon): ?array
    {
        $locale = app()->getLocale();
        $apiLang = $this->getApiLanguage($locale);

        // OpenWeatherMap APIに多言語パラメータでリクエスト
        $response = Http::get($this->baseUrl . '/weather', [
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $this->apiKey,
            'units' => 'metric', // 摂氏温度
            'lang' => $apiLang,  // 言語設定
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    // Laravel locale → OpenWeatherMap API言語コード変換
    private function getApiLanguage(string $locale): string
    {
        return match ($locale) {
            'ja' => 'ja',       // 日本語
            'zh' => 'zh_cn',    // 中国語（簡体字）
            'en' => 'en',       // 英語
            default => 'en',
        };
    }
}