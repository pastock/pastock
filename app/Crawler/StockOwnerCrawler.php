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

    public function __invoke(): Collection
    {
        return $this->client->get('https://openapi.twse.com.tw/v1/opendata/opendata/t187ap11_L')->collect();
    }
}
