<?php

namespace App\Commands;

use App\Crawler\EpsCrawler;
use Carbon\Carbon;
use LaravelZero\Framework\Commands\Command;

class Eps extends Command
{
    protected $signature = 'eps {quarter?} {year?}';

    protected $description = '查詢公司 EPS';

    public function handle(EpsCrawler $eps): int
    {
        $quarter = $this->argument('quarter');
        $year = $this->argument('year');

        $target = Carbon::now()->subQuarters(2);

        $quarter = empty($season) ? $target->quarter : $quarter;
        $year = empty($year) ? $target->year - 1911 : $year;

        $result = $eps($quarter, $year)
            ->sortByDesc(3)
            ->toArray();

        $header = array_shift($result);

        $this->table($header, $result);

        return 0;
    }
}
