<?php

declare(strict_types=1);

namespace App\Application\KeyStore;

use App\Domain\KeyStore\Exceptions\KeyNotFound;
use App\Domain\KeyStore\Key;
use App\Domain\KeyStore\KeyStoreRepository;
use App\Domain\KeyStore\Timestamp;
use App\Domain\KeyStore\Version;

final class ReadObject
{
    public function __construct(
        private readonly KeyStoreRepository $repository,
    ) {}

    public function latest(string $rawKey): Version
    {
        $key = Key::parse($rawKey);
        $version = $this->repository->latest($key);

        if ($version === null) {
            throw new KeyNotFound($key->value);
        }

        return $version;
    }

    public function asOf(string $rawKey, mixed $rawTimestamp): Version
    {
        $key = Key::parse($rawKey);
        $timestamp = Timestamp::fromUnixSeconds($rawTimestamp);
        $version = $this->repository->asOf($key, $timestamp);

        if ($version === null) {
            throw new KeyNotFound($key->value);
        }

        return $version;
    }
}
