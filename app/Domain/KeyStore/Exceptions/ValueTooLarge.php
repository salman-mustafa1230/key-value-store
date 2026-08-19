<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class ValueTooLarge extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'value_too_large';
    }

    public function status(): int
    {
        return 400;
    }
}
