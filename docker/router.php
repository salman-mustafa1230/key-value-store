<?php

declare(strict_types=1);

/**
 * php -S static-file gate. Only files inside public/ are served as-is.
 * Concatenating the raw URI (e.g. /../composer.json) is not enough — resolve
 * and check the prefix so path traversal cannot leave the docroot.
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? urldecode($uri) : '/';

if ($uri === '' || ! str_starts_with($uri, '/')) {
    $uri = '/'.$uri;
}

$public = realpath(__DIR__.'/../public');

if ($public !== false && $uri !== '/') {
    $candidate = realpath($public.$uri);

    if (
        is_string($candidate)
        && is_file($candidate)
        && str_starts_with($candidate, $public.DIRECTORY_SEPARATOR)
    ) {
        return false;
    }
}

require ($public !== false ? $public : __DIR__.'/../public').'/index.php';
