<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    private WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * 天気アプリのトップページ
     */
    public function index()
    {
        $regions = $this->weatherService->getAllRegions();
        return view('weather.index', compact('regions'));
    }

    /**
     * 指定地域の天気情報を取得して表示
     */
    public function show(Request $request)
    {
        $request->validate([
            'region_id' => 'required|integer|exists:regions,id'
        ]);

        $regions = $this->weatherService->getAllRegions();
        $weatherData = $this->weatherService->getWeatherForRegion($request->region_id);
        
        if (!$weatherData) {
            return back()->withErrors(['error' => '天気情報の取得に失敗しました。']);
        }

        return view('weather.index', compact('regions', 'weatherData'));
    }

    /**
     * 現在地の天気情報を取得
     */
    public function currentLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180'
        ]);

        $locationWeatherData = $this->weatherService->getWeatherByLocation($request->lat, $request->lon);
        
        if (!$locationWeatherData) {
            return response()->json(['error' => '天気情報の取得に失敗しました。'], 500);
        }

        return response()->json([
            'success' => true,
            'weatherData' => $locationWeatherData
        ]);
    }
}
