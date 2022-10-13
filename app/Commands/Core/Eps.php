<?php

namespace App\Commands\Core;

use App\Crawler\EpsCrawler;
use LaravelZero\Framework\Commands\Command;

class Eps extends Command
{
    protected $signature = 'core:eps {year} {quarter}
                                {--take=10 : 0 代表取無限筆}
                                {--reverse : 反向結果}
                                {--random : 隨機取樣}
                                {--trace : 查預設的公司代號}
                                {--desc : 欄位說明}
                                ';

    protected $description = 'EpsCrawler::__invoke()';

    public function handle(EpsCrawler $crawler): int
    {
        $take = (int)$this->option('take');

        $result = $crawler(
            $this->argument('year'),
            $this->argument('quarter'),
        );

        if ($this->option('trace')) {
            $stocks = config('stock.default');

            $o = array_map(function($stock) use ($result){
                foreach ($result as $r) {
                    if ($stock === $r['code']) {
                        return $r;
                    }
                }

                return null;
            }, $stocks);

            dump($o);

            return 0;
        }

        if ($this->option('random')) {
            $result->random($take)->dump();

            return 1;
        }

        if ($this->option('reverse')) {
            $result = $result->reverse();
        }

        if (0 < $take) {
            $result = $result->take($take);
        }

        if ($this->option('desc')) {
            $desc = EpsCrawler::buildColumnDesc($result->first());

            $this->table(['欄位', '說明'], array_map(function ($v, $k) {
                return [$k, $v];
            }, $desc, array_keys($desc)));
        }

        $result->dump();

        return 0;
    }

}
