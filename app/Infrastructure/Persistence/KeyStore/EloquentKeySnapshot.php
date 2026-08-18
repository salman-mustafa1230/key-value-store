<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\KeyStore;

use App\Infrastructure\Persistence\JsonDocument;
use Illuminate\Database\Eloquent\Model;

final class EloquentKeySnapshot extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'key_snapshots';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => JsonDocument::class,
            'recorded_at' => 'datetime',
        ];
    }
}
