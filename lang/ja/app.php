<?php

return [
    // ヘッダー・タイトル
    'app_name' => '天気アプリ',
    'app_description' => '地域を選択して今日の天気をチェック',
    
    // メインセクション
    'current_location' => '現在地の天気',
    'get_current_location' => '現在地の天気を取得',
    'region_selection' => '地域を選択',
    'select_region' => '地域を選択してください',
    'select_region_placeholder' => '地域を選択...',
    'get_region_weather' => '選択地域の天気を調べる',
    
    // 天気表示
    'weather_for_date' => 'の天気',
    'feels_like' => '体感',
    'high_low' => '最高/最低',
    'humidity' => '湿度',
    'pressure' => '気圧',
    'wind_speed' => '風速',
    'visibility' => '視界',
    'clouds' => '雲量',
    'sunrise_sunset' => '日の出/日の入り',
    
    // メッセージ
    'loading' => '現在地を取得中...',
    'getting_weather' => '取得中...',
    'from_cache' => 'キャッシュから取得',
    'from_api' => 'APIから取得',
    'cached_at' => 'に取得済み',
    'check_other_region' => '他の地域を調べる',
    
    // エラーメッセージ
    'location_not_supported' => 'お使いのブラウザは位置情報をサポートしていません。',
    'location_denied' => '位置情報の使用が拒否されました。ブラウザの設定から位置情報を許可してください。',
    'location_unavailable' => '位置情報を取得できませんでした。',
    'location_timeout' => '位置情報の取得がタイムアウトしました。',
    'location_error' => '位置情報の取得中にエラーが発生しました。',
    'weather_error' => '天気情報の取得に失敗しました。',
    'weather_fetch_error' => '天気情報の取得に失敗しました。',
    'city_not_found' => '指定された都市が見つかりませんでした。',
    'server_error' => 'サーバーエラーが発生しました。',
    
    // 言語切り替え
    'language' => '言語',
    'switch_language' => '言語を切り替え',
    
    // メタデータ
    'meta_description' => '日本の地域別天気予報を確認できる天気アプリです。リアルタイムの気温と天気状況をお知らせします。',
    'meta_keywords' => '天気予報, 気温, 日本, 天気アプリ, OpenWeatherMap',
    'powered_by' => 'Powered by OpenWeatherMap',
];