<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class KeyStoreSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_indexes_match_the_query_shapes(): void
    {
        $this->assertTrue(Schema::hasIndex('key_snapshots', ['key'], 'primary'));
        $this->assertTrue(Schema::hasIndex('key_versions', 'key_versions_as_of_index'));
        $this->assertFalse(Schema::hasIndex('key_versions', 'key_versions_key_recorded_at_index'));
    }
}
