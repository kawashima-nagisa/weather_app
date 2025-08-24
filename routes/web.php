<?php

use App\Http\Controllers\WeatherController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [WeatherController::class, 'index'])->name('weather.index');
Route::post('/weather', [WeatherController::class, 'show'])->name('weather.show');
Route::post('/weather/current-location', [WeatherController::class, 'currentLocation'])->name('weather.current-location');

// 言語切り替え
Route::post('/language/switch', [LanguageController::class, 'switch'])->name('language.switch');
