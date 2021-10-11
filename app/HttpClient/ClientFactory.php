<?php

namespace App\HttpClient;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;

use function tap;

/**
 * @mixin PendingRequest
 */
class ClientFactory
{
    private DelaySending $delaySending;
    private CacheMiddleware $cacheMiddleware;

    public function __construct(DelaySending $delaySending, CacheMiddleware $cacheMiddleware)
    {
        $this->delaySending = $delaySending;
        $this->cacheMiddleware = $cacheMiddleware;
    }

    public function __call($method, $parameters)
    {
        return tap($this->noCache(), function (PendingRequest $request) {
            $request->withMiddleware($this->cacheMiddleware);
        })->{$method}(...$parameters);
    }

    public function noCache(): PendingRequest
    {
        return (new Factory())->beforeSending($this->delaySending);
    }
}
