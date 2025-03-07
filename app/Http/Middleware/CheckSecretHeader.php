<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSecretHeader
{

    public function handle(Request $request, Closure $next)
    {

        $providedKey = $request->header('X-Secret-Key');

        // The expected key is read from config (points to .env)
        $expectedKey = config('app.x_secret_key');
        // or env('X_SECRET_KEY') if you'd rather go directly

        // Check if they match
        if (!$providedKey || $providedKey !== $expectedKey) {
            // If the header is missing or invalid, return 401 Unauthorized
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
