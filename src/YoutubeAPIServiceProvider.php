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

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'youtube-api-views');
        $this->loadRoutes();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/youtube-api.php', 'laravel-youtube-api');
    }

    private function loadRoutes() {
        $apiMiddleware = ['api'];

        if(config('youtube-api.enable_auth', false)) {
            $apiMiddleware[] = 'auth:api';
        }

        if(config('youtube-api.enable_throttle') !== null) {
            $apiMiddleware[] = 'throttle:' . config('youtube-api.enable_throttle');
        }

        Route::prefix('api/' . config('youtube-api.route_prefix', 'ytconverter'))
            ->middleware($apiMiddleware)
            ->namespace( 'MichaelBelgium\YoutubeAPI\Controllers')
            ->group(__DIR__.'/../routes/api.php');

        Route::prefix(config('youtube-api.route_prefix', 'ytconverter'))
            ->middleware('web')
            ->namespace('MichaelBelgium\YoutubeAPI\Controllers')
            ->group(__DIR__.'/../routes/web.php');
        
        
    }
}
