<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBannedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            return response()->json([
                'message' => 'Your account has been banned',
                'banned_until' => $user->active_banned_until,
                'reason' => $user->ban_reason,
            ], 403);
        }

        return $next($request);
    }
}
