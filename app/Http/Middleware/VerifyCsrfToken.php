<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // OAuth endpoints (API calls from external servers)
        'oauth/token',
        'oauth/revoke',
        'oauth/introspect',

        // API endpoints (protected by Bearer tokens instead of CSRF)
        'api/*',
    ];
}
