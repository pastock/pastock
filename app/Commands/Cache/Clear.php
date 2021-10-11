<?php

namespace App\Commands\Cache;

use Illuminate\Support\Facades\Cache;
use LaravelZero\Framework\Commands\Command;

class Clear extends Command
{
    protected $signature = 'cache:clear';

    protected $description = '清除快取';

    public function handle(): int
    {
        Cache::store('file')->clear();

        return 0;
    }
}
