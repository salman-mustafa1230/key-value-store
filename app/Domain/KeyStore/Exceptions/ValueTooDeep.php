<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class ValueTooDeep extends DomainException
{
    public function __construct(int $depth)
    {
        parent::__construct("Value nesting depth {$depth} exceeds the maximum of 2.");
    }

    public function errorCode(): string
    {
        return 'value_too_deep';
    }

    public function status(): int
    {
        return 400;
    }
}
