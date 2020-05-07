<?php

namespace MichaelBelgium\YoutubeAPI;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class YoutubeAPIServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/youtube-api.php' => config_path('youtube-api.php')
            ], 'youtube-api-config');
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/youtube-api.php', 'laravel-youtube-api');
    }
}
