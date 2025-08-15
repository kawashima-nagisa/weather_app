<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            [
                'name' => '東京',
                'code' => 'Tokyo',
                'lat' => 35.6762,
                'lon' => 139.6503,
            ],
            [
                'name' => '大阪',
                'code' => 'Osaka',
                'lat' => 34.6937,
                'lon' => 135.5023,
            ],
            [
                'name' => '京都',
                'code' => 'Kyoto',
                'lat' => 35.0116,
                'lon' => 135.7681,
            ],
            [
                'name' => '福岡',
                'code' => 'Fukuoka',
                'lat' => 33.5904,
                'lon' => 130.4017,
            ],
            [
                'name' => '札幌',
                'code' => 'Sapporo',
                'lat' => 43.0642,
                'lon' => 141.3469,
            ],
            [
                'name' => '名古屋',
                'code' => 'Nagoya',
                'lat' => 35.1815,
                'lon' => 136.9066,
            ],
            [
                'name' => '仙台',
                'code' => 'Sendai',
                'lat' => 38.2682,
                'lon' => 140.8694,
            ],
            [
                'name' => '広島',
                'code' => 'Hiroshima',
                'lat' => 34.3853,
                'lon' => 132.4553,
            ],
        ];

        foreach ($regions as $region) {
            Region::create($region);
        }
    }
}
