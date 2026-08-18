<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\KeyStore\ListSnapshot;
use App\Domain\KeyStore\Version;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListRecordsController extends Controller
{
    public function __invoke(Request $request, ListSnapshot $list): JsonResponse
    {
        $page = $list->handle($request->query('limit'), $request->query('cursor'));

        return response()->json([
            'data' => array_map(fn (Version $version): array => [
                'key' => $version->key->value,
                'value' => $version->value->json,
            ], $page->items),
            'next_cursor' => $page->nextCursor,
        ]);
    }
}
