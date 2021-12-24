<?php

namespace App\Commands\Stock;

use App\HttpClient\ClientFactory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\TableStyle;

class Price extends Command
{
    protected $signature = 'stock:price
                                {stock : 股票代號}
                                {--all : 查全部}
                                {--group-year : 以年統計到所有資料}
                                {--year= : 指定年份，預設本年}
                                {--unit=1 : 設定成交股數、成交金額、成交筆數的基本單位}
                                {--no-cache : 不使用 cache}
                                ';

    protected $description = '查詢個股歷史股價';

    public function handle(ClientFactory $client): int
    {
        $stock = $this->argument('stock');

        $now = Carbon::now();

        if ($this->option('no-cache')) {
            $data = Http::get("https://pastock.github.io/stock/{$stock}.json")->collect();
        } else {
            $data = $client->cached()->get("https://pastock.github.io/stock/{$stock}.json")->collect();
        }

        if (!$this->option('all')) {
            $year = $this->option('year');
            $year = empty($year) ? $now->year - 1911 : $year;

            $data = $data->filter(fn($item) => $item['year'] === $year);
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
                    'opening_price' => $last,
                    'highest_price' => $year->pluck('highest_price')->max(),
                    'lowest_price' => $year->pluck('lowest_price')->min(),
                    'closing_price' => $first,
                    'change' => round($first - $last, 2),
                    'transaction' => $year->pluck('transaction')->sum(),
                ];
            });
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

        return 0;
    }
}
