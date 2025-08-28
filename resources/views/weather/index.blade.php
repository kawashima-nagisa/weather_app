@extends('layouts.app')

@section('title', __('app.app_name'))

@section('content')
<div class="bg-white rounded-lg shadow-xl p-6">
    <!-- 現在地セクション -->
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-green-800 mb-3">📍 {{ __('app.current_location') }}</h3>
        <form id="currentLocationForm" action="{{ route('weather.current-location') }}" method="POST">
            @csrf
            <input type="hidden" name="lat" id="latInput">
            <input type="hidden" name="lon" id="lonInput">
            <button type="button" onclick="
                if (!navigator.geolocation) {
                    alert('{{ __('app.location_not_supported') }}');
                    return;
                }
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latInput').value = position.coords.latitude;
                    document.getElementById('lonInput').value = position.coords.longitude;
                    document.getElementById('currentLocationForm').submit();
                }, function(error) {
                    let message = '{{ __('app.location_error') }}';
                    if (error.code === error.PERMISSION_DENIED) {
                        message = '{{ __('app.location_denied') }}';
                    }
                    alert(message);
                });
            " class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
                📍 {{ __('app.get_current_location') }}
            </button>
        </form>
    </div>

    <!-- 地域選択セクション -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-blue-800 mb-3">🗾 {{ __('app.region_selection') }}</h3>
        <form action="{{ route('weather.show') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="region_id" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('app.select_region') }}
                </label>
                <select name="region_id" id="region_id" required
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">{{ __('app.select_region_placeholder') }}</option>
                    @foreach($regions as $region)
                        <option value="{{ $region->id }}" 
                            {{ isset($weatherData) && isset($weatherData['region']) && $weatherData['region']->id == $region->id ? 'selected' : '' }}>
                            {{ $region->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" 
                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
                🌤️ {{ __('app.get_region_weather') }}
            </button>
        </form>
    </div>


    @isset($weatherData)
    @php
        // 地域天気と現在地天気の両方に対応する統一変数
        $isRegionWeather = isset($weatherData['weather']) && isset($weatherData['region']);
        $isLocationWeather = isset($weatherData['location_weather']);
        
        if ($isRegionWeather) {
            $weather = $weatherData['weather'];
            $locationName = $weather->location_name ?? $weatherData['region']->name;
            $date = $weather->date;
        } elseif ($isLocationWeather) {
            $weather = $weatherData['location_weather'];
            $locationName = $weather->location_name;
            $date = $weather->date;
        } else {
            $weather = null;
            $locationName = 'Unknown';
            $date = now();
        }
        
        $locale = app()->getLocale();
        $dateFormat = $locale === 'en' ? 'F j, Y' : ($locale === 'zh' ? 'Y年m月d日' : 'Y年m月d日');
    @endphp
    
    @if($weather)
    <div class="border-t pt-6">
        <div class="bg-gradient-to-r {{ $isLocationWeather ? 'from-green-50 to-emerald-50' : 'from-blue-50 to-indigo-50' }} rounded-lg p-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    📍 {{ $locationName }}
                </h2>
                <div class="text-sm text-gray-600 mb-4">
                    {{ $date->format($dateFormat) }}{{ __('app.weather_for_date') }}
                </div>
                
                <!-- メイン天気情報 -->
                <div class="grid grid-cols-2 gap-4 max-w-md mx-auto mb-6">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="text-2xl mb-1">
                            @if($isLocationWeather)
                                @if(isset($weather->weather_data['weather'][0]['icon']))
                                    <img src="https://openweathermap.org/img/wn/{{ $weather->weather_data['weather'][0]['icon'] }}@2x.png" 
                                         alt="{{ $weather->weather_data['weather'][0]['description'] ?? '天気' }}" class="w-12 h-12 mx-auto">
                                @else
                                    🌤️
                                @endif
                            @else
                                @if($weather->icon)
                                    <img src="https://openweathermap.org/img/wn/{{ $weather->icon }}@2x.png" 
                                         alt="{{ $weather->weather }}" class="w-12 h-12 mx-auto">
                                @else
                                    🌤️
                                @endif
                            @endif
                        </div>
                        <div class="text-lg font-semibold text-gray-800">
                            {{ $isLocationWeather ? ($weather->weather_data['weather'][0]['description'] ?? '不明') : $weather->weather }}
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="text-2xl mb-1">🌡️</div>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ $isLocationWeather ? round($weather->weather_data['main']['temp'] ?? 0, 1) : $weather->temperature }}°C
                        </div>
                        @php $feelsLike = $isLocationWeather ? ($weather->weather_data['main']['feels_like'] ?? null) : $weather->feelsLike; @endphp
                        @if($feelsLike)
                            <div class="text-sm text-gray-600">
                                {{ __('app.feels_like') }} {{ $feelsLike }}°C
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 詳細天気情報 -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 max-w-4xl mx-auto mb-6">
                    @php 
                        $tempMin = $isLocationWeather ? ($weather->weather_data['main']['temp_min'] ?? null) : $weather->tempMin;
                        $tempMax = $isLocationWeather ? ($weather->weather_data['main']['temp_max'] ?? null) : $weather->tempMax;
                    @endphp
                    @if($tempMin && $tempMax)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">📊</div>
                        <div class="text-sm font-semibold text-gray-800">{{ __('app.high_low') }}</div>
                        <div class="text-lg font-bold text-red-500">{{ $tempMax }}°C</div>
                        <div class="text-lg font-bold text-blue-500">{{ $tempMin }}°C</div>
                    </div>
                    @endif

                    @php $humidity = $isLocationWeather ? ($weather->weather_data['main']['humidity'] ?? null) : $weather->humidity; @endphp
                    @if($humidity)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">💧</div>
                        <div class="text-sm font-semibold text-gray-800">{{ __('app.humidity') }}</div>
                        <div class="text-lg font-bold text-blue-600">{{ $humidity }}%</div>
                    </div>
                    @endif

                    @php $pressure = $isLocationWeather ? ($weather->weather_data['main']['pressure'] ?? null) : $weather->pressure; @endphp
                    @if($pressure)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">⏲️</div>
                        <div class="text-sm font-semibold text-gray-800">{{ __('app.pressure') }}</div>
                        <div class="text-lg font-bold text-purple-600">{{ $pressure }}hPa</div>
                    </div>
                    @endif

                    @php 
                        $windSpeed = $isLocationWeather ? ($weather->weather_data['wind']['speed'] ?? null) : $weather->windSpeed;
                        $windDeg = $isLocationWeather ? ($weather->weather_data['wind']['deg'] ?? null) : $weather->windDeg;
                    @endphp
                    @if($windSpeed)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">💨</div>
                        <div class="text-sm font-semibold text-gray-800">{{ __('app.wind_speed') }}</div>
                        <div class="text-lg font-bold text-green-600">{{ $windSpeed }}m/s</div>
                        @if($windDeg)
                            <div class="text-xs text-gray-500">{{ $windDeg }}°</div>
                        @endif
                    </div>
                    @endif

                    @php $visibility = $isLocationWeather ? ($weather->weather_data['visibility'] ?? null) : $weather->visibility; @endphp
                    @if($visibility)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">👁️</div>
                        <div class="text-sm font-semibold text-gray-800">{{ __('app.visibility') }}</div>
                        <div class="text-lg font-bold text-indigo-600">{{ $isLocationWeather ? round(($weather->weather_data['visibility'] ?? 0) / 1000, 1) : round($visibility / 1000, 1) }}km</div>
                    </div>
                    @endif

                    @php 
                        $sunrise = $isLocationWeather ? ($weather->weather_data['sys']['sunrise'] ?? null) : $weather->sunrise;
                        $sunset = $isLocationWeather ? ($weather->weather_data['sys']['sunset'] ?? null) : $weather->sunset;
                    @endphp
                    @if($sunrise && $sunset)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">🌅</div>
                        <div class="text-sm font-semibold text-gray-800">{{ __('app.sunrise_sunset') }}</div>
                        <div class="text-sm font-bold text-orange-500">{{ \Carbon\Carbon::createFromTimestamp($sunrise)->setTimezone('Asia/Tokyo')->format('H:i') }}</div>
                        <div class="text-sm font-bold text-purple-500">{{ \Carbon\Carbon::createFromTimestamp($sunset)->setTimezone('Asia/Tokyo')->format('H:i') }}</div>
                    </div>
                    @endif
                </div>

                <!-- 時間別予報セクション -->
                @if(isset($weatherData['hourly_forecast']) && count($weatherData['hourly_forecast']) > 0)
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">
                        ⏰ {{ __('app.hourly_forecast') }}
                    </h3>
                    
                    <!-- 横スクロール可能なカード -->
                    <div class="overflow-x-auto scrollbar-hide">
                        <div class="flex space-x-3 pb-2" style="width: max-content;">
                            @foreach($weatherData['hourly_forecast'] as $hourly)
                            <div class="bg-white rounded-lg p-3 shadow-sm flex-shrink-0 w-24 text-center">
                                <!-- 時刻 -->
                                <div class="text-xs text-gray-600 mb-1">
                                    {{ \Carbon\Carbon::createFromTimestamp($hourly['forecast_time'])->setTimezone('Asia/Tokyo')->format('H:i') }}
                                </div>
                                
                                <!-- 天気アイコン -->
                                <div class="mb-1">
                                    @if(isset($hourly['icon']) && $hourly['icon'])
                                        <img src="https://openweathermap.org/img/wn/{{ $hourly['icon'] }}.png" 
                                             alt="{{ $hourly['weather'] ?? '天気' }}" class="w-8 h-8 mx-auto">
                                    @else
                                        <div class="text-lg">🌤️</div>
                                    @endif
                                </div>
                                
                                <!-- 気温 -->
                                <div class="text-sm font-bold text-blue-600 mb-1">
                                    {{ round($hourly['temperature'], 1) }}°
                                </div>
                                
                                <!-- 降水確率 -->
                                @if(isset($hourly['pop']) && $hourly['pop'] > 0)
                                <div class="text-xs text-blue-500">
                                    💧{{ round($hourly['pop'] * 100) }}%
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-4 text-xs text-gray-500">
                    @if($weatherData['is_from_cache'])
                        {{ __('app.from_cache') }} ({{ $weatherData['retrieved_at']->setTimezone('Asia/Tokyo')->format('H:i') }}{{ __('app.cached_at') }})
                    @else
                        {{ __('app.from_api') }} ({{ $weatherData['retrieved_at']->setTimezone('Asia/Tokyo')->format('H:i') }})
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    @endisset

    {{-- 天気連動グルメ推奨セクション --}}
    @isset($weatherData['restaurant_recommendations'])
    <div class="mt-6 border-t pt-6">
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg p-6 border border-yellow-200">
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                🍽️ {{ __('app.weather_based_restaurants') }}
            </h3>
            
            @if($weatherData['restaurant_recommendations']['has_recommendations'])
                {{-- 天気に基づく推奨理由 --}}
                <div class="bg-white rounded-lg p-4 mb-4 border-l-4 border-yellow-400">
                    <p class="text-gray-700 text-sm">
                        {{ $weatherData['restaurant_recommendations']['weather_based_reason'] }}
                    </p>
                </div>
                
                {{-- レストラン一覧 --}}
                <div class="grid gap-3">
                    @foreach($weatherData['restaurant_recommendations']['restaurants'] as $restaurant)
                    @if(!empty($restaurant['urls']['pc']))
                        {{-- クリック可能なレストランカード --}}
                        <a href="{{ $restaurant['urls']['pc'] }}" target="_blank" class="block bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md hover:border-orange-300 transition-all transform hover:scale-[1.02] cursor-pointer">
                            <div class="flex items-start space-x-3">
                                {{-- 店舗画像 --}}
                                @if(!empty($restaurant['photo']['pc']))
                                    <div class="flex-shrink-0">
                                        <img src="{{ $restaurant['photo']['pc'] }}" 
                                             alt="{{ $restaurant['name'] }}" 
                                             class="w-16 h-16 rounded-lg object-cover">
                                    </div>
                                @endif
                                
                                {{-- 店舗情報 --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-800 mb-2">{{ $restaurant['name'] }}</h4>
                                    <p class="text-sm text-gray-600 mb-1">🏷️ {{ $restaurant['genre'] }}</p>
                                    
                                    @if($restaurant['address'])
                                        <p class="text-xs text-gray-500 mb-1">🏠 {{ Str::limit($restaurant['address'], 40) }}</p>
                                    @endif
                                    
                                    @if($restaurant['station_name'])
                                        <p class="text-xs text-gray-500 mb-1">🚉 {{ $restaurant['station_name'] }}駅</p>
                                    @endif
                                    
                                    @if($restaurant['open'])
                                        <p class="text-xs text-gray-500 mb-1">🕒 {{ Str::limit($restaurant['open'], 30) }}</p>
                                    @endif
                                    
                                    @if($restaurant['budget'])
                                        <p class="text-sm text-gray-600 mb-1">💰 {{ $restaurant['budget'] }}</p>
                                    @endif
                                    
                                    @if($restaurant['access'])
                                        <p class="text-xs text-gray-500 mb-1">🚃 {{ Str::limit($restaurant['access'], 40) }}</p>
                                    @endif
                                    
                                </div>
                                
                                {{-- 詳細ボタン --}}
                                <div class="flex-shrink-0">
                                    <div class="bg-orange-500 text-white text-xs px-3 py-2 rounded-lg">
                                        {{ __('app.view_details') }}
                                    </div>
                                </div>
                            </div>
                        </a>
                    @else
                        {{-- URLが無い場合はクリック不可 --}}
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                            <div class="flex items-start space-x-3">
                                {{-- 店舗画像 --}}
                                @if(!empty($restaurant['photo']['pc']))
                                    <div class="flex-shrink-0">
                                        <img src="{{ $restaurant['photo']['pc'] }}" 
                                             alt="{{ $restaurant['name'] }}" 
                                             class="w-16 h-16 rounded-lg object-cover">
                                    </div>
                                @endif
                                
                                {{-- 店舗情報 --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-800 mb-2">{{ $restaurant['name'] }}</h4>
                                    <p class="text-sm text-gray-600 mb-1">🏷️ {{ $restaurant['genre'] }}</p>
                                    
                                    @if($restaurant['address'])
                                        <p class="text-xs text-gray-500 mb-1">🏠 {{ Str::limit($restaurant['address'], 40) }}</p>
                                    @endif
                                    
                                    @if($restaurant['station_name'])
                                        <p class="text-xs text-gray-500 mb-1">🚉 {{ $restaurant['station_name'] }}駅</p>
                                    @endif
                                    
                                    @if($restaurant['open'])
                                        <p class="text-xs text-gray-500 mb-1">🕒 {{ Str::limit($restaurant['open'], 30) }}</p>
                                    @endif
                                    
                                    @if($restaurant['budget'])
                                        <p class="text-sm text-gray-600 mb-1">💰 {{ $restaurant['budget'] }}</p>
                                    @endif
                                    
                                    @if($restaurant['access'])
                                        <p class="text-xs text-gray-500 mb-1">🚃 {{ Str::limit($restaurant['access'], 40) }}</p>
                                    @endif
                                    
                                </div>
                            </div>
                        </div>
                    @endif
                    @endforeach
                </div>
            @else
                {{-- グルメ情報が取得できない場合 --}}
                <div class="text-center py-4">
                    <p class="text-gray-600">{{ $weatherData['restaurant_recommendations']['weather_based_reason'] }}</p>
                </div>
            @endif
            
            {{-- HotPepper クレジット表示（ガイドライン準拠） --}}
            <div class="mt-4 pt-4 border-t border-yellow-300">
                <div class="flex items-center justify-center text-xs text-gray-500">
                    <span class="mr-2">{{ $weatherData['restaurant_recommendations']['credit']['text'] }}</span>
                    <a href="{{ $weatherData['restaurant_recommendations']['credit']['link_url'] }}" target="_blank">
                        <img src="{{ $weatherData['restaurant_recommendations']['credit']['logo_url'] }}" alt="ホットペッパーグルメ" class="h-4">
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endisset
</div>

@isset($weatherData)
<div class="mt-6 text-center">
    <a href="{{ route('weather.index') }}" 
        class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
        🔄 {{ __('app.check_other_region') }}
    </a>
</div>
@endisset

@endsection