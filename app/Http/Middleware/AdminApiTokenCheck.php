<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminApiTokenCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Access denied. No token provided.',
                ], 401);
            }

            $admin = auth('admin-api')->user();

            if (!$admin) {
                $admin = auth('admin-api')->setToken($token)->user();
            }

            if (!$admin) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthorized access. Invalid credentials.',
                ], 401);
            }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Session expired. Please login again.',
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid token. Please login again.',
            ], 401);

        } catch (TokenBlacklistedException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token has been revoked. Please login again.',
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong with authentication, please try again.',
            ], 401);
        }

        return $next($request);
    }
}

