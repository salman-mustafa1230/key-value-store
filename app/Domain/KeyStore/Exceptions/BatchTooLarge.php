<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class BatchTooLarge extends DomainException
{
    public function __construct(int $max)
    {
        parent::__construct("A single POST may write at most {$max} keys.");
    }

    public function errorCode(): string
    {
        return 'batch_too_large';
    }

    public function status(): int
    {
        return 400;
    }
}
