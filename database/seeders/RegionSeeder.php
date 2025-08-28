<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            // 関東地方
            ['name' => '東京', 'lat' => 35.6895, 'lon' => 139.6917],
            ['name' => '横浜', 'lat' => 35.4437, 'lon' => 139.6380],
            ['name' => '千葉', 'lat' => 35.6074, 'lon' => 140.1065],
            
            // 関西地方
            ['name' => '大阪', 'lat' => 34.6937, 'lon' => 135.5023],
            ['name' => '京都', 'lat' => 34.9859, 'lon' => 135.7581],  // 京都駅周辺
            ['name' => '神戸', 'lat' => 34.6901, 'lon' => 135.1956],
            ['name' => '奈良', 'lat' => 34.6851, 'lon' => 135.8049],
            
            // 中部地方
            ['name' => '名古屋', 'lat' => 35.1815, 'lon' => 136.9066],
            ['name' => '静岡', 'lat' => 34.9769, 'lon' => 138.3831],
            ['name' => '金沢', 'lat' => 36.5944, 'lon' => 136.6256],
            
            // 北海道・東北
            ['name' => '札幌', 'lat' => 43.0642, 'lon' => 141.3469],
            ['name' => '仙台', 'lat' => 38.2682, 'lon' => 140.8694],
            
            // 九州・沖縄
            ['name' => '福岡', 'lat' => 33.5904, 'lon' => 130.4017],
            ['name' => '熊本', 'lat' => 32.7898, 'lon' => 130.7417],
            ['name' => '那覇', 'lat' => 26.2124, 'lon' => 127.6792],
            
            // 中国・四国
            ['name' => '広島', 'lat' => 34.3853, 'lon' => 132.4553],
            ['name' => '岡山', 'lat' => 34.6551, 'lon' => 133.9195],
            ['name' => '高松', 'lat' => 34.3401, 'lon' => 134.0431],
        ];

        foreach ($regions as $region) {
            Region::create($region);
        }
    }
}