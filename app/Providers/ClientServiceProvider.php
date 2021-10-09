<?php

namespace App\Providers;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Factory::class, function () {
            $cache = Cache::store('array');

            // 因呼叫 twse 的 API 太頻繁的話會 ban IP，所以這裡做了硬幹阻擋的小程式
            // 目前有看到網路有分享規則是 5 秒內不能超過 3 次，這裡設定是 5 秒內可以連續呼叫 2 次，第三次開始會等 1 秒。
            return (new Factory())->beforeSending(function () use ($cache) {
                while ($cache->get('lock') >= 2) {
                    sleep(1);
                };

                if ($cache->has('lock')) {
                    $cache->increment('lock');
                } else {
                    $cache->put('lock', 1, 5);
                }
            });
        });
    }
}
