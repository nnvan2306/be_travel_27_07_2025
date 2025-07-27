<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AuthenticateWithCookie
{
  public function handle(Request $request, Closure $next): Response
  {
    $token = $request->cookie('auth_token');
    if ($token) {
      $accessToken = PersonalAccessToken::findToken($token);
      if ($accessToken && $accessToken->tokenable) {
        Auth::login($accessToken->tokenable);
        Log::info('Authenticated user via cookie: ', ['user' => $accessToken->tokenable]);
      } else {
        Log::info('Invalid token in auth_token cookie: ', ['token' => $token]);
      }
    } else {
      Log::info('No auth_token cookie found');
    }

    if (!Auth::check()) {
      return response()->json(['message' => 'Unauthenticated'], 401);
    }

    return $next($request);
  }
}