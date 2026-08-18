<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

final class ReservedKey extends DomainException
{
    public function __construct()
    {
        parent::__construct('The key get_all_records is reserved for the list endpoint.');
    }

    public function errorCode(): string
    {
        return 'reserved_key';
    }

    public function status(): int
    {
        return 400;
    }
}
