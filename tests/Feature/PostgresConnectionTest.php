<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PostgresConnectionTest extends TestCase
{
    public function test_connection_budget_is_fifteen(): void
    {
        $this->assertSame(15, config('database.connections.pgsql.pool.max'));
        $this->assertFalse(config('database.connections.pgsql.options')[\PDO::ATTR_PERSISTENT] ?? true);
    }

    public function test_session_uses_production_postgres_defaults(): void
    {
        $this->assertSame('key-value-store', $this->pgSetting('application_name'));
        $this->assertSame('UTC', $this->pgSetting('TimeZone'));
        $this->assertSame('read committed', strtolower($this->pgSetting('transaction_isolation')));
        $this->assertSame('15000', $this->pgSettingMs('statement_timeout'));
        $this->assertSame('30000', $this->pgSettingMs('idle_in_transaction_session_timeout'));
        $this->assertSame('5000', $this->pgSettingMs('lock_timeout'));
    }

    private function pgSetting(string $name): string
    {
        return (string) DB::selectOne('select current_setting(?) as v', [$name])->v;
    }

    private function pgSettingMs(string $name): string
    {
        return (string) DB::selectOne(
            'select setting as v from pg_settings where name = ?',
            [$name],
        )->v;
    }
}
