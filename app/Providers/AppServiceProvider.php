<?php

namespace App\Providers;

use App\Services\Ai\AiManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiManager::class);
    }

    public function boot(): void
    {
        //
    }
}
