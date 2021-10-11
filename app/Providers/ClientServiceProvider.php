<?php

namespace App\Providers;

use App\HttpClient\CacheMiddleware;
use App\HttpClient\ClientFactory;
use App\HttpClient\DelaySending;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ClientFactory::class);

        $this->app->singleton(DelaySending::class, function () {
            return new DelaySending(Cache::store('array'));
        });

        $this->app->singleton(CacheMiddleware::class, function () {
            return new CacheMiddleware();
        });
    }
}
