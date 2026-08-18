<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\KeyStore;

use App\Domain\KeyStore\Exceptions\PersistenceFailed;
use App\Domain\KeyStore\Key;
use App\Domain\KeyStore\KeyStoreRepository;
use App\Domain\KeyStore\SnapshotPage;
use App\Domain\KeyStore\Timestamp;
use App\Domain\KeyStore\Value;
use App\Domain\KeyStore\Version;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class PostgresKeyStoreRepository implements KeyStoreRepository
{
    private const MAX_ATTEMPTS = 3;

    public function append(array $versions): void
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $this->appendOnce($versions);

                return;
            } catch (Throwable $e) {
                if ($attempt >= self::MAX_ATTEMPTS || ! $this->isTransient($e)) {
                    throw new PersistenceFailed('Failed to persist versions after retries.', $e);
                }

                usleep(50_000 * (2 ** ($attempt - 1)));
            }
        }
    }

    /**
     * @param  list<Version>  $versions
     */
    private function appendOnce(array $versions): void
    {
        DB::transaction(function () use ($versions): void {
            $keys = [];

            foreach ($versions as $version) {
                $keys[$version->key->value] = $version->key->value;
            }

            sort($keys);

            foreach ($keys as $key) {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [$key]);
            }

            foreach ($versions as $version) {
                EloquentKeyVersion::query()->create([
                    'key' => $version->key->value,
                    'value' => $version->value->json,
                    'recorded_at' => $version->recordedAt,
                ]);

                EloquentKeySnapshot::query()->updateOrCreate(
                    ['key' => $version->key->value],
                    [
                        'value' => $version->value->json,
                        'recorded_at' => $version->recordedAt,
                    ],
                );
            }
        });
    }

    public function latest(Key $key): ?Version
    {
        $row = EloquentKeySnapshot::query()->where('key', $key->value)->first();

        return $row === null ? null : $this->toVersion($row->key, $row->value, $row->recorded_at);
    }

    public function asOf(Key $key, Timestamp $timestamp): ?Version
    {
        $row = EloquentKeyVersion::query()
            ->where('key', $key->value)
            ->where('recorded_at', '<=', $timestamp->endOfUnixSecond())
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        return $row === null ? null : $this->toVersion($row->key, $row->value, $row->recorded_at);
    }

    public function listSnapshot(?Key $after, int $limit): SnapshotPage
    {
        $query = EloquentKeySnapshot::query()->orderBy('key');

        if ($after !== null) {
            $query->where('key', '>', $after->value);
        }

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $page = $hasMore ? $rows->take($limit) : $rows;

        $items = $page->map(
            fn (EloquentKeySnapshot $row): Version => $this->toVersion($row->key, $row->value, $row->recorded_at),
        )->values()->all();

        $nextCursor = null;

        if ($hasMore && $items !== []) {
            $last = $items[array_key_last($items)];
            $nextCursor = $this->encodeCursor($last->key);
        }

        return new SnapshotPage($items, $nextCursor);
    }

    private function encodeCursor(Key $key): string
    {
        return rtrim(strtr(base64_encode($key->value), '+/', '-_'), '=');
    }

    private function toVersion(string $key, mixed $value, mixed $recordedAt): Version
    {
        $instant = CarbonImmutable::parse($recordedAt)->utc();

        return new Version(
            Key::parse($key),
            Value::fromJson($value),
            $instant,
        );
    }

    private function isTransient(Throwable $e): bool
    {
        while ($e !== null) {
            if ($e instanceof QueryException) {
                $state = $e->errorInfo[0] ?? (string) $e->getCode();

                if (in_array($state, ['40001', '40P01', '23505'], true)) {
                    return true;
                }
            }

            $e = $e->getPrevious();
        }

        return false;
    }
}
