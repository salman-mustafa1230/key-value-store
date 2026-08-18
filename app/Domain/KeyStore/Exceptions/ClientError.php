<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

interface ClientError
{
    public function errorCode(): string;

    public function status(): int;
}
