<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherApiService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openweather.api_key');
        $this->baseUrl = config('services.openweather.base_url');
    }

    /**
     * OpenWeatherMap APIから天気データを取得（
     */
    public function fetchWeatherData(float $lat, float $lon, ?string $regionName = null): ?array
    {
        try {
            $response = Http::get($this->baseUrl . '/weather', [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->apiKey,
                'units' => 'metric', // 摂氏温度
                'lang' => 'ja', // 日本語
            ]);

            if ($response->failed()) {
                Log::error("OpenWeatherMap API エラー", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'lat' => $lat,
                    'lon' => $lon,
                    'region' => $regionName,
                ]);
                return null;
            }

            $data = $response->json();

            return [
                'name' => $data['name'] ?? '不明',
                'country' => $data['sys']['country'] ?? null,
                'weather' => $data['weather'][0]['description'] ?? '不明',
                'icon' => $data['weather'][0]['icon'] ?? null,
                'temperature' => round($data['main']['temp'], 1),
                'feels_like' => round($data['main']['feels_like'], 1),
                'temp_min' => round($data['main']['temp_min'], 1),
                'temp_max' => round($data['main']['temp_max'], 1),
                'pressure' => $data['main']['pressure'] ?? null,
                'humidity' => $data['main']['humidity'] ?? null,
                'visibility' => $data['visibility'] ?? null,
                'wind_speed' => $data['wind']['speed'] ?? null,
                'wind_deg' => $data['wind']['deg'] ?? null,
                'clouds' => $data['clouds']['all'] ?? null,
                'sunrise' => isset($data['sys']['sunrise']) ? $data['sys']['sunrise'] : null,
                'sunset' => isset($data['sys']['sunset']) ? $data['sys']['sunset'] : null,
                'api_dt' => $data['dt'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("天気API取得エラー", [
                'lat' => $lat,
                'lon' => $lon,
                'region' => $regionName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}