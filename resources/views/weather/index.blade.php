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
                        {{ isset($weatherRecord) && $weatherRecord->region_id == $region->id ? 'selected' : '' }}>
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

    @isset($weatherRecord)
    <div class="border-t pt-6">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    📍 {{ $weatherRecord->region->name }}
                </h2>
                <div class="text-sm text-gray-600 mb-4">
                    {{ $weatherRecord->date->format('Y年m月d日') }}の天気
                </div>
                
                <div class="grid grid-cols-2 gap-4 max-w-md mx-auto">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="text-2xl mb-1">🌤️</div>
                        <div class="text-lg font-semibold text-gray-800">
                            {{ $weatherRecord->weather }}
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="text-2xl mb-1">🌡️</div>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ $weatherRecord->temperature }}°C
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-xs text-gray-500">
                    @if($weatherRecord->created_at->isToday())
                        APIから取得 ({{ $weatherRecord->created_at->setTimezone('Asia/Tokyo')->format('H:i') }})
                    @else
                        データベースから取得
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endisset
</div>

@isset($weatherRecord)
<div class="mt-6 text-center">
    <a href="{{ route('weather.index') }}" 
        class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
        🔄 他の地域を調べる
    </a>
</div>
@endisset
@endsection