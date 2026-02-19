<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle JSON responses for API errors
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => $e->errors(),
                    ], 422);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'message' => 'Unauthenticated. Please login first.',
                    ], 401);
                }

                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'message' => 'Unauthorized. You do not have permission to perform this action.',
                    ], 403);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'message' => 'Resource not found.',
                    ], 404);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    return response()->json([
                        'message' => 'Method not allowed.',
                    ], 405);
                }

                // Generic error response
                $message = config('app.debug') ? $e->getMessage() : 'An error occurred. Please try again later.';

                return response()->json([
                    'message' => $message,
                    'error' => config('app.debug') ? get_class($e) : null,
                ], $statusCode);
            }
        });
    })->create();
