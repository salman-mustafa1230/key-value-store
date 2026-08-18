<?php

declare(strict_types=1);

namespace App\Domain\KeyStore;

use App\Domain\KeyStore\Exceptions\InvalidTimestamp;
use Carbon\CarbonImmutable;

final readonly class Timestamp
{
    private function __construct(public CarbonImmutable $instant) {}

    public static function fromNow(CarbonImmutable $now): self
    {
        return new self($now.utc());
    }

    public static function fromUnixSeconds(mixed $raw): self
    {
        if (is_int($raw)) {
            $seconds = $raw;
        } elseif (is_string($raw) && preg_match('/^(0|[1-9][0-9]*)$/', $raw) === 1) {
            $seconds = (int) $raw;
        } else {
            throw new InvalidTimestamp;
        }

        if ($seconds < 0) {
            throw new InvalidTimestamp;
        }

        return new self(CarbonImmutable::createFromTimestampUTC($seconds));
    }

    public function unixSeconds(): int
    {
        return $this->instant->getTimestamp();
    }

    public function endOfUnixSecond(): CarbonImmutable
    {
        return $this->instant->utc()->endOfSecond();
    }
}
