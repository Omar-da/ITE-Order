<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    public function handle(Request $request, Closure $next): Response    // Set the language of the user
    {
        $user = auth()->user();
        if($user)
            App::setLocale($user->lang);  

        return $next($request);
    }
}
