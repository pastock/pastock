<?php

namespace App\Commands;

use App\Crawler\StockOwnerCrawler;
use Illuminate\Support\Arr;
use LaravelZero\Framework\Commands\Command;

class StockOwner extends Command
{
    protected $signature = 'stock-owner {stock}';

    protected $description = '查詢上市公司董監事持股餘額明細資料';

    public function handle(StockOwnerCrawler $crawler): int
    {
        $stock = $this->argument('stock');

        $data = $crawler();

        $data = $data->where('公司代號', $stock)
            ->values()
            ->map(function (array $v) {
                return Arr::only($v, [
                    '職稱',
                    '姓名',
                    '目前持股',
                ]);
            });


        $this->table([
            '職稱',
            '姓名',
            '目前持股',
        ], $data->toArray());

        return 0;
    }
}
