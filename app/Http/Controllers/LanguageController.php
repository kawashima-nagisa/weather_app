<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * 言語を切り替える
     */
    public function switch(Request $request)
    {
        $locale = $request->input('locale');
        $supportedLocales = array_keys(config('app.supported_locales'));
        
        if (in_array($locale, $supportedLocales)) {
            Session::put('locale', $locale);
            App::setLocale($locale);
        }
        
        return redirect()->route('weather.index');
    }
}
