<?php

declare(strict_types=1);

namespace App\Domain\KeyStore;

use Carbon\CarbonImmutable;

interface Clock
{
    public function now(): CarbonImmutable;
}
