<?php

namespace App\Services;

use App\Models\Region;
use App\Models\WeatherRecord;
use App\Models\WeatherLocation;
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
    public function getWeatherForRegion(int $regionId): ?array
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
            return [
                'record' => $existingRecord,
                'is_from_cache' => true,
                'cached_at' => $existingRecord->created_at,
            ];
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
            'icon' => $weatherData['icon'],
            'temperature' => $weatherData['temperature'],
            'feels_like' => $weatherData['feels_like'],
            'temp_min' => $weatherData['temp_min'],
            'temp_max' => $weatherData['temp_max'],
            'pressure' => $weatherData['pressure'],
            'humidity' => $weatherData['humidity'],
            'visibility' => $weatherData['visibility'],
            'wind_speed' => $weatherData['wind_speed'],
            'wind_deg' => $weatherData['wind_deg'],
            'clouds' => $weatherData['clouds'],
            'sunrise' => $weatherData['sunrise'],
            'sunset' => $weatherData['sunset'],
            'country' => $weatherData['country'],
            'api_dt' => $weatherData['api_dt'],
            'date' => $today,
        ]);

        Log::info("天気データをAPIから取得しDBに保存", ['region' => $region->name, 'date' => $today]);
        return [
            'record' => $weatherRecord,
            'is_from_cache' => false,
            'fetched_at' => now(),
        ];
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
                'country' => $data['sys']['country'] ?? null,
                'api_dt' => $data['dt'] ?? null,
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

    /**
     * 現在地の天気情報を取得（座標範囲キャッシュ機能付き）
     */
    public function getWeatherByLocation(float $lat, float $lon): ?array
    {
        $latRounded = round($lat, 1);
        $lonRounded = round($lon, 1);
        $today = Carbon::today();

        // 既存のレコードをチェック
        $existingRecord = WeatherLocation::where('lat_rounded', $latRounded)
            ->where('lon_rounded', $lonRounded)
            ->where('date', $today)
            ->first();

        if ($existingRecord) {
            Log::info("現在地天気データをDBから取得", [
                'lat_rounded' => $latRounded, 
                'lon_rounded' => $lonRounded, 
                'date' => $today
            ]);
            return [
                'record' => $existingRecord,
                'is_from_cache' => true,
                'cached_at' => $existingRecord->created_at,
            ];
        }

        // APIから取得
        $weatherData = $this->fetchWeatherFromApiByCoords($lat, $lon);
        if (!$weatherData) {
            return null;
        }

        // DBに保存
        $weatherLocation = WeatherLocation::create([
            'lat_rounded' => $latRounded,
            'lon_rounded' => $lonRounded,
            'date' => $today,
            'location_name' => $weatherData['name'],
            'country' => $weatherData['country'],
            'weather_data' => $weatherData,
        ]);

        Log::info("現在地天気データをAPIから取得しDBに保存", [
            'lat_rounded' => $latRounded,
            'lon_rounded' => $lonRounded,
            'location_name' => $weatherData['name'],
            'date' => $today
        ]);
        
        return [
            'record' => $weatherLocation,
            'is_from_cache' => false,
            'fetched_at' => now(),
        ];
    }

    /**
     * OpenWeatherMap APIから座標指定で天気データを取得
     */
    private function fetchWeatherFromApiByCoords(float $lat, float $lon): ?array
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
                Log::error("OpenWeatherMap API エラー（座標指定）", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'lat' => $lat,
                    'lon' => $lon,
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
            Log::error("現在地天気API取得エラー", [
                'lat' => $lat,
                'lon' => $lon,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}