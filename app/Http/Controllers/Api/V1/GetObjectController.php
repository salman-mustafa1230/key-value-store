<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\KeyStore\ReadObject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetObjectController extends Controller
{
    public function __invoke(Request $request, string $key, ReadObject $read): JsonResponse
    {
        $version = $request->query->has('timestamp')
            ? $read->asOf($key, $request->query('timestamp'))
            : $read->latest($key);

        // Symfony JsonResponse coalesces PHP null to {}, which would collide with a stored null Value.
        return JsonResponse::fromJsonString(
            json_encode($version->value->json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }
}
