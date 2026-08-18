<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class KeyNotFound extends DomainException
{
    public function __construct(string $key)
    {
        parent::__construct("No value exists for key {$key} at the requested time.");
    }

    public function errorCode(): string
    {
        return 'key_not_found';
    }

    public function status(): int
    {
        return 404;
    }
}
