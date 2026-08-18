<?php

declare(strict_types=1);

namespace App\Application\KeyStore;

use App\Domain\KeyStore\Exceptions\InvalidCursor;
use App\Domain\KeyStore\Exceptions\InvalidLimit;
use App\Domain\KeyStore\Key;
use App\Domain\KeyStore\KeyStoreRepository;
use App\Domain\KeyStore\SnapshotPage;

final class ListSnapshot
{
    public function __construct(
        private readonly KeyStoreRepository $repository,
    ) {}

    public function handle(mixed $rawLimit, mixed $rawCursor): SnapshotPage
    {
        $limit = $this->parseLimit($rawLimit);
        $after = $this->parseCursor($rawCursor);

        return $this->repository->listSnapshot($after, $limit);
    }

    private function parseLimit(mixed $raw): int
    {
        $default = (int) config('keystore.list_default_limit', 50);
        $max = (int) config('keystore.list_max_limit', 1000);

        if ($raw === null || $raw === '') {
            return $default;
        }

        if (is_int($raw)) {
            $limit = $raw;
        } elseif (is_string($raw) && preg_match('/^[1-9][0-9]*$/', $raw) === 1) {
            $limit = (int) $raw;
        } else {
            throw new InvalidLimit;
        }

        if ($limit < 1 || $limit > $max) {
            throw new InvalidLimit;
        }

        return $limit;
    }

    private function parseCursor(mixed $raw): ?Key
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_string($raw)) {
            throw new InvalidCursor;
        }

        $padded = strtr($raw, '-_', '+/');
        $pad = strlen($padded) % 4;

        if ($pad > 0) {
            $padded .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($padded, true);

        if ($decoded === false || $decoded === '') {
            throw new InvalidCursor;
        }

        try {
            return Key::parse($decoded);
        } catch (\Throwable) {
            throw new InvalidCursor;
        }
    }
}
