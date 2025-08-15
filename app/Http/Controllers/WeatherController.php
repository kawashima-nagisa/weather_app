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
        $weatherRecord = $this->weatherService->getWeatherForRegion($request->region_id);
        
        if (!$weatherRecord) {
            return back()->withErrors(['error' => '天気情報の取得に失敗しました。']);
        }

        return view('weather.index', compact('regions', 'weatherRecord'));
    }
}
