<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class GuestMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Redirect authenticated users to the index route
        if (request()->header('Authorization') && JWTAuth::parseToken()->check()) 
            return to_route('markets');
        return $next($request);
    }
}
