<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\KeyStore\Clock;
use App\Domain\KeyStore\KeyStoreRepository;
use App\Domain\KeyStore\SystemClock;
use App\Infrastructure\Persistence\ConfigurePostgresSession;
use App\Infrastructure\Persistence\KeyStore\PostgresKeyStoreRepository;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
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
    }
}
