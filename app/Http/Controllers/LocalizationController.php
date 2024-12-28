<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocalizationController extends Controller
{
    public function __invoke($locale)    // Change the language 
    {   
        // Accept only Arabic and English languages
        if($locale != 'ar' && $locale != 'en')
            return response()->json([
                'message' => 'Language not found'
            ], 404);
            
        auth()->user()->update([
            'lang' => $locale
        ]);

        return response()->json([
            'message' => 'Language changed successfully'
        ]);
    }
}
