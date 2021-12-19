<?php

namespace App\Commands\Stock;

use App\Crawler\StockBasicCrawler;
use LaravelZero\Framework\Commands\Command;

/**
 * @see https://openapi.twse.com.tw/v1/opendata/t187ap03_L
 */
class Basic extends Command
{
    protected $signature = 'stock:basic';

    protected $description = '上市公司基本資訊';

    public function handle(StockBasicCrawler $basic)
    {
        $data = $basic();

        $this->table([
            '公司代號',
            '公司簡稱',
        ], $data->toArray());
    }
}
