<?php

namespace App\Commands;

use App\Crawler\OverviewCrawler;
use Carbon\Carbon;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class Sre extends Command
{
    protected $signature = 'sre {day?} {month?} {year?}';

    protected $description = '查詢歷年股利';

    public function handle(OverviewCrawler $overview): int
    {
        $day = $this->argument('day');
        $month = $this->argument('month');
        $year = $this->argument('year');

        $now = Carbon::now();

        $day = empty($day) ? $now->day - 1 : $day;
        $month = empty($month) ? $now->month : $month;
        $year = empty($year) ? $now->year : $year;

        $data = $overview($year, $month, $day)
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
            ->sortBy(4)
            ->toArray();

        $this->table([
            '證券代號',
            '證券名稱',
            '殖利率(%)',
            '股利年度',
            '本益比',
            '股價淨值比',
            '財報年/季',
            '評價',
        ], $data);

        return 0;
    }
}
