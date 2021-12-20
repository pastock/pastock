<?php

namespace App\Commands;

use App\Crawler\StockCrawler;
use App\HttpClient\ClientFactory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LaravelZero\Framework\Commands\Command;
use MilesChou\ImgEcho\ImgEcho;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

class Stock extends Command
{
    protected $signature = 'stock
                                {stock : 股票代號}
                                {month? : 指定月份，預設本月}
                                {year? : 指定月份，預設本年}
                                {--all : 查全部}
                                {--group-year : 以年統計到所有資料}
                                {--unit=1 : 設定成交股數、成交金額、成交筆數的基本單位}
                                {--chart}
                                {--json : 輸出 JSON 格式}
                                ';

    protected $description = '查詢個股股價';

    public function handle(StockCrawler $stockCrawler, ClientFactory $client): int
    {
        $stock = $this->argument('stock');
        $month = $this->argument('month');
        $year = $this->argument('year');

        $now = Carbon::now();

        $month = empty($month) ? $now->month : $month;
        $year = empty($year) ? $now->year : $year;

        if ($this->option('all')) {
            $data = collect();

            $time = Carbon::createFromDate($year, $month);

            while (true) {
                $this->line("查詢 {$time->year} 年 {$time->month} 月", null, OutputInterface::VERBOSITY_DEBUG);
                $result = $stockCrawler($stock, $time->year, $time->month);

                if ($result->isEmpty()) {
                    break;
                }

                $result->reverse()->each(fn($v) => $data->push($v));

                $time->subMonth();
            }

            if ($this->option('group-year')) {
                $data = $data->groupBy('year')->map(function (Collection $year) {
                    $first = $year->pluck('opening_price')->first();
                    $last = $year->pluck('closing_price')->last();

                    return [
                        'year' => $year->pluck('year')->first(),
                        'month' => '*',
                        'day' => '*',
                        'volume' => $year->pluck('volume')->sum(),
                        'value' => $year->pluck('value')->sum(),
                        'opening_price' => $first,
                        'highest_price' => $year->pluck('highest_price')->max(),
                        'lowest_price' => $year->pluck('lowest_price')->min(),
                        'closing_price' => $last,
                        'change' => $last - $first,
                        'transaction' => $year->pluck('transaction')->sum(),
                    ];
                });
            }
        } else {
            $data = $stockCrawler($stock, $year, $month);
        }

        if ($this->option('json')) {
            $this->line($data->toJson());

            return 0;
        }

        $unit = $this->option('unit');

        $data->transform(function ($item) use ($unit) {
            $item['volume'] = number_format(round($item['volume'] / $unit));
            $item['value'] = number_format(round($item['value'] / $unit));
            $item['transaction'] = number_format(round($item['transaction'] / $unit));

            return $item;
        });

        $this->table([
            '年',
            '月',
            '日',
            '成交股數',
            '成交金額',
            '開盤價',
            '最高價',
            '最低價',
            '收盤價',
            '漲跌價差',
            '成交筆數',
        ], $data->toArray(), 'box-double', array_fill(0, 11, (new TableStyle())->setPadType(STR_PAD_LEFT)));

        if ($this->option('chart')) {
            // See http://coopermaa2nd.blogspot.com/2011/01/google-chart-api.html
            $parameter = [
                'cht' => 'lc',
                'chs' => '1000x300',
                'chd' => 't:' . $data->map(fn($v) => 100 * (($v[6] - 600) / (650 - 600)))->implode(','),
                'chxl' => '0:|' . $data->map(fn($v) => $v[0])->implode('|'),
                'chxt' => 'x,y',
                'chxr' => '1,600,650',
                'chg' => '20,10',
            ];

            $uri = (new HttpFactory())
                ->createUri('https://chart.googleapis.com/chart')
                ->withQuery(Arr::query($parameter));

            $b = $client->get($uri)->body();

            $this->newLine();

            $this->line(
                (new ImgEcho())
                    ->withWidth('50%')
                    ->withImage($b)
                    ->build()
            );

            $this->newLine();

            return 0;
        }

        return 0;
    }
}
