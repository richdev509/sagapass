<?php

namespace App\Providers;

use App\Services\FaceVerification\FaceVerificationScriptClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FaceVerificationScriptClient::class, function () {
            $config = config('faceverification');

            return new FaceVerificationScriptClient(
                scriptPath: (string) $config['script_path'],
                modelCacheDir: (string) $config['model_cache_dir'],
                timeoutSeconds: (int) $config['timeout_seconds'],
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
