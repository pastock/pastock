<?php

namespace App\Commands;

use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;

class Etf extends Command
{
    protected $signature = 'etf {code} {intersect?*}';

    protected $description = '查詢 ETF ';

    public function handle(): int
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

        $this->table([
            'stock',
            'name',
            'type',
        ], collect($data)->map(function ($v) {
            unset($v['Date']);
            unset($v['Value']);
            unset($v['Unit']);
            unset($v['Amount']);
            unset($v['Currency']);
            unset($v['Weights']);

            return $v;
        }));

        return 0;
    }
}
