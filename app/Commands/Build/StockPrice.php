<?php

namespace App\Commands\Build;

use App\Crawler\StockBasicCrawler;
use App\Crawler\StockCrawler;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class StockPrice extends Command
{
    protected $signature = 'build:stock:price
                                {stock?}
                                {--output=build : 輸出目錄}';

    protected $description = '建置所有股票的價錢';

    private Carbon $now;

    private StockCrawler $price;

    public function handle(StockBasicCrawler $basic, StockCrawler $price): int
    {
        $this->now = Carbon::now();
        $this->price = $price;

        $this->info('抓取所有上市股票的清單 ...', OutputInterface::VERBOSITY_VERBOSE);

        $output = $this->option('output');

        File::makeDirectory($output, 0755, true, true);

        // 固定抓最新的清單
        $list = $basic(true);

        if ($stock = $this->argument('stock')) {
            $list = $list->filter(function ($item) use ($stock) {
                return $item['code'] === $stock;
            });
        }

        $list->each(function ($item) use ($price, $output) {
            [$code, $name] = array_values($item);
            $this->info("下載 {$code} {$name} 的資料 ...", OutputInterface::VERBOSITY_VERBOSE);

            $cache = Http::get("https://pastock.github.io/stock/{$code}.json");

            if ($cache->status() === 404) {
                $json = $this->full($code, $name)->toJson();
            } else {
                $data = $cache->collect()->reject(function ($item) {
                    return $item['year'] === $this->now->year && $item['month'] === $this->now->month;
                });

                $current = $this->get($code, $name, $this->now->year, $this->now->month);
                $current->reverse()->each(fn($v) => $data->push($v));

                $json = $data->toJson();
            }

            $path = $output . '/' . $code . '.json';

            $this->info('寫入檔案：' . $path, OutputInterface::VERBOSITY_VERBOSE);
            File::put($path, $json);
        });

        return 0;
    }

    private function full(string $code, string $name): Collection
    {
        $data = collect();

        $time = $this->now->copy();

        while (true) {
            $time->subMonth();

            try {
                $result = $this->get($code, $name, $time->year, $time->month);
            } catch (RuntimeException) {
                break;
            }

            $result->reverse()->each(fn($v) => $data->push($v));
        }

        return $data;
    }

    private function get(mixed $code, mixed $name, int $year, int $month): Collection
    {
        $this->line(
            "下載 {$code} {$name} {$year} 年 {$month} 月資料",
            null,
            OutputInterface::VERBOSITY_VERY_VERBOSE
        );

        $result = ($this->price)($code, $year, $month);

        if ($result->isEmpty()) {
            throw new RuntimeException("股票代碼 $code 無法下載");
        }

        return $result;
    }
}
