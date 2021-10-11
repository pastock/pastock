<?php

namespace App\Crawler;

use App\HttpClient\ClientFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @see https://www.twse.com.tw/exchangeReport/BWIBBU_d?response=json&date=20170906&selectType=ALL
 * @see https://www.twse.com.tw/exchangeReport/BWIBBU_d?response=html&date=20170906&selectType=ALL
 */
class OverviewCrawler
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
    public function __invoke(string $year, string $month, string $day): Collection
    {
        $uri = sprintf(
            'https://www.twse.com.tw/exchangeReport/BWIBBU_d?response=json&date=%s%s%s&selectType=ALL',
            $year,
            Str::padLeft($month, 2, '0'),
            Str::padLeft($day, 2, '0'),
        );

        return $this->client->get($uri)->collect('data');
    }
}
