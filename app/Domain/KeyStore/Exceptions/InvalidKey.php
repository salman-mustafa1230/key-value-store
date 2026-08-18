<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class InvalidKey extends DomainException
{
    public function __construct(string $key)
    {
        parent::__construct('Key must be 1–64 characters, start with alphanumeric, and contain only A–Z, a–z, 0–9, underscore, or dash.');
    }

    public function errorCode(): string
    {
        return 'invalid_key';
    }

    public function status(): int
    {
        return 400;
    }
}
