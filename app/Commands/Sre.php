<?php

namespace App\Commands;

use App\Crawler\OverviewCrawler;
use App\HttpClient\ClientFactory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use MilesChou\ImgEcho\ImgEcho;

class Sre extends Command
{
    protected $signature = 'sre {day?} {month?} {year?}
                                {--take=10}
                                {--stock=*}
                                {--chart}';

    protected $description = '查詢歷年股利';

    public function handle(OverviewCrawler $overview, ClientFactory $client): int
    {
        $day = $this->argument('day');
        $month = $this->argument('month');
        $year = $this->argument('year');
        $stocks = $this->option('stock');

        $now = Carbon::now();

        $day = empty($day) ? $now->day - 1 : $day;
        $month = empty($month) ? $now->month : $month;
        $year = empty($year) ? $now->year : $year;

        $data = $overview($year, $month, $day)
            ->filter(function ($v) use ($stocks) {
                if (empty($stocks)) {
                    return true;
                }

                return in_array($v[0], $stocks);
            })
            ->reject(function ($value) {
                return $value[4] === '-';
            })
            ->map(function ($value) {
                $value[4] = str_replace(',', '', $value[4]);
                $value[] = match (true) {
                    $value[4] === '-' => '-',
                    $value[4] <= 5 => '<info>★★★★★</info>',
                    $value[4] <= 8 => '★★★★☆',
                    $value[4] <= 10 => '★★★☆☆',
                    $value[4] <= 12 => '★★☆☆☆',
                    $value[4] <= 15 => '★☆☆☆☆',
                    default => '-',
                };

                return $value;
            })
            ->sortByDesc(4)
            ->take($this->option('take'));

        $this->table([
            '證券代號',
            '證券名稱',
            '殖利率(%)',
            '股利年度',
            '本益比',
            '股價淨值比',
            '財報年/季',
            '評價',
        ], $data->toArray(), 'box');

        if ($this->option('chart')) {
            // See http://coopermaa2nd.blogspot.com/2011/01/google-chart-api.html
            $parameter = [
                'cht' => 'bvg',
                'chs' => '700x400',
                'chd' => 't:' . $data->map(fn($v) => 100 * (1 / $v[4]))->implode(','),
                'chxl' => '0:|' . $data->map(fn($v) => $v[1])->implode('|'),
                'chxt' => 'x',
                'chxr' => '1,0,1',
                'chg' => '10,10',
            ];

            $uri = (new HttpFactory())
                ->createUri('https://chart.googleapis.com/chart')
                ->withQuery(Arr::query($parameter));

            $b = $client->get($uri)->body();

            $this->newLine();

            $this->line(
                (new ImgEcho())
                    ->withWidth('80%')
                    ->withImage($b)
                    ->build()
            );

            $this->newLine();

            return 0;
        }

        return 0;
    }
}
