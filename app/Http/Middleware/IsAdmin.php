<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle($request, Closure $next)
    {
        if (! Auth::user()->is_admin) {
            Auth::logout();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
