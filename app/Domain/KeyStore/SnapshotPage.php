<?php

declare(strict_types=1);

namespace App\Domain\KeyStore;

final readonly class SnapshotPage
{
    /**
     * @param  list<Version>  $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
    ) {}
}
