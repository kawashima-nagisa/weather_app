<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'weather',
        'icon',
        'temperature',
        'feels_like',
        'temp_min',
        'temp_max',
        'pressure',
        'humidity',
        'visibility',
        'wind_speed',
        'wind_deg',
        'clouds',
        'sunrise',
        'sunset',
        'country',
        'api_dt',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'temperature' => 'float',
        'feels_like' => 'float',
        'temp_min' => 'float',
        'temp_max' => 'float',
        'wind_speed' => 'float',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
