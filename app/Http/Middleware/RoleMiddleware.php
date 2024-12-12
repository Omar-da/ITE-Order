<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    public function handle($request, Closure $next, string $role)       // Check if user is admin or not
    {

        if (auth()->user()->role == $role || (auth()->user()->role == 'owner' && $role == 'admin'))
            return $next($request);
        
        abort(403, 'Access Denied');
    }

}
