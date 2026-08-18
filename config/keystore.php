<?php

declare(strict_types=1);

return [
    'max_keys_per_write' => (int) env('KEYSTORE_MAX_KEYS_PER_WRITE', 10),
    'list_default_limit' => (int) env('KEYSTORE_LIST_DEFAULT_LIMIT', 50),
    'list_max_limit' => (int) env('KEYSTORE_LIST_MAX_LIMIT', 1000),
];
