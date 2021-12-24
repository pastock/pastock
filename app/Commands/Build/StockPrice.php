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
                                {stock?*}
                                {--random : 隨機順序}
                                {--output=build : 輸出目錄}
                                {--full-limit=1 : 執行完全下載的次數上限}';

    protected $description = '建置所有股票的價錢';

    private Carbon $now;

    private int $limit;

    private StockCrawler $price;

    public function handle(StockBasicCrawler $basic, StockCrawler $price): int
    {
        $this->now = Carbon::now();
        $this->price = $price;
        $this->limit = (int)$this->option('full-limit');

        $this->info('抓取所有上市股票的清單 ...', OutputInterface::VERBOSITY_VERBOSE);

        $output = $this->option('output');

        File::makeDirectory($output, 0755, true, true);

        // 固定抓最新的清單
        $list = $basic(true);

        $this->info('上市股票總共有 ' . $list->count() . ' 家', OutputInterface::VERBOSITY_VERBOSE);

        if ($stock = $this->argument('stock')) {
            $list = $list->filter(function ($item) use ($stock) {
                return in_array($item['code'], $stock, true);
            });

            $this->info('限制範圍：' . implode(',', $stock), OutputInterface::VERBOSITY_VERBOSE);
        }

        if ($this->option('random')) {
            $list = $list->shuffle();
        }

        $list->each(function ($item, $key) use ($output) {
            [$code, $name] = array_values($item);
            $this->info("[$key] 下載 {$code} {$name} 的資料 ...", OutputInterface::VERBOSITY_VERBOSE);

            $cache = Http::get("https://pastock.github.io/stock/{$code}.json");

            if ($this->limit > 0 && 404 === $cache->status()) {
                $data = $this->full($code, $name);

                $this->limit--;
                $this->info('剩下 ' . $this->limit . ' 次', OutputInterface::VERBOSITY_VERY_VERBOSE);
            } else {
                $data = $cache->collect()->reject(function ($item) {
                    return $item['year'] === $this->now->year && $item['month'] === $this->now->month;
                });
            }

            if ($data->isEmpty()) {
                return;
            }

            $current = $this->get($code, $name, $this->now->year, $this->now->month, true)->reverse();

            $data->each(fn($v) => $current->push($v));

            $path = $output . '/' . $code . '.json';

            $this->info('寫入檔案：' . $path, OutputInterface::VERBOSITY_VERBOSE);
            File::put($path, $current->toJson(JSON_PRETTY_PRINT));
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

    private function get(mixed $code, mixed $name, int $year, int $month, bool $noCache = false): Collection
    {
        $this->line(
            "> 下載 {$code} {$name} {$year} 年 {$month} 月資料",
            null,
            OutputInterface::VERBOSITY_VERY_VERBOSE
        );

        $result = ($this->price)($code, $year, $month, $noCache);

        if ($result->isEmpty()) {
            throw new RuntimeException("股票代碼 $code 無法下載");
        }

        return $result;
    }
}
