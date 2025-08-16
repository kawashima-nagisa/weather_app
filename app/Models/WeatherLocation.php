<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'lat_rounded',
        'lon_rounded',
        'date',
        'location_name',
        'country',
        'weather_data',
    ];

    protected $casts = [
        'date' => 'date',
        'weather_data' => 'array',
        'lat_rounded' => 'float',
        'lon_rounded' => 'float',
    ];
}
