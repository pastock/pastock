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
                                {--output=build : 輸出目錄}
                                {--full-limit=10 : 執行完全下載的次數上限}';

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
        }

        $list->each(function ($item, $key) use ($price, $output) {
            [$code, $name] = array_values($item);
            $this->info("[$key] 下載 {$code} {$name} 的資料 ...", OutputInterface::VERBOSITY_VERBOSE);

            $current = $this->get($code, $name, $this->now->year, $this->now->month, true)->reverse();

            $cache = Http::get("https://pastock.github.io/stock/{$code}.json");

            $isNotFound = $cache->status() === 404;
            if ($isNotFound) {
                $data = $this->full($code, $name);

                $this->limit--;
                $this->info('剩下 ' . $this->limit . ' 次', OutputInterface::VERBOSITY_VERY_VERBOSE);
            } else {
                $data = $cache->collect()->reject(function ($item) {
                    return $item['year'] === $this->now->year && $item['month'] === $this->now->month;
                });
            }

            $data->each(fn($v) => $current->push($v));

            $path = $output . '/' . $code . '.json';

            $this->info('寫入檔案：' . $path, OutputInterface::VERBOSITY_VERBOSE);
            File::put($path, $current->toJson());

            if ($isNotFound && (0 === $this->limit)) {
                return false;
            }

            return true;
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
