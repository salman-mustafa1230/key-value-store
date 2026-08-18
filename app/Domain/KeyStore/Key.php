<?php

declare(strict_types=1);

namespace App\Domain\KeyStore;

use App\Domain\KeyStore\Exceptions\InvalidKey;
use App\Domain\KeyStore\Exceptions\ReservedKey;

final readonly class Key
{
    public const PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/';

    public const RESERVED = 'get_all_records';

    private function __construct(public string $value) {}

    public static function parse(string $raw): self
    {
        if ($raw === self::RESERVED) {
            throw new ReservedKey;
        }

        if (preg_match(self::PATTERN, $raw) !== 1) {
            throw new InvalidKey($raw);
        }

        return new self($raw);
    }
}
