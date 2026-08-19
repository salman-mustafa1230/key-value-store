<?php

use App\Domain\KeyStore\Exceptions\ClientError;
use App\Domain\KeyStore\Exceptions\PersistenceFailed;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['api', 'throttle:keystore'])
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api/v1.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 404/400 must not go through Monolog. If the log stream is closed
        // (php -S workers on Railway), report() throws and the kernel never
        // renders — empty HTML 500, nothing in Railway logs.
        $exceptions->dontReport([
            ClientError::class,
            ThrottleRequestsException::class,
        ]);

        $exceptions->reportable(function (Throwable $e): void {
            try {
                $line = $e->__toString();
                error_log($line);
                fwrite(STDERR, $line.PHP_EOL);
            } catch (Throwable) {
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*')
                || $request->expectsJson(),
        );

        $exceptions->render(function (ClientError $e) {
            return response()->json([
                'error' => [
                    'code' => $e->errorCode(),
                    'message' => $e->getMessage(),
                ],
            ], $e->status());
        });

        $exceptions->render(function (PersistenceFailed $e) {
            return response()->json([
                'error' => [
                    'code' => 'persistence_failed',
                    'message' => 'The write could not be completed. Retry the request.',
                ],
            ], 500);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();
            $code = $status === 429 ? 'rate_limited' : 'http_error';
            $message = $status === 429
                ? 'Too many requests. Retry later.'
                : ($e->getMessage() !== '' ? $e->getMessage() : 'HTTP error.');

            return response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ], $status);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'server_error',
                    'message' => 'An unexpected error occurred.',
                ],
            ], 500);
        });
    })->create();
