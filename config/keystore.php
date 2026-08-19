<?php

declare(strict_types=1);

return [
    'max_keys_per_write' => (int) env('KEYSTORE_MAX_KEYS_PER_WRITE', 10),
    'list_default_limit' => (int) env('KEYSTORE_LIST_DEFAULT_LIMIT', 50),
    'list_max_limit' => (int) env('KEYSTORE_LIST_MAX_LIMIT', 1000),
    'max_body_bytes' => (int) env('KEYSTORE_MAX_BODY_BYTES', 65_536),
    'max_value_bytes' => (int) env('KEYSTORE_MAX_VALUE_BYTES', 8192),
    'max_value_breadth' => (int) env('KEYSTORE_MAX_VALUE_BREADTH', 100),
    // 0 disables the limiter (PHPUnit). Production default is 60/minute/IP.
    'rate_limit_per_minute' => (int) env('KEYSTORE_RATE_LIMIT_PER_MINUTE', 60),
];
