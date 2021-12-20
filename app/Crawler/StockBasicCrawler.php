<?php

namespace App\Crawler;

use App\HttpClient\ClientFactory;
use Illuminate\Support\Collection;

/**
 * @see https://openapi.twse.com.tw/v1/opendata/opendata/t187ap11_L
 */
class StockBasicCrawler
{
    private ClientFactory $client;

    public function __construct(ClientFactory $client)
    {
        $this->client = $client;
    }

    public function __invoke($noCache = false): Collection
    {
        if ($noCache) {
            $client = $this->client->noCached();
        } else {
            $client = $this->client;
        }

        return $client->get('https://openapi.twse.com.tw/v1/opendata/t187ap03_L')
            ->collect()
            ->map(function ($item) {
                return [
                    'code' => $item['公司代號'],
                    'short_name' => $item['公司簡稱'],
                ];
            });
    }
}
