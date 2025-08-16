@extends('layouts.app')

@section('title', '天気アプリ')

@section('content')
<div class="bg-white rounded-lg shadow-xl p-6">
    <!-- 現在地セクション -->
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-green-800 mb-3">📍 現在地の天気</h3>
        <button type="button" id="currentLocationBtn" 
            class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
            📍 現在地の天気を取得
        </button>
    </div>

    <!-- ローディング表示 -->
    <div id="loadingIndicator" class="hidden mb-6">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
            <div class="animate-spin inline-block w-6 h-6 border-4 border-current border-t-transparent text-yellow-600 rounded-full mb-2"></div>
            <p class="text-yellow-800">現在地を取得中...</p>
        </div>
    </div>

    <!-- エラー表示 -->
    <div id="errorMessage" class="hidden mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800" id="errorText"></p>
        </div>
    </div>

    <!-- 地域選択セクション -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-blue-800 mb-3">🗾 地域を選択</h3>
        <form action="{{ route('weather.show') }}" method="POST">
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
                            {{ isset($weatherData) && isset($weatherData['record']->region_id) && $weatherData['record']->region_id == $region->id ? 'selected' : '' }}>
                            {{ $region->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" 
                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
                🌤️ 選択地域の天気を調べる
            </button>
        </form>
    </div>

    <!-- 天気表示エリア -->
    <div id="weatherDisplay" class="hidden border-t pt-6">
        <!-- JavaScript で動的に生成 -->
    </div>

    @isset($weatherData)
    <div class="border-t pt-6">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    📍 {{ isset($weatherData['record']->region) ? $weatherData['record']->region->name : $weatherData['record']->location_name }}
                </h2>
                <div class="text-sm text-gray-600 mb-4">
                    {{ isset($weatherData['record']->date) ? $weatherData['record']->date->format('Y年m月d日') : date('Y年m月d日') }}の天気
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

<script>
// 現在地取得機能
document.getElementById('currentLocationBtn').addEventListener('click', function() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage = document.getElementById('errorMessage');
    const weatherDisplay = document.getElementById('weatherDisplay');
    const button = this;
    
    // UI初期化
    loadingIndicator.classList.remove('hidden');
    errorMessage.classList.add('hidden');
    weatherDisplay.classList.add('hidden');
    button.disabled = true;
    button.innerHTML = '📍 取得中...';
    
    if (!navigator.geolocation) {
        showError('お使いのブラウザは位置情報をサポートしていません。');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            fetch('{{ route("weather.current-location") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ lat: lat, lon: lon })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCurrentLocationWeather(data.weatherData);
                } else {
                    showError(data.error || '天気情報の取得に失敗しました。');
                }
            })
            .catch(error => {
                showError('サーバーエラーが発生しました。');
            })
            .finally(() => {
                loadingIndicator.classList.add('hidden');
                button.disabled = false;
                button.innerHTML = '📍 現在地の天気を取得';
            });
        },
        function(error) {
            let errorMsg = '';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg = '位置情報の使用が拒否されました。ブラウザの設定から位置情報を許可してください。';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg = '位置情報を取得できませんでした。';
                    break;
                case error.TIMEOUT:
                    errorMsg = '位置情報の取得がタイムアウトしました。';
                    break;
                default:
                    errorMsg = '位置情報の取得中にエラーが発生しました。';
                    break;
            }
            showError(errorMsg);
            loadingIndicator.classList.add('hidden');
            button.disabled = false;
            button.innerHTML = '📍 現在地の天気を取得';
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
    
    function showError(message) {
        document.getElementById('errorText').textContent = message;
        errorMessage.classList.remove('hidden');
    }
    
    function displayCurrentLocationWeather(weatherData) {
        const record = weatherData.record;
        const data = record.weather_data;
        
        const weatherHtml = `
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">
                        📍 ${record.location_name}
                    </h2>
                    <div class="text-sm text-gray-600 mb-4">
                        ${record.date}の天気
                    </div>
                    
                    <!-- メイン天気情報 -->
                    <div class="grid grid-cols-2 gap-4 max-w-md mx-auto mb-6">
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-2xl mb-1">
                                ${data.icon ? `<img src="https://openweathermap.org/img/wn/${data.icon}@2x.png" alt="${data.weather}" class="w-12 h-12 mx-auto">` : '🌤️'}
                            </div>
                            <div class="text-lg font-semibold text-gray-800">
                                ${data.weather}
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-2xl mb-1">🌡️</div>
                            <div class="text-2xl font-bold text-blue-600">
                                ${data.temperature}°C
                            </div>
                            ${data.feels_like ? `<div class="text-sm text-gray-600">体感 ${data.feels_like}°C</div>` : ''}
                        </div>
                    </div>

                    <!-- 詳細天気情報 -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 max-w-4xl mx-auto mb-6">
                        ${data.temp_min && data.temp_max ? `
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                            <div class="text-lg mb-1">📊</div>
                            <div class="text-sm font-semibold text-gray-800">最高/最低</div>
                            <div class="text-lg font-bold text-red-500">${data.temp_max}°C</div>
                            <div class="text-lg font-bold text-blue-500">${data.temp_min}°C</div>
                        </div>` : ''}

                        ${data.humidity ? `
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                            <div class="text-lg mb-1">💧</div>
                            <div class="text-sm font-semibold text-gray-800">湿度</div>
                            <div class="text-lg font-bold text-blue-600">${data.humidity}%</div>
                        </div>` : ''}

                        ${data.pressure ? `
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                            <div class="text-lg mb-1">⏲️</div>
                            <div class="text-sm font-semibold text-gray-800">気圧</div>
                            <div class="text-lg font-bold text-purple-600">${data.pressure}hPa</div>
                        </div>` : ''}

                        ${data.wind_speed ? `
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                            <div class="text-lg mb-1">💨</div>
                            <div class="text-sm font-semibold text-gray-800">風速</div>
                            <div class="text-lg font-bold text-green-600">${data.wind_speed}m/s</div>
                            ${data.wind_deg ? `<div class="text-xs text-gray-500">${data.wind_deg}°</div>` : ''}
                        </div>` : ''}

                        ${data.visibility ? `
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                            <div class="text-lg mb-1">👁️</div>
                            <div class="text-sm font-semibold text-gray-800">視界</div>
                            <div class="text-lg font-bold text-indigo-600">${Math.round(data.visibility / 1000 * 10) / 10}km</div>
                        </div>` : ''}

                        ${data.clouds !== null ? `
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                            <div class="text-lg mb-1">☁️</div>
                            <div class="text-sm font-semibold text-gray-800">雲量</div>
                            <div class="text-lg font-bold text-gray-600">${data.clouds}%</div>
                        </div>` : ''}

                        ${data.sunrise && data.sunset ? `
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                            <div class="text-lg mb-1">🌅</div>
                            <div class="text-sm font-semibold text-gray-800">日の出/日の入り</div>
                            <div class="text-sm font-bold text-orange-500">${new Date(data.sunrise * 1000).toLocaleTimeString('ja-JP', {hour: '2-digit', minute:'2-digit', timeZone: 'Asia/Tokyo'})}</div>
                            <div class="text-sm font-bold text-purple-500">${new Date(data.sunset * 1000).toLocaleTimeString('ja-JP', {hour: '2-digit', minute:'2-digit', timeZone: 'Asia/Tokyo'})}</div>
                        </div>` : ''}
                    </div>

                    <div class="mt-4 text-xs text-gray-500">
                        ${weatherData.is_from_cache 
                            ? `キャッシュから取得 (${new Date(weatherData.cached_at).toLocaleTimeString('ja-JP', {hour: '2-digit', minute:'2-digit'})}に取得済み)`
                            : `APIから取得 (${new Date().toLocaleTimeString('ja-JP', {hour: '2-digit', minute:'2-digit'})})`
                        }
                    </div>
                </div>
            </div>
        `;
        
        weatherDisplay.innerHTML = weatherHtml;
        weatherDisplay.classList.remove('hidden');
    }
});
</script>
@endsection