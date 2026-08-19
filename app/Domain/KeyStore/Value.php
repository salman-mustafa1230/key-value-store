<?php

declare(strict_types=1);

namespace App\Domain\KeyStore;

use App\Domain\KeyStore\Exceptions\ValueTooDeep;
use App\Domain\KeyStore\Exceptions\ValueTooLarge;

final readonly class Value
{
    public const MAX_DEPTH = 2;

    public const MAX_ENCODED_BYTES = 8192;

    public const MAX_BREADTH = 100;

    private function __construct(public mixed $json) {}

    public static function fromJson(mixed $json): self
    {
        $depth = self::depth($json);

        if ($depth > self::MAX_DEPTH) {
            throw new ValueTooDeep($depth);
        }

        return new self($json);
    }

    /**
     * Write path only. Reads must not apply these caps or stored Values would 500 after a limit change.
     */
    public static function fromWrite(mixed $json, int $maxEncodedBytes, int $maxBreadth): self
    {
        $value = self::fromJson($json);

        $breadth = self::breadth($json);

        if ($breadth > $maxBreadth) {
            throw new ValueTooLarge("Value has {$breadth} members; the maximum is {$maxBreadth}.");
        }

        $encoded = json_encode($json, JSON_THROW_ON_ERROR);

        if (strlen($encoded) > $maxEncodedBytes) {
            throw new ValueTooLarge('Value exceeds the maximum encoded size of '.$maxEncodedBytes.' bytes.');
        }

        return $value;
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

    public static function breadth(mixed $value): int
    {
        if (is_object($value)) {
            $children = get_object_vars($value);
            $max = count($children);

            foreach ($children as $child) {
                $max = max($max, self::breadth($child));
            }

            return $max;
        }

        if (is_array($value)) {
            $max = count($value);

            foreach ($value as $child) {
                $max = max($max, self::breadth($child));
            }

            return $max;
        }

        return 0;
    }
}
