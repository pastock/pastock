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
        $target = Carbon::now()->subQuarters(2);

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

        $r = $result->reduce(function (array $c, array $value) {
            if ((int)$value[0] < 2014) {
                return $c;
            }

            if (empty($c[(int)$value[1] - 1])) {
                $c[(int)$value[1] - 1] = array_fill(0, 10, '');
            }

            $c[(int)$value[1] - 1][$value[0] - 2013] = $value[2];

            return $c;
        }, array_fill(0, 5, []));

        foreach ($r as $k => $v) {
            if ($k === 0) {
                continue;
            }

            $v = collect($v)
                ->map(function ($v2, $k2) use ($r, $k) {
                    if ($k2 === 0) {
                        return $v2;
                    }

                    if (empty($v2)) {
                        return $v2;
                    }

                    return $v2 - $r[$k - 1][$k2];
                })
                ->toArray();

            $r[$k] = $v;
        }

        $r[0][0] = 'Q1';
        $r[1][0] = 'Q2';
        $r[2][0] = 'Q3';
        $r[3][0] = 'Q4';
        $r[4][0] = '總計';

        for ($i = 1; $i < count($r[0]); $i++) {
            $r[4][$i] = (float)$r[0][$i] + (float)$r[1][$i] + (float)$r[2][$i] + (float)$r[3][$i];
        }

        $this->newLine();
        $this->line('股票代碼：' . $stock);

        $this->table([
            '季別/年度',
            '2014',
            '2015',
            '2016',
            '2017',
            '2018',
            '2019',
            '2020',
            '2021',
            '2022',
        ], $r, 'borderless');

//        $this->table([
//            '年度',
//            '季',
//            'EPS',
//            '營業收入',
//            '營業利益',
//            '營業外收入及支出',
//            '稅後淨利',
//        ], $result->toArray(), 'box-double', array_fill(0, 7, (new TableStyle())->setPadType(STR_PAD_LEFT)));

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
