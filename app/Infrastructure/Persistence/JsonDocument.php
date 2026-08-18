<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<mixed, mixed>
 */
final class JsonDocument implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            $value = json_encode($value, JSON_THROW_ON_ERROR);
        }

        return json_decode($value, false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [$key => json_encode($value, JSON_THROW_ON_ERROR)];
    }
}
