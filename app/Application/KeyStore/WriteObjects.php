<?php

declare(strict_types=1);

namespace App\Application\KeyStore;

use App\Domain\KeyStore\Clock;
use App\Domain\KeyStore\Exceptions\BatchTooLarge;
use App\Domain\KeyStore\Exceptions\InvalidPayload;
use App\Domain\KeyStore\Key;
use App\Domain\KeyStore\KeyStoreRepository;
use App\Domain\KeyStore\Value;
use App\Domain\KeyStore\Version;

final class WriteObjects
{
    public function __construct(
        private readonly KeyStoreRepository $repository,
        private readonly Clock $clock,
    ) {}

    /**
     * @param  array<string, mixed>  $pairs
     * @return list<Version>
     */
    public function handle(array $pairs): array
    {
        if ($pairs === []) {
            throw new InvalidPayload('At least one key is required.');
        }

        $max = (int) config('keystore.max_keys_per_write', 10);

        if (count($pairs) > $max) {
            throw new BatchTooLarge($max);
        }

        $recordedAt = $this->clock->now();
        $versions = [];

        foreach ($pairs as $rawKey => $rawValue) {
            $versions[] = new Version(
                Key::parse((string) $rawKey),
                Value::fromWrite(
                    $rawValue,
                    (int) config('keystore.max_value_bytes', Value::MAX_ENCODED_BYTES),
                    (int) config('keystore.max_value_breadth', Value::MAX_BREADTH),
                ),
                $recordedAt,
            );
        }

        $this->repository->append($versions);

        return $versions;
    }
}
