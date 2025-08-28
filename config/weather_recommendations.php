<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Weather-based Restaurant Recommendations
    |--------------------------------------------------------------------------
    |
    | 天気に基づくレストラン推奨設定
    | HotPepper Gourmet API のジャンルコードと推奨理由をマッピング
    |
    */

    'genre_mapping' => [
        // 晴れ系（屋外系、テラス、BBQ等）
        'clear' => [
            'primary' => 'G017',    // お好み焼き・もんじゃ（屋外気分）
            'secondary' => 'G014',  // イタリアン・フレンチ（テラス席）
            'keywords' => ['晴', '晴天', '快晴', 'clear sky', '晴朗'],
            'reason' => [
                'ja' => 'お天気が良いので、テラス席やお好み焼きなど気軽に楽しめるお店がおすすめです',
                'en' => 'Perfect weather for terrace dining and casual outdoor restaurants',
                'zh' => '天气晴朗，推荐有露台座位的轻松用餐场所'
            ]
        ],

        // 雨系（屋内でゆったり、温かい料理）
        'rain' => [
            'primary' => 'G004',    // 和食（温かい料理）
            'secondary' => 'G013',  // カフェ・スイーツ（ゆったり）
            'keywords' => ['雨', '小雨', '大雨', '弱いにわか雨', '強い雨', 'light rain', 'moderate rain', 'heavy rain', 'shower'],
            'reason' => [
                'ja' => '雨の日は温かい和食やゆったりできるカフェで過ごしませんか',
                'en' => 'Rainy weather calls for warm Japanese cuisine or cozy cafes',
                'zh' => '雨天适合享用温暖的日式料理或在咖啡厅悠闲度过'
            ]
        ],

        // 曇り系（バランス重視、普通の日）
        'clouds' => [
            'primary' => 'G002',    // ダイニングバー・バル（バランス重視）
            'secondary' => 'G005',  // 洋食（定番）
            'keywords' => ['雲', '薄い雲', '厚い雲', '曇りがち', 'few clouds', 'scattered clouds', 'broken clouds', 'overcast clouds'],
            'reason' => [
                'ja' => '曇りの日は定番の洋食やダイニングバーでお食事を楽しみましょう',
                'en' => 'Cloudy weather is perfect for classic Western cuisine or dining bars',
                'zh' => '阴天适合享用经典西餐或在餐酒吧用餐'
            ]
        ],

        // 雪系（温かい料理、鍋物系）
        'snow' => [
            'primary' => 'G016',    // 韓国料理（辛くて温かい）
            'secondary' => 'G006',  // 中華（温かい）
            'keywords' => ['雪', '吹雪', 'snow', 'blizzard', 'sleet'],
            'reason' => [
                'ja' => '寒い雪の日は辛い韓国料理や温かい中華で体を温めましょう',
                'en' => 'Cold snowy weather is perfect for spicy Korean or warm Chinese cuisine',
                'zh' => '雪天适合享用温暖的韩式料理或中华料理'
            ]
        ],

        // 霧・靄系
        'mist' => [
            'primary' => 'G001',    // 居酒屋（雰囲気重視）
            'secondary' => 'G004',  // 和食
            'keywords' => ['霧', '靄', '霞', 'mist', 'fog', 'haze'],
            'reason' => [
                'ja' => '霧の幻想的な日は落ち着いた居酒屋や和食でゆっくり過ごしませんか',
                'en' => 'Misty weather creates a perfect atmosphere for traditional izakaya dining',
                'zh' => '雾天适合在传统居酒屋或日式料理店悠闲用餐'
            ]
        ],

        // デフォルト（その他の天気）
        'default' => [
            'primary' => 'G001',    // 居酒屋（万能）
            'secondary' => 'G005',  // 洋食
            'keywords' => [],
            'reason' => [
                'ja' => '今日は定番の居酒屋や洋食でお食事を楽しみましょう',
                'en' => 'Enjoy classic izakaya or Western cuisine today',
                'zh' => '今天推荐经典居酒屋或西餐'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | HotPepper Genre Codes Reference
    |--------------------------------------------------------------------------
    |
    | 参考：HotPepper Gourmet API ジャンルコード一覧
    |
    */
    'genre_codes' => [
        'G001' => '居酒屋',
        'G002' => 'ダイニングバー・バル',
        'G003' => '創作料理',
        'G004' => '和食',
        'G005' => '洋食',
        'G006' => '中華',
        'G007' => '焼肉・ホルモン',
        'G008' => 'アジア・エスニック料理',
        'G013' => 'カフェ・スイーツ',
        'G014' => 'イタリアン・フレンチ',
        'G016' => '韓国料理',
        'G017' => 'お好み焼き・もんじゃ'
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    |
    | API検索時の設定値
    |
    */
    'search' => [
        'default_count' => 20,      // デフォルト取得件数
        'max_count' => 50,          // 最大取得件数
        'default_range' => 3,       // デフォルト検索範囲（1000m）
        'ranges' => [
            1 => '300m',
            2 => '500m',
            3 => '1000m',
            4 => '2000m',
            5 => '3000m'
        ]
    ],

];