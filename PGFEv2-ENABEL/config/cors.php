<?php

/**
 * Normalize an origin URL for CORS matching (scheme://host[:port], no trailing slash).
 */
$normalizeOrigin = static function (?string $url): ?string {
    if ($url === null) {
        return null;
    }

    $url = trim($url);
    if ($url === '') {
        return null;
    }

    $parts = parse_url($url);
    if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return rtrim($url, '/');
    }

    $origin = $parts['scheme'].'://'.$parts['host'];
    if (! empty($parts['port'])) {
        $origin .= ':'.$parts['port'];
    }

    return $origin;
};

/**
 * Build localhost <-> 127.0.0.1 twin so a slightly wrong FRONTEND_URL still matches.
 */
$originTwins = static function (?string $origin) use ($normalizeOrigin): array {
    $origin = $normalizeOrigin($origin);
    if ($origin === null) {
        return [];
    }

    $twins = [$origin];
    if (str_contains($origin, '://localhost')) {
        $twins[] = str_replace('://localhost', '://127.0.0.1', $origin);
    } elseif (str_contains($origin, '://127.0.0.1')) {
        $twins[] = str_replace('://127.0.0.1', '://localhost', $origin);
    }

    return $twins;
};

$frontendUrl = $normalizeOrigin(env('FRONTEND_URL', 'http://127.0.0.1:5173'));

$explicitOrigins = array_values(array_unique(array_filter(array_merge(
    $originTwins($frontendUrl),
    [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:4173',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
        'http://127.0.0.1:4173',
        'https://apischool.capslockdev.com',
        'https://rafiki.capslockdev.com',
    ]
))));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Local Vite (see PGFEv2-ENABEL-FRONT/vite.config.ts) serves on
    | http://127.0.0.1:5173 — set FRONTEND_URL to that origin.
    | Patterns below also cover any localhost / 127.0.0.1 Vite port.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Explicit origins required when supports_credentials = true (no wildcard *).
    'allowed_origins' => $explicitOrigins,

    'allowed_origins_patterns' => [
        // Any local Vite / preview port (5173, 5174, 4173, …)
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
        '#^https://([a-z0-9-]+\.)?capslockdev\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // Cache preflight a bit to reduce OPTIONS chatter in dev
    'max_age' => 7200,

    // Front uses Bearer tokens with withCredentials: false, but keeping credentials
    // enabled stays compatible if Sanctum cookie/CSRF flows are re-enabled later.
    // With credentials true, Access-Control-Allow-Origin must echo a concrete origin.
    'supports_credentials' => true,

];
