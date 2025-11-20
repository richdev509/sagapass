<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            // Si l'URL commence par /admin, rediriger vers admin login
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            // Sinon rediriger vers citizen login
            return route('login');
        }

        return null;
    }
}
