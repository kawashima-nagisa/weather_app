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
        // 晴れ系（屋外・カジュアル・アクティブ）
        'clear' => [
            'options' => ['G016', 'G006', 'G008'],  // お好み焼き・イタリアン・焼肉
            'keywords' => ['晴', '晴天', '快晴', 'clear sky', '晴朗'],
            'reason' => [
                'ja' => 'お天気が良いので、テラス席でイタリアンやお好み焼き、BBQなど屋外気分を楽しめるお店がおすすめです',
                'en' => 'Perfect weather for terrace dining, BBQ, and casual outdoor restaurants',
                'zh' => '天气晴朗，推荐有露台座位、烧烤等轻松的户外用餐场所'
            ]
        ],

        // 雨系（屋内・温かい・ゆったり）
        'rain' => [
            'options' => ['G004', 'G014', 'G013'],  // 和食・カフェ・ラーメン
            'keywords' => ['雨', '小雨', '大雨', '弱いにわか雨', '強い雨', 'light rain', 'moderate rain', 'heavy rain', 'shower'],
            'reason' => [
                'ja' => '雨の日は温かい和食や熱々のラーメン、ゆったりできるカフェで過ごしませんか',
                'en' => 'Rainy weather calls for warm Japanese cuisine, hot ramen, or cozy cafes',
                'zh' => '雨天适合享用温暖的日式料理、热腾腾的拉面或在咖啡厅悠闲度过'
            ]
        ],

        // 曇り系（バランス・普通・新発見）
        'clouds' => [
            'options' => ['G002', 'G005', 'G003'],  // ダイニングバー・洋食・創作料理
            'keywords' => ['雲', '薄い雲', '厚い雲', '曇りがち', 'few clouds', 'scattered clouds', 'broken clouds', 'overcast clouds'],
            'reason' => [
                'ja' => '曇りの日は定番の洋食やダイニングバー、新感覚の創作料理で新しい発見を楽しみましょう',
                'en' => 'Cloudy weather is perfect for classic Western cuisine, dining bars, or creative dishes',
                'zh' => '阴天适合享用经典西餐、餐酒吧或创意料理'
            ]
        ],

        // 雪系（寒い・辛い・温かい）
        'snow' => [
            'options' => ['G017', 'G007', 'G009'],  // 韓国料理・中華・アジア
            'keywords' => ['雪', '吹雪', 'snow', 'blizzard', 'sleet'],
            'reason' => [
                'ja' => '寒い雪の日は辛い韓国料理や温かい中華、スパイシーなアジア料理で体を温めましょう',
                'en' => 'Cold snowy weather is perfect for spicy Korean, warm Chinese, or Asian cuisine',
                'zh' => '雪天适合享用温暖的韩式料理、中华料理或亚洲料理'
            ]
        ],

        // 霧系（幻想的・落ち着いた・異国情緒）
        'mist' => [
            'options' => ['G001', 'G012', 'G010'],  // 居酒屋・バー・各国料理
            'keywords' => ['霧', '靄', '霞', 'mist', 'fog', 'haze'],
            'reason' => [
                'ja' => '霧の幻想的な日は落ち着いた居酒屋やバー、異国情緒あふれる各国料理でゆっくり過ごしませんか',
                'en' => 'Misty weather creates a perfect atmosphere for traditional izakaya, bars, or international cuisine',
                'zh' => '雾天适合在传统居酒屋、酒吧或各国料理店悠闲用餐'
            ]
        ],

        // デフォルト（その他・楽しい）
        'default' => [
            'options' => ['G015', 'G011'],  // その他グルメ・カラオケ
            'keywords' => [],
            'reason' => [
                'ja' => '今日はその他グルメやカラオケ・パーティで楽しい時間をお過ごしください',
                'en' => 'Enjoy diverse gourmet options or karaoke dining today',
                'zh' => '今天推荐各种美食或卡拉OK聚餐'
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
        'G006' => 'イタリアン・フレンチ',
        'G007' => '中華',
        'G008' => '焼肉・ホルモン',
        'G009' => 'アジア・エスニック料理',
        'G010' => '各国料理',
        'G011' => 'カラオケ・パーティ',
        'G012' => 'バー・カクテル',
        'G013' => 'ラーメン',
        'G014' => 'カフェ・スイーツ',
        'G015' => 'その他グルメ',
        'G016' => 'お好み焼き・もんじゃ',
        'G017' => '韓国料理'
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