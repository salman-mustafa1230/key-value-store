<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class PayloadTooLarge extends DomainException
{
    public function __construct(int $maxBytes)
    {
        parent::__construct("Request body must be at most {$maxBytes} bytes.");
    }

    public function errorCode(): string
    {
        return 'payload_too_large';
    }

    public function status(): int
    {
        return 413;
    }
}
