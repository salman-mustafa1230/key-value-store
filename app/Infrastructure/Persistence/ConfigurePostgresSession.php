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

        $configs = [
            'TimeZone' => (string) ($session['timezone'] ?? 'UTC'),
            'search_path' => (string) ($session['search_path'] ?? 'public'),
            'default_transaction_isolation' => (string) ($session['isolation_level'] ?? 'read committed'),
            'statement_timeout' => (string) ((int) ($session['statement_timeout_ms'] ?? 0)),
            'lock_timeout' => (string) ((int) ($session['lock_timeout_ms'] ?? 0)),
            'idle_in_transaction_session_timeout' => (string) ((int) ($session['idle_in_transaction_timeout_ms'] ?? 0)),
        ];

        $selects = [];
        $bindings = [];

        foreach ($configs as $setting => $value) {
            if ($value === '' || $value === '0') {
                continue;
            }

            $selects[] = 'set_config(?, ?, false)';
            $bindings[] = $setting;
            $bindings[] = $value;
        }

        if ($selects === []) {
            return;
        }

        $event->connection->select('select '.implode(', ', $selects), $bindings);
    }
}
