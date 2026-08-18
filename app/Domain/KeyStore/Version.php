<?php

declare(strict_types=1);

namespace App\Domain\KeyStore;

use Carbon\CarbonImmutable;

final readonly class Version
{
    public function __construct(
        public Key $key,
        public Value $value,
        public CarbonImmutable $recordedAt,
    ) {}

    public function unixSeconds(): int
    {
        return $this->recordedAt->getTimestamp();
    }
}
