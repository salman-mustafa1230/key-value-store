<?php

declare(strict_types=1);

namespace App\Domain\KeyStore;

use App\Domain\KeyStore\Exceptions\ValueTooDeep;

final readonly class Value
{
    public const MAX_DEPTH = 2;

    private function __construct(public mixed $json) {}

    public static function fromJson(mixed $json): self
    {
        $depth = self::depth($json);

        if ($depth > self::MAX_DEPTH) {
            throw new ValueTooDeep($depth);
        }

        return new self($json);
    }

    public static function depth(mixed $value): int
    {
        if (is_object($value)) {
            $children = get_object_vars($value);
            $maxChild = 0;

            foreach ($children as $child) {
                $maxChild = max($maxChild, self::depth($child));
            }

            return 1 + $maxChild;
        }

        if (is_array($value)) {
            $maxChild = 0;

            foreach ($value as $child) {
                $maxChild = max($maxChild, self::depth($child));
            }

            return 1 + $maxChild;
        }

        return 0;
    }
}
