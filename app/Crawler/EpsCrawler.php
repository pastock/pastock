<?php

namespace App\Crawler;

use App\HttpClient\ClientFactory;
use DOMNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

use function collect;

class EpsCrawler
{
    private ClientFactory $client;

    public function __construct(ClientFactory $client)
    {
        $this->client = $client;
    }

    public function __invoke(int $quarter, int $year): Collection
    {
        $data = [
            'encodeURIComponent' => '1',
            'step' => '1',
            'firstin' => '1',
            'TYPEK' => 'sii',
            'code' => '',
            'year' => (string)$year,
            'season' => Str::padLeft($quarter, 2, 0),
        ];

        $html = $this->client->asForm()
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

        return collect($result);
    }
}
