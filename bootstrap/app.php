<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Configurer les exceptions CSRF
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Configuration du rate limiting
        $middleware->throttleApi('60,1'); // 60 requêtes par minute pour l'API

        // Redis désactivé - utiliser le rate limiting sans Redis
        // $middleware->throttleWithRedis(); // Utiliser Redis si disponible

        // Alias de middleware Spatie Permission
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'security.check' => \App\Http\Middleware\SecurityCheck::class,
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
            'ensure.2fa' => \App\Http\Middleware\EnsureTwoFactorEnabled::class,
            'verify.email.session' => \App\Http\Middleware\VerifyEmailInSession::class,
        ]);

        // Middleware global de sécurité
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Middleware de maintenance (appliqué au groupe web)
        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        // Middleware de détection d'attaques (facultatif - à activer avec précaution)
        // $middleware->append(\App\Http\Middleware\SecurityCheck::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('sessions:clean-expired')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('kyc-identities:notify-expired')->daily()->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
