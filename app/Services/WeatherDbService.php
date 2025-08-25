<?php

namespace App\Services;

use App\Models\Region;
use App\Models\WeatherRecord;
use App\Models\WeatherLocation;
use App\Models\WeatherHourlyForecast;
use Carbon\Carbon;

// データベース操作専用サービス
class WeatherDbService
{
    // 全地域を取得（名前順）
    public function getAllRegions()
    {
        return Region::orderBy('name')->get();
    }

    // 地域IDから地域情報を取得
    public function getRegionById(int $regionId): ?Region
    {
        return Region::find($regionId);
    }

    // 地域天気のキャッシュ確認（地域×日付×言語で検索）
    public function findRegionWeatherCache(int $regionId, string $locale): ?WeatherRecord
    {
        return WeatherRecord::where('region_id', $regionId)
            ->where('date', Carbon::today())
            ->where('locale', $locale)
            ->first();
    }

    // 地域天気をDBに保存（APIから取得したデータ）
    public function saveRegionWeather(array $weatherData, int $regionId, string $locale): WeatherRecord
    {
        return WeatherRecord::create([
            'region_id' => $regionId,
            'location_name' => $weatherData['name'] ?? '不明', // APIから取得した多言語地域名
            'weather' => $weatherData['weather'][0]['description'] ?? '不明',
            'icon' => $weatherData['weather'][0]['icon'] ?? null,
            'temperature' => $weatherData['main']['temp'],
            'feels_like' => $weatherData['main']['feels_like'],
            'temp_min' => $weatherData['main']['temp_min'],
            'temp_max' => $weatherData['main']['temp_max'],
            'pressure' => $weatherData['main']['pressure'] ?? null,
            'humidity' => $weatherData['main']['humidity'] ?? null,
            'visibility' => $weatherData['visibility'] ?? null,
            'wind_speed' => $weatherData['wind']['speed'] ?? null,
            'wind_deg' => $weatherData['wind']['deg'] ?? null,
            'clouds' => $weatherData['clouds']['all'] ?? null,
            'sunrise' => $weatherData['sys']['sunrise'] ?? null,
            'sunset' => $weatherData['sys']['sunset'] ?? null,
            'country' => $weatherData['sys']['country'] ?? null,
            'api_dt' => $weatherData['dt'] ?? null,
            'date' => Carbon::today(),
            'locale' => $locale, // 言語別キャッシュのキー
        ]);
    }

    // 現在地天気のキャッシュ確認（座標範囲×日付×言語で検索）
    public function findLocationWeatherCache(float $lat, float $lon, string $locale): ?WeatherLocation
    {
        $latRounded = round($lat, 1); // 0.1度単位で丸める（約11km四方）
        $lonRounded = round($lon, 1);

        return WeatherLocation::where('lat_rounded', $latRounded)
            ->where('lon_rounded', $lonRounded)
            ->where('date', Carbon::today())
            ->where('locale', $locale)
            ->first();
    }

    // 現在地天気をDBに保存（APIレスポンス全体をJSONで保存）
    public function saveLocationWeather(array $weatherData, float $lat, float $lon, string $locale): WeatherLocation
    {
        $latRounded = round($lat, 1);
        $lonRounded = round($lon, 1);

        return WeatherLocation::create([
            'lat_rounded' => $latRounded,
            'lon_rounded' => $lonRounded,
            'date' => Carbon::today(),
            'location_name' => $weatherData['name'] ?? '不明',
            'country' => $weatherData['sys']['country'] ?? null,
            'weather_data' => $weatherData, // API全データをJSON保存
            'locale' => $locale,
        ]);
    }

    // 地域用の時間別予報キャッシュ確認
    public function findRegionHourlyCache(int $regionId, string $locale): ?array
    {
        $forecasts = WeatherHourlyForecast::forRegion($regionId, $locale)
            ->futureOnly()
            ->take(24) // 今後24時間分のみ取得
            ->get();
        
        if ($forecasts->isEmpty()) {
            return null;
        }

        // データ構造を統一（モデルのアクセサーを活用）
        return $forecasts->map(function ($forecast) {
            return $forecast->frontend_data;  // アクセサーを使用してコード簡略化
        })->toArray();
    }

    // 現在地用の時間別予報キャッシュ確認
    public function findLocationHourlyCache(float $lat, float $lon, string $locale): ?array
    {
        $latRounded = round($lat, 1);
        $lonRounded = round($lon, 1);

        $forecasts = WeatherHourlyForecast::forLocation($latRounded, $lonRounded, $locale)
            ->futureOnly()
            ->take(24) // 今後24時間分のみ取得
            ->get();
        
        if ($forecasts->isEmpty()) {
            return null;
        }

        // データ構造を統一（モデルのアクセサーを活用）
        return $forecasts->map(function ($forecast) {
            return $forecast->frontend_data;  // アクセサーを使用してコード簡略化
        })->toArray();
    }

    // 地域用の時間別予報をDBに一括保存
    public function saveRegionHourlyForecasts(array $hourlyData, int $regionId, string $locale): void
    {
        // 既存データを削除（同一地域・同一言語の当日データ）
        WeatherHourlyForecast::where('region_id', $regionId)
            ->where('locale', $locale)
            ->where('date', Carbon::today())
            ->delete();

        // 48時間分のデータを保存
        foreach ($hourlyData as $hourly) {
            WeatherHourlyForecast::create([
                'region_id' => $regionId,
                'lat_rounded' => null,
                'lon_rounded' => null,
                'forecast_time' => Carbon::createFromTimestamp($hourly['dt']),
                'temperature' => $hourly['temp'],
                'weather' => $hourly['weather'][0]['description'] ?? '不明',
                'icon' => $hourly['weather'][0]['icon'] ?? null,
                'pop' => $hourly['pop'] ?? 0, // 降水確率
                'date' => Carbon::today(),
                'locale' => $locale,
            ]);
        }
    }

    // 現在地用の時間別予報をDBに一括保存
    public function saveLocationHourlyForecasts(array $hourlyData, float $lat, float $lon, string $locale): void
    {
        $latRounded = round($lat, 1);
        $lonRounded = round($lon, 1);

        // 既存データを削除（同一座標・同一言語の当日データ）
        WeatherHourlyForecast::where('lat_rounded', $latRounded)
            ->where('lon_rounded', $lonRounded)
            ->where('locale', $locale)
            ->where('date', Carbon::today())
            ->delete();

        // 48時間分のデータを保存
        foreach ($hourlyData as $hourly) {
            WeatherHourlyForecast::create([
                'region_id' => null,
                'lat_rounded' => $latRounded,
                'lon_rounded' => $lonRounded,
                'forecast_time' => Carbon::createFromTimestamp($hourly['dt']),
                'temperature' => $hourly['temp'],
                'weather' => $hourly['weather'][0]['description'] ?? '不明',
                'icon' => $hourly['weather'][0]['icon'] ?? null,
                'pop' => $hourly['pop'] ?? 0, // 降水確率
                'date' => Carbon::today(),
                'locale' => $locale,
            ]);
        }
    }
}