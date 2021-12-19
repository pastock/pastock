<?php

namespace App\Commands;

use App\Crawler\EpsCrawler;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

class Eps extends Command
{
    protected $signature = 'eps {stock} {quarter?} {year?}
                                {--unit=1000000 : 單位，預設百萬 }
                                {--chart}
                                {--take=10}
                                ';

    protected $description = '查詢公司 EPS';

    public function handle(EpsCrawler $eps): int
    {
        $stock = $this->argument('stock');
        $unit = $this->option('unit');
        $target = Carbon::now()->subQuarter();

        $result = $this->getAllEps($eps, $target)
            ->map(function (Collection $record, string $key) use ($stock, $unit) {
                $item = $record->first(function ($item) use ($stock) {
                    return $item['code'] === $stock;
                });

                Arr::forget($item, [
                    'code',
                    'name',
                    'industry',
                ]);

                $item['operating_revenue'] = round($item['operating_revenue'] / $unit);
                $item['operating_income'] = round($item['operating_income'] / $unit);
                $item['non_operating_income_expenses'] = round($item['non_operating_income_expenses'] / $unit);
                $item['profit'] = round($item['profit'] / $unit);

                $item = array_values($item);

                [$year, $quarter] = explode('/', $key);

                array_unshift($item, $year, $quarter);

                return $item;
            });

        $this->table([
            '年度',
            '季',
            'EPS',
            '營業收入',
            '營業利益',
            '營業外收入及支出',
            '稅後淨利',
        ], $result->toArray(), 'box-double', array_fill(0, 7, (new TableStyle())->setPadType(STR_PAD_LEFT)));

        return 0;
    }

    private function getEps(EpsCrawler $eps, Carbon $target): Collection
    {
        return $eps($target->quarter, $target->year);
    }

    private function getAllEps(EpsCrawler $eps, Carbon $target): Collection
    {
        $data = [];

        while (true) {
            $result = $this->getEps($eps, $target);

            $this->line(json_encode($result->first(), JSON_UNESCAPED_UNICODE), null, OutputInterface::VERBOSITY_DEBUG);

            if ($result->isEmpty()) {
                break;
            }

            $count = count($result);

            $this->line(
                "查詢 {$target->year} 年 Q{$target->quarter} 共 {$count} 筆",
                null,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $data["{$target->year}/{$target->quarter}"] = $result;

            $target->subQuarter();
        }

        return collect($data);
    }
}
