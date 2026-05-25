<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLogger
{
    private float $startTime;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->startTime = microtime(true);

        return $next($request);
    }

    // applaying after the response arived to the user
    public function terminate(Request $request, Response $response): void
    {
        // AFTER the response is ready — log everything
        $duration = round((microtime(true) - $this->startTime) * 1000, 2);

        Log::channel('api')->info('API Request', [
            'user_id' => $request->user()?->id ?? 'guest',
            'user_role' => $request->user()?->role ?? 'guest',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
