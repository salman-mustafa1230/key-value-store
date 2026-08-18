<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class StoreObjectBatchQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_ten_pairs_do_not_issue_a_query_per_key(): void
    {
        $pairs = [];

        for ($i = 0; $i < 10; $i++) {
            $pairs['batch'.$i] = $i;
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->postJson('/api/v1/object', $pairs)->assertCreated();

        $writeQueries = array_values(array_filter(
            DB::getQueryLog(),
            fn (array $query): bool => $this->isWritePathQuery($query['query']),
        ));

        $this->assertLessThanOrEqual(
            4,
            count($writeQueries),
            'Expected one lock query, one versions insert, and one snapshot upsert; got: '.json_encode($writeQueries),
        );
    }

    private function isWritePathQuery(string $sql): bool
    {
        $normalized = strtolower($sql);

        return str_contains($normalized, 'key_versions')
            || str_contains($normalized, 'key_snapshots')
            || str_contains($normalized, 'pg_advisory_xact_lock');
    }
}
