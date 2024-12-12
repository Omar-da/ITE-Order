<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Contracts\Providers\JWT;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Exceptions\JWTException;

class RefreshMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {   
        // Determinate the place of refresh token according to the incoming request
        if($request->url() == route('logout')) {
            $refreshToken = str_replace('Bearer ', '', $request->input('refresh_token'));
            $request->attributes->set('refresh_token', $refreshToken);
        }
        if($request->url() == route('refresh'))
            $refreshToken = str_replace('Bearer ', '', $request->header('Authorization'));
        
        // Check if refresh token is existed
        if (!$refreshToken)
            return response()->json(['error' => 'Refresh token is required'], 400);
    
        // Check if it's an expired token
        try{
            $payload = JWTAuth::setToken($refreshToken)->getPayload();
        }
        catch(JWTException $e)
        {
            return response()->json(['error' => 'Token expired'], 401);
        }

        // Check if it's a valid refresh token
        if ($payload['type'] !== 'refresh') {
            return response()->json(['error' => 'Invalid token type'], 401);
        }
        
        // Check if it's a blacklisted refresh token
        $jti = $payload->get('jti');
        if(Cache::has("blacklisted_refresh_token_{$jti}"))
            return response()->json(['error' => 'Unauthenticated'], 401);

        return $next($request);
    }
}
