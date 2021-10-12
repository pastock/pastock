<?php

namespace App\Commands;

use App\Crawler\StockCrawler;
use App\HttpClient\ClientFactory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Arr;
use LaravelZero\Framework\Commands\Command;
use MilesChou\ImgEcho\ImgEcho;

class Stock extends Command
{
    protected $signature = 'stock {stock} {month?} {year?} {--chart}';

    protected $description = '查詢個股股價';

    public function handle(StockCrawler $stockCrawler, ClientFactory $client): int
    {
        $stock = $this->argument('stock');
        $month = $this->argument('month');
        $year = $this->argument('year');

        $now = Carbon::now();

        $month = empty($month) ? $now->month : $month;
        $year = empty($year) ? $now->year : $year;

        $data = $stockCrawler($stock, $year, $month);

        $this->table([
            '日期',
            '成交股數',
            '成交金額',
            '開盤價',
            '最高價',
            '最低價',
            '收盤價',
            '漲跌價差',
            '成交筆數',
        ], $data->toArray());

        if ($this->option('chart')) {
            // See http://coopermaa2nd.blogspot.com/2011/01/google-chart-api.html
            $parameter = [
                'cht' => 'lc',
                'chs' => '1000x300',
                'chd' => 't:' . $data->map(fn($v) => 100 * (($v[6] - 550) / (600 - 550)))->implode(','),
                'chxl' => '0:|' . $data->map(fn($v) => $v[0])->implode('|'),
                'chxt' => 'x,y',
                'chxr' => '1,550,600',
                'chg' => '20,10',
            ];

            $uri = (new HttpFactory())
                ->createUri('https://chart.googleapis.com/chart')
                ->withQuery(Arr::query($parameter));

            $b = $client->get($uri)->body();

            $this->newLine();

            $this->line(
                (new ImgEcho())
                    ->withWidth('100%')
                    ->withImage($b)
                    ->build()
            );

            $this->newLine();

            return 0;
        }

        return 0;
    }
}
