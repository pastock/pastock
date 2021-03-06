<?php

namespace App\Crawler;

use App\HttpClient\ClientFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

    public function __invoke(string $stock, string $year, string $month, bool $noCache = false): Collection
    {
        $uri = sprintf(
            'https://www.twse.com.tw/exchangeReport/STOCK_DAY?response=json&date=%s%s01&stockNo=%s',
            $year,
            Str::padLeft($month, 2, '0'),
            $stock
        );

        if ($noCache) {
            $client = $this->client->noCached();
        } else {
            $client = $this->client;
        }

        return $client->get($uri)
            ->collect('data')
            ->map(function (array $record) {
                [$year, $month, $day] = explode('/', $record[0]);

                return [
                    'year' => (int)$year,
                    'month' => (int)$month,
                    'day' => (int)$day,
                    'volume' => (int)str_replace(',', '', $record[1]),
                    'value' => (int)str_replace(',', '', $record[2]),
                    'opening_price' => (float)$record[3],
                    'highest_price' => (float)$record[4],
                    'lowest_price' => (float)$record[5],
                    'closing_price' => (float)$record[6],
                    'change' => (float)str_replace('+', '', $record[7]),
                    'transaction' => (int)str_replace(',', '', $record[8]),
                ];
            });
    }
}
