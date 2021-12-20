<?php

namespace App\Commands;

use App\Crawler\StockOwnerCrawler;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\OutputInterface;

class Etf extends Command
{
    protected $signature = 'etf {code} {intersect?*}
                                {--stock : 只顯示股票}
                                {--compact : 只顯示代碼}
                                {--with-mof : 過濾財政部}
                                ';

    protected $description = '查詢 ETF ';

    public function handle(StockOwnerCrawler $ownerCrawler): int
    {
        $code = $this->argument('code');
        $intersect = $this->argument('intersect');

        $etf = Http::asForm()->post('https://www.cmoney.tw/etf/ashx/e210.ashx', [
            'action' => 'GetShareholdingDetails',
            'stockId' => $code,
        ])->json('Data');

        // curl -X POST -d 'action=GetShareholdingDetails&stockId=0050' -v https://www.cmoney.tw/etf/ashx/e210.ashx
        $intersect = collect($intersect)->flip()->map(function ($_, $code) {
            return Http::asForm()->post('https://www.cmoney.tw/etf/ashx/e210.ashx', [
                'action' => 'GetShareholdingDetails',
                'stockId' => $code,
            ])->json('Data');
        });

        $data = $intersect->reduce(function (array $c, $v) {
            if (empty($c)) {
                return $v;
            }

            return array_uintersect($c, $v, function ($c1, $c2) {
                return strcmp($c1['CommKey'], $c2['CommKey']);
            });
        }, $etf);

        $data = collect($data)->map(function ($v) {
            return Arr::only($v, [
                'CommKey',
                'CommName',
                'Type',
            ]);
        });

        if ($this->option('stock')) {
            $data = $data->filter(function ($v) {
                return $v['Type'] === '股票';
            });
        }

        if ($this->option('with-mof')) {
            $data = $data->filter(function ($v) use ($ownerCrawler) {
                $owner = $ownerCrawler($v['CommKey']);

                $this->line(
                    "檢查 {$v['CommKey']} {$v['CommName']} 是否有財政部持股",
                    null,
                    OutputInterface::VERBOSITY_VERY_VERBOSE
                );

                foreach ($owner as $item) {
                    if ($item['姓名'] === '財政部') {
                        $this->info("{$v['CommKey']} {$v['CommName']} 有財政部持股", OutputInterface::VERBOSITY_DEBUG);
                        return true;
                    }
                }

                $this->line("{$v['CommKey']} {$v['CommName']} 沒有財政部持股", null, OutputInterface::VERBOSITY_DEBUG);

                return false;
            });
        }

        if ($this->option('compact')) {
            foreach ($data as $v) {
                $this->line($v['CommKey']);
            }

            return 0;
        }

        $this->table([
            'stock',
            'name',
            'type',
        ], $data);

        return 0;
    }
}
