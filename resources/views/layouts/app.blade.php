<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', __('app.meta_description'))">
    <meta name="keywords" content="{{ __('app.meta_keywords') }}">
    <meta name="author" content="{{ __('app.app_name') }}">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', __('app.app_name'))">
    <meta property="og:description" content="@yield('description', __('app.meta_description'))">
    <meta property="og:image" content="{{ asset('images/weather.png') }}">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="@yield('title', __('app.app_name'))">
    <meta property="twitter:description" content="@yield('description', __('app.meta_description'))">
    <meta property="twitter:image" content="{{ asset('images/weather.png') }}">
    
    <!-- JSON-LD structured data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "{{ __('app.app_name') }}",
        "description": "{{ __('app.meta_description') }}",
        "url": "{{ url('/') }}",
        "applicationCategory": "Weather",
        "operatingSystem": "Web Browser"
    }
    </script>
    
    <title>@yield('title', __('app.app_name'))</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('weather-favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('weather-favicon.ico') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-400 to-blue-600 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- 言語切り替えボタン -->
        <div class="flex justify-end mb-4">
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-2">
                <form action="{{ route('language.switch') }}" method="POST" class="flex items-center space-x-2">
                    @csrf
                    <span class="text-white text-sm">{{ __('app.language') }}:</span>
                    <select name="locale" onchange="this.form.submit()" 
                        class="bg-white/20 text-white border border-white/30 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-white/50">
                        @foreach(config('app.supported_locales') as $code => $name)
                            <option value="{{ $code }}" {{ app()->getLocale() === $code ? 'selected' : '' }}
                                class="bg-blue-600 text-white">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <header class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">🌤️ {{ __('app.app_name') }}</h1>
            <p class="text-blue-100">{{ __('app.app_description') }}</p>
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
            <p>&copy; 2025 {{ __('app.app_name') }} - {{ __('app.powered_by') }}</p>
        </footer>
    </div>
</body>
</html>