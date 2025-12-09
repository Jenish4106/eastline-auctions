<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            if ($request->getMethod() === 'GET' && !$request->expectsJson()) {
                session(['intended_url' => $request->fullUrl()]);
            }

            return redirect()->route('admin.login')->with('error', 'Please login to access this page.');
        }

        return $next($request);
    }
}
