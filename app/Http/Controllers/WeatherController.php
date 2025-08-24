<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;

// 天気アプリのHTTPリクエスト処理専用コントローラ
class WeatherController extends Controller
{
    public function __construct(
        private WeatherService $weatherService, // ビジネスロジック担当
    ) {}

    // トップページ表示（地域選択フォーム）
    public function index()
    {
        $regions = $this->weatherService->getAllRegions();
        return view('weather.index', compact('regions'));
    }

    // 地域選択による天気表示
    public function show(Request $request)
    {
        // フォーム入力検証
        $request->validate([
            'region_id' => 'required|integer|exists:regions,id'
        ]);

        $regions = $this->weatherService->getAllRegions();
        $weatherData = $this->weatherService->getRegionWeather($request->region_id);
        
        if (!$weatherData) {
            return back()->withErrors(['error' => __('app.weather_fetch_error')]);
        }

        return view('weather.index', compact('regions', 'weatherData'));
    }

    // 現在地による天気表示
    public function currentLocation(Request $request)
    {
        // JavaScript位置情報APIから取得した座標を使用
        $locationWeatherData = $this->weatherService->getLocationWeather(
            $request->lat,
            $request->lon
        );
        
        $regions = $this->weatherService->getAllRegions();
        
        if (!$locationWeatherData) {
            return back()->withErrors(['error' => __('app.weather_fetch_error')]);
        }

        return view('weather.index', [
            'regions' => $regions,
            'weatherData' => $locationWeatherData
        ]);
    }
}
