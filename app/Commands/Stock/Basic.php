<?php

namespace App\Commands\Stock;

use App\HttpClient\ClientFactory;
use LaravelZero\Framework\Commands\Command;

/**
 * @see https://openapi.twse.com.tw/v1/opendata/t187ap03_L
 */
class Basic extends Command
{
    protected $signature = 'stock:basic';

    protected $description = '上市公司基本資訊';

    public function handle(ClientFactory $client)
    {
        $data = $client->get('https://openapi.twse.com.tw/v1/opendata/t187ap03_L')
            ->collect()
            ->map(function ($item) {
                return [
                    'code' => $item['公司代號'],
                    'short_name' => $item['公司簡稱'],
                ];
            });

        $this->table([
            '公司代號',
            '公司簡稱',
        ], $data->toArray());
    }
}
