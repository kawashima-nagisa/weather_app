<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', '日本の地域別天気予報を確認できる天気アプリです。リアルタイムの気温と天気状況をお知らせします。')">
    <meta name="keywords" content="天気予報, 気温, 日本, 天気アプリ, OpenWeatherMap">
    <meta name="author" content="天気アプリ">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', '天気アプリ')">
    <meta property="og:description" content="@yield('description', '日本の地域別天気予報を確認できる天気アプリです。リアルタイムの気温と天気状況をお知らせします。')">
    <meta property="og:image" content="{{ asset('images/weather-app-og.jpg') }}">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="@yield('title', '天気アプリ')">
    <meta property="twitter:description" content="@yield('description', '日本の地域別天気予報を確認できる天気アプリです。リアルタイムの気温と天気状況をお知らせします。')">
    <meta property="twitter:image" content="{{ asset('images/weather-app-og.jpg') }}">
    
    <!-- JSON-LD structured data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "天気アプリ",
        "description": "日本の地域別天気予報を確認できる天気アプリです。",
        "url": "{{ url('/') }}",
        "applicationCategory": "Weather",
        "operatingSystem": "Web Browser"
    }
    </script>
    
    <title>@yield('title', '天気アプリ')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('weather-favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('weather-favicon.ico') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-400 to-blue-600 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <header class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">🌤️ 天気アプリ</h1>
            <p class="text-blue-100">地域を選択して今日の天気をチェック</p>
        </header>

        <main class="max-w-2xl mx-auto">
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="text-center mt-12 text-blue-100">
            <p>&copy; 2025 天気アプリ - Powered by OpenWeatherMap</p>
        </footer>
    </div>
</body>
</html>