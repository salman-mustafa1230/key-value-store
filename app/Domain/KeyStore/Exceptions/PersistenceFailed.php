<?php

declare(strict_types=1);

namespace App\Domain\KeyStore\Exceptions;

use RuntimeException;
use Throwable;

final class PersistenceFailed extends RuntimeException
{
    public function __construct(string $message = 'Failed to persist versions after retries.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
