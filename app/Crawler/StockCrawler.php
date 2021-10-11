<?php

namespace App\Crawler;

use App\HttpClient\ClientFactory;
use Illuminate\Support\Collection;

/**
 * @see https://www.twse.com.tw/exchangeReport/STOCK_DAY?response=json&date=20200101&stockNo=2330
 * @see https://www.twse.com.tw/exchangeReport/STOCK_DAY?response=html&date=20200101&stockNo=2330
 */
class StockCrawler
{
    private ClientFactory $client;

    public function __construct(ClientFactory $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $year
     * @param string $month
     * @param string $day
     * @return Collection
     */
    public function __invoke(string $stock, string $year, string $month, string $day = '01'): Collection
    {
        $uri = sprintf(
            'https://www.twse.com.tw/exchangeReport/STOCK_DAY?response=json&date=%s%s%s&stockNo=%s',
            $year,
            $month,
            $day,
            $stock
        );

        return $this->client->get($uri)->collect('data');
    }
}
