<?php

namespace App\HttpClient;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Psr\Http\Message\RequestInterface;

/**
 * 因呼叫 twse 的 API 太頻繁的話會 ban IP，所以這裡做了硬幹阻擋的小程式
 *
 * 目前有看到網路有分享規則是 5 秒內不能超過 3 次，這也是預設設定
 */
class DelaySending
{
    private Repository $cache;
    private int $delay;
    private int $retry;

    /**
     * @param Repository $cache
     * @param int $delay
     * @param int $retry
     */
    public function __construct(Repository $cache, int $delay = 5, int $retry = 2)
    {
        $this->cache = $cache;
        $this->delay = $delay;
        $this->retry = $retry;
    }

    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            while ($this->cache->get('lock') >= $this->retry) {
                sleep(1);
            };

            if ($this->cache->has('lock')) {
                $this->cache->increment('lock');
            } else {
                $this->cache->put('lock', 1, $this->delay);
            }

            return $handler($request, $options);
        };
    }
}
