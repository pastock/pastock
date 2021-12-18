<?php

namespace App\Crawler;

use App\HttpClient\ClientFactory;
use Illuminate\Support\Collection;

/**
 * @see https://openapi.twse.com.tw/v1/opendata/opendata/t187ap11_L
 */
class StockOwnerCrawler
{
    private ClientFactory $client;

    public function __construct(ClientFactory $client)
    {
        $this->client = $client;
    }

    public function __invoke(?string $stock = null): Collection
    {
        $collection = $this->client->get('https://openapi.twse.com.tw/v1/opendata/opendata/t187ap11_L')->collect();

        return $collection->where('公司代號', $stock)->values();
    }
}
