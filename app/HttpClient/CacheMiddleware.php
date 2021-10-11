<?php

namespace App\HttpClient;

use Closure;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\RequestInterface;

class CacheMiddleware
{
    /**
     * @var null
     */
    private $ttl;

    public function __construct($ttl = null)
    {
        $this->ttl = $ttl;
    }

    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $key = sha1(json_encode([
                $request->getMethod(),
                (string)$request->getUri(),
                (string)$request->getBody(),
                $request->getHeaders(),
            ]));

            if ($data = Cache::store('file')->get($key)) {
                $response = (new HttpFactory())->createResponse($data['statusCode'])
                    ->withBody(Utils::streamFor($data['body']));

                foreach ($data['headers'] as $key => $value) {
                    $response = $response->withHeader($key, $value);
                }

                return new FulfilledPromise($response);
            }

            /** @var FulfilledPromise $promise */
            $promise = $handler($request, $options);

            $response = $promise->wait();

            Cache::store('file')->put($key, [
                'statusCode' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => (string)$response->getBody(),
            ], $this->ttl);

            return $promise;
        };
    }
}
