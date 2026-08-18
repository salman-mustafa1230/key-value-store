<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException implements ClientError
{
    abstract public function errorCode(): string;

    abstract public function status(): int;
}
