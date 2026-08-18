<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Events\ConnectionEstablished;

final class ConfigurePostgresSession
{
    public function handle(ConnectionEstablished $event): void
    {
        if ($event->connection->getDriverName() !== 'pgsql') {
            return;
        }

        $session = config('database.connections.pgsql.session', []);

        $statements = [
            'statement_timeout' => (int) ($session['statement_timeout_ms'] ?? 0),
            'idle_in_transaction_session_timeout' => (int) ($session['idle_in_transaction_timeout_ms'] ?? 0),
            'lock_timeout' => (int) ($session['lock_timeout_ms'] ?? 0),
        ];

        foreach ($statements as $setting => $milliseconds) {
            if ($milliseconds <= 0) {
                continue;
            }

            $event->connection->statement("set {$setting} = {$milliseconds}");
        }
    }
}
