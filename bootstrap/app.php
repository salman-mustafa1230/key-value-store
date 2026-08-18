<?php

use App\Domain\KeyStore\Exceptions\ClientError;
use App\Domain\KeyStore\Exceptions\PersistenceFailed;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api/v1.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e): void {
            fwrite(STDERR, $e->__toString().PHP_EOL);
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

            return response()->json([
                'error' => [
                    'code' => 'http_error',
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'HTTP error.',
                ],
            ], $e->getStatusCode());
        });
    })->create();
