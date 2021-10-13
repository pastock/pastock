<?php

namespace App\Commands;

use App\Crawler\EpsCrawler;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use LaravelZero\Framework\Commands\Command;
use MilesChou\ImgEcho\ImgEcho;
use Pachart\Drivers\GoogleChart\Bar;

class Eps extends Command
{
    protected $signature = 'eps {quarter?} {year?}
                                {--chart}
                                {--take=10}
                                ';

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
            ->take($this->option('take'))
            ->toArray();

        $header = array_shift($result);

        $this->table($header, $result);

        if ($this->option('chart')) {
            $data = collect($result);

            $line = new Bar(new Client(), new HttpFactory());
            $line->size(700, 400)
                ->setData($data->map(fn($v) => $v[3]))
                ->setXLabel($data->map(fn($v) => $v[0]))
                ->setXt()
                ->range(0, 100)
                ->setGrid(10, 10);

            $this->newLine();

            $this->line(
                (new ImgEcho())
                    ->withWidth('80%')
                    ->withImage($line->binary())
                    ->build()
            );

            $this->newLine();

            return 0;
        }

        return 0;
    }
}
