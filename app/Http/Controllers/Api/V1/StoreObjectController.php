<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\KeyStore\WriteObjects;
use App\Domain\KeyStore\Exceptions\InvalidPayload;
use App\Domain\KeyStore\Version;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StoreObjectController extends Controller
{
    public function __invoke(Request $request, WriteObjects $write): JsonResponse
    {
        $decoded = json_decode($request->getContent());

        if (! is_object($decoded)) {
            throw new InvalidPayload;
        }

        $pairs = get_object_vars($decoded);

        $versions = $write->handle($pairs);

        return response()->json([
            'data' => array_map(fn (Version $version): array => [
                'key' => $version->key->value,
                'value' => $version->value->json,
                'timestamp' => $version->unixSeconds(),
            ], $versions),
        ], 201);
    }
}
