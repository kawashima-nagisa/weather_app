@extends('layouts.app')

@section('title', '天気アプリ')

@section('content')
<div class="bg-white rounded-lg shadow-xl p-6">
    <form action="{{ route('weather.show') }}" method="POST" class="mb-6">
        @csrf
        <div class="mb-4">
            <label for="region_id" class="block text-sm font-medium text-gray-700 mb-2">
                地域を選択してください
            </label>
            <select name="region_id" id="region_id" required
                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">地域を選択...</option>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}" 
                        {{ isset($weatherData) && $weatherData['record']->region_id == $region->id ? 'selected' : '' }}>
                        {{ $region->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" 
            class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
            🌤️ 天気を調べる
        </button>
    </form>

    @isset($weatherData)
    <div class="border-t pt-6">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    📍 {{ $weatherData['record']->region->name }}
                </h2>
                <div class="text-sm text-gray-600 mb-4">
                    {{ $weatherData['record']->date->format('Y年m月d日') }}の天気
                </div>
                
                <!-- メイン天気情報 -->
                <div class="grid grid-cols-2 gap-4 max-w-md mx-auto mb-6">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="text-2xl mb-1">
                            @if($weatherData['record']->icon)
                                <img src="https://openweathermap.org/img/wn/{{ $weatherData['record']->icon }}@2x.png" 
                                     alt="{{ $weatherData['record']->weather }}" class="w-12 h-12 mx-auto">
                            @else
                                🌤️
                            @endif
                        </div>
                        <div class="text-lg font-semibold text-gray-800">
                            {{ $weatherData['record']->weather }}
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="text-2xl mb-1">🌡️</div>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ $weatherData['record']->temperature }}°C
                        </div>
                        @if($weatherData['record']->feels_like)
                            <div class="text-sm text-gray-600">
                                体感 {{ $weatherData['record']->feels_like }}°C
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 詳細天気情報 -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 max-w-4xl mx-auto mb-6">
                    @if($weatherData['record']->temp_min && $weatherData['record']->temp_max)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">📊</div>
                        <div class="text-sm font-semibold text-gray-800">最高/最低</div>
                        <div class="text-lg font-bold text-red-500">{{ $weatherData['record']->temp_max }}°C</div>
                        <div class="text-lg font-bold text-blue-500">{{ $weatherData['record']->temp_min }}°C</div>
                    </div>
                    @endif

                    @if($weatherData['record']->humidity)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">💧</div>
                        <div class="text-sm font-semibold text-gray-800">湿度</div>
                        <div class="text-lg font-bold text-blue-600">{{ $weatherData['record']->humidity }}%</div>
                    </div>
                    @endif

                    @if($weatherData['record']->pressure)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">⏲️</div>
                        <div class="text-sm font-semibold text-gray-800">気圧</div>
                        <div class="text-lg font-bold text-purple-600">{{ $weatherData['record']->pressure }}hPa</div>
                    </div>
                    @endif

                    @if($weatherData['record']->wind_speed)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">💨</div>
                        <div class="text-sm font-semibold text-gray-800">風速</div>
                        <div class="text-lg font-bold text-green-600">{{ $weatherData['record']->wind_speed }}m/s</div>
                        @if($weatherData['record']->wind_deg)
                            <div class="text-xs text-gray-500">{{ $weatherData['record']->wind_deg }}°</div>
                        @endif
                    </div>
                    @endif

                    @if($weatherData['record']->visibility)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">👁️</div>
                        <div class="text-sm font-semibold text-gray-800">視界</div>
                        <div class="text-lg font-bold text-indigo-600">{{ round($weatherData['record']->visibility / 1000, 1) }}km</div>
                    </div>
                    @endif

                    @if($weatherData['record']->clouds)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">☁️</div>
                        <div class="text-sm font-semibold text-gray-800">雲量</div>
                        <div class="text-lg font-bold text-gray-600">{{ $weatherData['record']->clouds }}%</div>
                    </div>
                    @endif

                    @if($weatherData['record']->sunrise && $weatherData['record']->sunset)
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-lg mb-1">🌅</div>
                        <div class="text-sm font-semibold text-gray-800">日の出/日の入り</div>
                        <div class="text-sm font-bold text-orange-500">{{ \Carbon\Carbon::createFromTimestamp($weatherData['record']->sunrise)->setTimezone('Asia/Tokyo')->format('H:i') }}</div>
                        <div class="text-sm font-bold text-purple-500">{{ \Carbon\Carbon::createFromTimestamp($weatherData['record']->sunset)->setTimezone('Asia/Tokyo')->format('H:i') }}</div>
                    </div>
                    @endif
                </div>

                <div class="mt-4 text-xs text-gray-500">
                    @if($weatherData['is_from_cache'])
                        キャッシュから取得 ({{ $weatherData['cached_at']->setTimezone('Asia/Tokyo')->format('H:i') }}に取得済み)
                    @else
                        APIから取得 ({{ $weatherData['fetched_at']->format('H:i') }})
                    @endif
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
        🔄 他の地域を調べる
    </a>
</div>
@endisset
@endsection