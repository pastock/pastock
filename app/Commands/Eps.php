<?php

namespace App\Commands;

use App\Crawler\EpsCrawler;
use App\HttpClient\ClientFactory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Arr;
use LaravelZero\Framework\Commands\Command;
use MilesChou\ImgEcho\ImgEcho;

class Eps extends Command
{
    protected $signature = 'eps {quarter?} {year?}
                                {--chart}
                                {--take=10}
                                ';

    protected $description = '查詢公司 EPS';

    public function handle(EpsCrawler $eps, ClientFactory $client): int
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
            // See http://coopermaa2nd.blogspot.com/2011/01/google-chart-api.html
            $parameter = [
                'cht' => 'bvg',
                'chs' => '700x400',
                'chd' => 't:' . $data->map(fn($v) => $v[3])->implode(','),
                'chxl' => '0:|' . $data->map(fn($v) => $v[0])->implode('|'),
                'chxt' => 'x,y',
                'chxr' => '1,0,100',
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
