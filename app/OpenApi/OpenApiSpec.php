<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Version-controlled key-value store. Writes append immutable Versions. GET latest, GET as-of a UNIX timestamp, or list the current snapshot.',
    title: 'Versioned Key Store',
)]
#[OA\Server(url: '/', description: 'This host')]
#[OA\Tag(name: 'Objects', description: 'Store, read, and list versioned keys')]
#[OA\Schema(
    schema: 'ErrorBody',
    required: ['error'],
    properties: [
        new OA\Property(
            property: 'error',
            required: ['code', 'message'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'key_not_found'),
                new OA\Property(property: 'message', type: 'string', example: 'No value exists for key mykey at the requested time.'),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'StoredVersion',
    required: ['key', 'value', 'timestamp'],
    properties: [
        new OA\Property(property: 'key', type: 'string', example: 'mykey', pattern: '^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$'),
        new OA\Property(property: 'value', description: 'Stored JSON value (any type, max nesting depth 2, max 8 KiB encoded, max 100 members per object/array).', example: 'value1'),
        new OA\Property(property: 'timestamp', type: 'integer', example: 1440568800, description: 'UNIX seconds (UTC) assigned by the server.'),
    ],
    type: 'object',
)]
#[OA\Post(
    path: '/api/v1/object',
    operationId: 'storeObjects',
    description: 'Accepts a JSON object of 1–10 Key → Value pairs (body ≤ 64 KiB). All pairs share one Timestamp and commit atomically. Distinct times require distinct POSTs.',
    summary: 'Write one or more keys',
    tags: ['Objects'],
    requestBody: new OA\RequestBody(
        required: true,
        description: 'JSON object whose property names are Keys. Example from the spec: {"mykey": "value1"}',
        content: new OA\JsonContent(
            required: [],
            type: 'object',
            example: ['mykey' => 'value1'],
            additionalProperties: true,
            minProperties: 1,
            maxProperties: 10,
        ),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Versions written',
            content: new OA\JsonContent(
                required: ['data'],
                properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/StoredVersion'),
                    ),
                ],
                type: 'object',
            ),
        ),
        new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorBody')),
        new OA\Response(response: 413, description: 'Request body too large', content: new OA\JsonContent(ref: '#/components/schemas/ErrorBody')),
        new OA\Response(response: 429, description: 'Too many requests', content: new OA\JsonContent(ref: '#/components/schemas/ErrorBody')),
        new OA\Response(response: 500, description: 'Write failed after retries', content: new OA\JsonContent(ref: '#/components/schemas/ErrorBody')),
    ],
)]
#[OA\Get(
    path: '/api/v1/object/get_all_records',
    operationId: 'listRecords',
    description: 'Current snapshot (latest Version per Key), cursor-paginated, Keys in ascending order. Walk next_cursor until it is null.',
    summary: 'List currently stored keys',
    tags: ['Objects'],
    parameters: [
        new OA\QueryParameter(
            name: 'limit',
            description: 'Page size. Default 50, max 1000.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 1000, example: 50),
        ),
        new OA\QueryParameter(
            name: 'cursor',
            description: 'Opaque cursor from the previous page next_cursor.',
            required: false,
            schema: new OA\Schema(type: 'string'),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'One page of the current snapshot',
            content: new OA\JsonContent(
                required: ['data', 'next_cursor'],
                properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/StoredVersion'),
                    ),
                    new OA\Property(property: 'next_cursor', type: 'string', nullable: true, example: null),
                ],
                type: 'object',
            ),
        ),
        new OA\Response(response: 400, description: 'Invalid cursor or limit', content: new OA\JsonContent(ref: '#/components/schemas/ErrorBody')),
        new OA\Response(response: 429, description: 'Too many requests', content: new OA\JsonContent(ref: '#/components/schemas/ErrorBody')),
    ],
)]
#[OA\Get(
    path: '/api/v1/object/{key}',
    operationId: 'getObject',
    description: 'Returns the raw JSON Value. Without timestamp: latest. With timestamp: newest Version with Timestamp ≤ the end of that UNIX second. Missing Key is 404, not null.',
    summary: 'Read a key (latest or as-of)',
    tags: ['Objects'],
    parameters: [
        new OA\PathParameter(
            name: 'key',
            description: 'Key. Alphanumeric, underscore, dash; 1–64 chars; cannot be get_all_records.',
            schema: new OA\Schema(type: 'string', example: 'mykey', pattern: '^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$'),
        ),
        new OA\QueryParameter(
            name: 'timestamp',
            description: 'UNIX seconds (UTC). Omit for latest. Future timestamps are legal as-of reads.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 0, example: 1440568980),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Raw stored Value (string, number, object, array, boolean, or JSON null)',
            content: new OA\JsonContent(description: 'The stored JSON value', example: 'value1'),
        ),
        new OA\Response(response: 400, description: 'Invalid key or timestamp', content: new OA\JsonContent(ref: '#/components/schemas/ErrorBody')),
        new OA\Response(response: 404, description: 'No Version for this question', content: new OA\JsonContent(ref: '#/components/schemas/ErrorBody')),
        new OA\Response(response: 429, description: 'Too many requests', content: new OA\JsonContent(ref: '#/components/schemas/ErrorBody')),
    ],
)]
final class OpenApiSpec {}
