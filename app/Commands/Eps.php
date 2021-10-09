<?php

namespace App\Commands;

use DOMNode;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\DomCrawler\Crawler;

class Eps extends Command
{
    protected $signature = 'eps {company}';

    protected $description = '查詢公司 EPS';

    public function handle(): int
    {
        $company = $this->argument('company');


        $body = 'encodeURIComponent=1&step=1&firstin=1&TYPEK=sii&code=&year=105&season=01';
        parse_str($body, $data);

        $html = Http::asForm()
            ->post('https://mops.twse.com.tw/mops/web/ajax_t163sb19', $data)
            ->body();

        $crawler = new Crawler($html);

        $filter = $crawler->filter('table.hasBorder > tr');

        $result = [];

        /** @var DOMNode $node */
        foreach ($filter as $key => $node) {
            $arr = [
                @trim($node->childNodes[1]->textContent),
                @trim($node->childNodes[3]->textContent),
                @trim($node->childNodes[5]->textContent),
                @trim($node->childNodes[7]->textContent),
                @trim($node->childNodes[9]->textContent),
                @trim($node->childNodes[11]->textContent),
                @trim($node->childNodes[13]->textContent),
                @trim($node->childNodes[15]->textContent),
                @trim($node->childNodes[17]->textContent),
            ];

            if (empty($arr[4])) {
                continue;
            }

            if ($key !== 0 && "公司代號" === $arr[0]) {
                continue;
            }

            $result[] = $arr;
        }

        $result = collect($result)
            ->sortByDesc(3)
            ->toArray();

        $header = array_shift($result);

        $this->table($header, $result);

        return 0;
    }

    private function toArray($stream): array
    {
        $result = [];

        while (($data = fgetcsv($stream)) !== false) {
            $result[] = $data;
        }

        return $result;
    }
}
