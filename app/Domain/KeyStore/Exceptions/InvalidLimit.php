<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class InvalidLimit extends DomainException
{
    public function __construct()
    {
        parent::__construct('limit must be an integer between 1 and the configured maximum.');
    }

    public function errorCode(): string
    {
        return 'invalid_limit';
    }

    public function status(): int
    {
        return 400;
    }
}
