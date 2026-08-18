<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class InvalidPayload extends DomainException
{
    public function __construct(string $message = 'Request body must be a JSON object of Key to Value pairs.')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'invalid_payload';
    }

    public function status(): int
    {
        return 400;
    }
}
