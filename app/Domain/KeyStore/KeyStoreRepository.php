<?php

declare(strict_types=1);

namespace App\Domain\KeyStore;

interface KeyStoreRepository
{
    /**
     * Persist every Version in one transaction and update the current snapshot.
     *
     * @param  list<Version>  $versions
     */
    public function append(array $versions): void;

    public function latest(Key $key): ?Version;

    public function asOf(Key $key, Timestamp $timestamp): ?Version;

    public function listSnapshot(?Key $after, int $limit): SnapshotPage;
}
