<?php

declare(strict_types=1);

/**
 * API v1 route map.
 *
 * Add a new feature by dropping a file in routes/api/v1/ and it is loaded
 * automatically. A future v2 is a sibling folder plus a prefix group in bootstrap/app.php.
 */
foreach (glob(__DIR__.'/v1/*.php') ?: [] as $routeFile) {
    require $routeFile;
}
