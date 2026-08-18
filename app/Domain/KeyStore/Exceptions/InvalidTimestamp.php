<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class InvalidTimestamp extends DomainException
{
    public function __construct()
    {
        parent::__construct('timestamp must be a non-negative UNIX second.');
    }

    public function errorCode(): string
    {
        return 'invalid_timestamp';
    }

    public function status(): int
    {
        return 400;
    }
}
