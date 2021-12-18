<?php

namespace App\Commands;

use App\Crawler\EpsCrawler;
use Carbon\Carbon;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\OutputInterface;

class Eps extends Command
{
    protected $signature = 'eps {stock} {quarter?} {year?}
                                {--group-year}
                                {--chart}
                                {--take=10}
                                ';

    protected $description = '查詢公司 EPS';

    public function handle(EpsCrawler $eps): int
    {
        $stock = $this->argument('stock');
        $target = Carbon::now()->subQuarters(2);

        if ($this->option('group-year')) {
            $result = $this->getEpsGroupYears($eps, $target, $stock);
        } else {
            $result = $this->getEps($eps, $target, $stock);
        }

        dd($result);

        $header = array_shift($result);

        $this->table($header, $result);

        return 0;
    }

    private function getEps(EpsCrawler $eps, Carbon $target, string $stock)
    {
        return $eps($target->quarter, $target->year - 1911)
            ->first(function ($value) use ($stock) {
                return $value[0] === $stock;
            });
    }

    private function getEpsGroupYears(EpsCrawler $eps, Carbon $target, string $stock)
    {
        $data = collect();
        while (true) {
            $result = $this->getEps($eps, $target, $stock);

            if (null === $result) {
                break;
            }

            $this->line("查詢 {$target->year} 年 Q{$target->quarter}", null, OutputInterface::VERBOSITY_DEBUG);

            $data->push($result);

            $target->subQuarter();
        }

        return $data;
    }
}
