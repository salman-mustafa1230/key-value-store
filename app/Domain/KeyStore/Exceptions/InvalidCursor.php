<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class InvalidCursor extends DomainException
{
    public function __construct()
    {
        parent::__construct('cursor is invalid.');
    }

    public function errorCode(): string
    {
        return 'invalid_cursor';
    }

    public function status(): int
    {
        return 400;
    }
}
