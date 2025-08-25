<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * 時間別天気予報モデル
 * OpenWeatherMap One Call API 3.0 から取得した48時間分の予報データを格納
 */
class WeatherHourlyForecast extends Model
{
    use HasFactory;

    /**
     * テーブル名
     */
    protected $table = 'weather_hourly_forecasts';

    /**
     * 一括代入可能な属性（データベースに保存できるフィールド）
     */
    protected $fillable = [
        'region_id',        // 地域ID（地域選択時に使用）
        'lat_rounded',      // 丸めた緯度（現在地用、0.1度単位）
        'lon_rounded',      // 丸めた経度（現在地用、0.1度単位）
        'forecast_time',    // 予報時刻（UTC）
        'temperature',      // 気温（摂氏）
        'weather',          // 天気概要（晴れ、曇りなど）
        'icon',            // 天気アイコンID（OpenWeatherMap形式）
        'pop',             // 降水確率（0-1の小数点、例：0.3=30%）
        'date',            // 日付（キャッシュ管理用）
        'locale'           // 言語コード（ja/en/zh）
    ];

    /**
     * 型キャスト（データベースの値を適切な型に自動変換）
     */
    protected $casts = [
        'forecast_time' => 'datetime',    // 文字列 → Carbon日時オブジェクト
        'date' => 'date',                // 文字列 → Carbon日付オブジェクト
        'temperature' => 'float',        // 文字列 → 小数点数
        'pop' => 'float',               // 文字列 → 小数点数（降水確率）
        'lat_rounded' => 'decimal:1',   // 緯度（小数点1桁まで）
        'lon_rounded' => 'decimal:1'    // 経度（小数点1桁まで）
    ];

    /**
     * 地域との関連（地域選択用）
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * 地域選択用の時間別予報取得スコープ
     * 使用例: WeatherHourlyForecast::forRegion(1, 'ja')->get()
     */
    public function scopeForRegion($query, $regionId, $locale = 'ja')
    {
        return $query->where('region_id', $regionId)        // 指定された地域
                    ->where('locale', $locale)              // 指定された言語
                    ->where('date', Carbon::today())        // 今日の予報のみ
                    ->orderBy('forecast_time');             // 時間順で並び替え
    }

    /**
     * 現在地用の時間別予報取得スコープ
     * 使用例: WeatherHourlyForecast::forLocation(35.7, 139.7, 'ja')->get()
     */
    public function scopeForLocation($query, $latRounded, $lonRounded, $locale = 'ja')
    {
        return $query->where('lat_rounded', $latRounded)    // 丸めた緯度
                    ->where('lon_rounded', $lonRounded)     // 丸めた経度
                    ->where('locale', $locale)              // 指定された言語
                    ->where('date', Carbon::today())        // 今日の予報のみ
                    ->orderBy('forecast_time');             // 時間順で並び替え
    }

    /**
     * 指定時刻以降の予報のみ取得（過去の時刻は除外）
     * 使用例: WeatherHourlyForecast::futureOnly()->get()
     */
    public function scopeFutureOnly($query)
    {
        return $query->where('forecast_time', '>=', Carbon::now());  // 現在時刻以降のみ
    }

    /**
     * 予報時刻をJST形式で取得
     * 使用例: $forecast->forecast_time_jst （UTC → JST自動変換）
     */
    public function getForecastTimeJstAttribute()
    {
        return $this->forecast_time->setTimezone('Asia/Tokyo');  // 日本時間に変換
    }

    /**
     * 降水確率をパーセント形式で取得
     * 使用例: $forecast->pop_percent （0.3 → 30 に自動変換）
     */
    public function getPopPercentAttribute()
    {
        return round($this->pop * 100);  // 0-1 → 0-100% に変換
    }

    /**
     * 予報時刻をタイムスタンプ形式で取得（フロントエンド用）
     * 使用例: $forecast->forecast_timestamp
     */
    public function getForecastTimestampAttribute()
    {
        return $this->forecast_time->timestamp;  // Unix timestamp
    }

    /**
     * フロントエンド用のデータ配列を取得
     * 使用例: $forecast->frontend_data
     */
    public function getFrontendDataAttribute()
    {
        return [
            'forecast_time' => $this->forecast_timestamp,
            'temperature' => $this->temperature,
            'weather' => $this->weather,
            'icon' => $this->icon,
            'pop' => $this->pop
        ];
    }
}
