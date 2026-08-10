<?php

namespace App\Providers;

use App\Services\Ai\AiManager;
use App\Services\Tools\ToolManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiManager::class);
        $this->app->singleton(ToolManager::class);
    }

    public function boot(): void
    {
        //
    }
}
