<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\KeyStore\Clock;
use App\Domain\KeyStore\KeyStoreRepository;
use App\Domain\KeyStore\SystemClock;
use App\Infrastructure\Persistence\ConfigurePostgresSession;
use App\Infrastructure\Persistence\KeyStore\PostgresKeyStoreRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(KeyStoreRepository::class, PostgresKeyStoreRepository::class);
    }

    public function boot(): void
    {
        Event::listen(ConnectionEstablished::class, ConfigurePostgresSession::class);

        RateLimiter::for('keystore', function (Request $request) {
            $perMinute = (int) config('keystore.rate_limit_per_minute', 60);

            if ($perMinute < 1) {
                return Limit::none();
            }

            return Limit::perMinute($perMinute)->by($request->ip());
        });
    }
}
