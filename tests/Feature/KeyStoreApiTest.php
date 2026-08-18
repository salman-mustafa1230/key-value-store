<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\KeyStore\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KeyStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_spec_example_latest_and_as_of(): void
    {
        $this->travelTo(CarbonImmutable::createFromTimestampUTC(1440568800)); // 6:00pm sample

        $first = $this->postJson('/api/v1/object', ['mykey' => 'value1']);
        $first->assertCreated()
            ->assertJsonPath('data.0.key', 'mykey')
            ->assertJsonPath('data.0.value', 'value1')
            ->assertJsonPath('data.0.timestamp', 1440568800);

        $this->assertSame('value1', $this->getJson('/api/v1/object/mykey')->json());

        $this->travelTo(CarbonImmutable::createFromTimestampUTC(1440569100)); // 6:05pm

        $this->postJson('/api/v1/object', ['mykey' => 'value2'])
            ->assertCreated();

        $this->assertSame('value2', $this->getJson('/api/v1/object/mykey')->json());

        $this->assertSame('value1', $this->getJson('/api/v1/object/mykey?timestamp=1440568980')->json());
    }

    public function test_get_all_records_is_the_current_snapshot(): void
    {
        $this->travelTo(CarbonImmutable::createFromTimestampUTC(1_700_000_000));
        $this->postJson('/api/v1/object', ['alpha' => 1, 'beta' => 2])->assertCreated();

        $this->travelTo(CarbonImmutable::createFromTimestampUTC(1_700_000_100));
        $this->postJson('/api/v1/object', ['alpha' => 9])->assertCreated();

        $this->getJson('/api/v1/object/get_all_records')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'alpha')
            ->assertJsonPath('data.0.value', 9)
            ->assertJsonPath('data.0.timestamp', 1_700_000_100)
            ->assertJsonPath('data.1.key', 'beta')
            ->assertJsonPath('data.1.value', 2)
            ->assertJsonPath('data.1.timestamp', 1_700_000_000)
            ->assertJsonPath('next_cursor', null);
    }

    public function test_cursor_pagination(): void
    {
        $this->postJson('/api/v1/object', [
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ])->assertCreated();

        $page1 = $this->getJson('/api/v1/object/get_all_records?limit=2')
            ->assertOk()
            ->json();

        $this->assertCount(2, $page1['data']);
        $this->assertNotNull($page1['next_cursor']);

        $page2 = $this->getJson('/api/v1/object/get_all_records?limit=2&cursor='.$page1['next_cursor'])
            ->assertOk()
            ->json();

        $this->assertCount(1, $page2['data']);
        $this->assertNull($page2['next_cursor']);
        $this->assertSame('c', $page2['data'][0]['key']);
    }

    public function test_missing_key_is_not_found_not_null(): void
    {
        $this->postJson('/api/v1/object', ['present' => null])->assertCreated();

        $response = $this->get('/api/v1/object/present', ['Accept' => 'application/json']);
        $response->assertOk();
        $this->assertSame('null', $response->getContent());

        $this->getJson('/api/v1/object/absent')
            ->assertNotFound()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('error.code', 'key_not_found');
    }

    public function test_as_of_before_first_version_is_not_found(): void
    {
        $this->travelTo(CarbonImmutable::createFromTimestampUTC(2_000_000_000));
        $this->postJson('/api/v1/object', ['k' => 'v'])->assertCreated();

        $this->getJson('/api/v1/object/k?timestamp=1')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'key_not_found');
    }

    public function test_invalid_timestamp_is_400(): void
    {
        $this->postJson('/api/v1/object', ['k' => 'v'])->assertCreated();

        $this->getJson('/api/v1/object/k?timestamp=nope')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'invalid_timestamp');

        $this->getJson('/api/v1/object/k?timestamp=-1')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'invalid_timestamp');
    }

    public function test_reserved_key_is_rejected(): void
    {
        $this->postJson('/api/v1/object', ['get_all_records' => 'no'])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'reserved_key');
    }

    public function test_batch_cap_and_all_or_nothing(): void
    {
        $tooMany = [];
        for ($i = 0; $i < 11; $i++) {
            $tooMany['k'.$i] = $i;
        }

        $this->postJson('/api/v1/object', $tooMany)
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'batch_too_large');

        $this->getJson('/api/v1/object/k0')->assertNotFound();
    }

    public function test_rejects_too_deep_json(): void
    {
        $this->postJson('/api/v1/object', [
            'mykey' => ['a' => ['b' => ['c' => 1]]],
        ])->assertStatus(400)
            ->assertJsonPath('error.code', 'value_too_deep');
    }

    public function test_dash_keys_work_in_the_path(): void
    {
        $this->postJson('/api/v1/object', ['my-key' => 'ok'])->assertCreated();

        $this->assertSame('ok', $this->getJson('/api/v1/object/my-key')->json());
    }

    public function test_same_unix_second_keeps_both_versions(): void
    {
        $clock = new class implements Clock
        {
            private int $micros = 0;

            public function now(): CarbonImmutable
            {
                $this->micros++;

                return CarbonImmutable::createFromTimestampUTC(1_700_000_000)
                    ->addMicroseconds($this->micros);
            }
        };

        $this->app->instance(Clock::class, $clock);

        $this->postJson('/api/v1/object', ['k' => 'first'])->assertCreated();
        $this->postJson('/api/v1/object', ['k' => 'second'])->assertCreated();

        $this->assertSame('second', $this->getJson('/api/v1/object/k')->json());

        $this->assertSame('second', $this->getJson('/api/v1/object/k?timestamp=1700000000')->json());
    }

    public function test_array_body_is_rejected(): void
    {
        $this->call('POST', '/api/v1/object', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode(['not', 'an', 'object']))
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'invalid_payload');
    }
}
