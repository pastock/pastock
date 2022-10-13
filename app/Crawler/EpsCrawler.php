<?php

namespace App\Crawler;

use App\HttpClient\ClientFactory;
use App\Utils\StrUtil;
use DOMNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

use function collect;

/**
 * @see https://mops.twse.com.tw/mops/web/t163sb19
 */
class EpsCrawler
{
    private ClientFactory $client;

    public static function buildColumnDesc(array $record): array
    {
        return array_combine(array_keys($record), [
            '公司代號',
            '公司名稱',
            '產業別',
            '基本每股盈餘(元)',
            '普通股每股面額',
            '營業收入',
            '營業利益',
            '營業外收入及支出',
            '稅後淨利',
        ]);
    }

    public function __construct(ClientFactory $client)
    {
        $this->client = $client;
    }

    public function __invoke(int $year, int $quarter, bool $noCache = false): Collection
    {
        $client = $noCache
            ? $this->client->noCached()
            : $this->client->cached();

        $data = [
            'encodeURIComponent' => '1',
            'step' => '1',
            'firstin' => '1',
            'TYPEK' => 'sii',
            'code' => '',
            'year' => (string)$year - 1911,
            'season' => Str::padLeft($quarter, 2, 0),
        ];

        $html = $client->asForm()
            ->post('https://mops.twse.com.tw/mops/web/ajax_t163sb19', $data)
            ->body();

        $crawler = new Crawler($html);

        $filter = $crawler->filter('table.hasBorder > tr');

        $result = [];

        /** @var DOMNode $node */
        foreach ($filter as $node) {
            $arr = [
                // 公司代號
                'code' => @trim($node->childNodes[1]->textContent),

                // 公司名稱
                'name' => @trim($node->childNodes[3]->textContent),

                // 產業別
                'industry' => @trim($node->childNodes[5]->textContent),

                // 基本每股盈餘（EPS）
                'earnings_per_share' => (float)@trim($node->childNodes[7]->textContent),

                // 普通股每股面額
                'par_value' => StrUtil::replaceMultiSpaceToOne(@trim($node->childNodes[9]->textContent)),

                // 營業收入
                'operating_revenue' => @trim($node->childNodes[11]->textContent),

                // 營業利益
                'operating_income' => @trim($node->childNodes[13]->textContent),

                // 營業外收入及支出
                'non_operating_income_expenses' => @trim($node->childNodes[15]->textContent),

                // 稅後淨利
                'profit' => @trim($node->childNodes[17]->textContent),
            ];

            if ('加權平均數' === $arr['name']) {
                continue;
            }

            // 這裡單位都是千元，先乘 1,000
            $arr['operating_revenue'] = $this->parseToInt($arr['operating_revenue']) * 1000;
            $arr['operating_income'] = $this->parseToInt($arr['operating_income']) * 1000;
            $arr['non_operating_income_expenses'] = $this->parseToInt($arr['non_operating_income_expenses']) * 1000;
            $arr['profit'] = $this->parseToInt($arr['profit']) * 1000;

            if (empty($arr['code'])) {
                continue;
            }

            $result[] = $arr;
        }

        // 移除 title 列
        // ["公司代號","公司名稱","產業別","基本每股盈餘(元)","普通股每股面額","營業收入","營業利益","營業外收入及支出","稅後淨利"]
        return collect($result)->reject(fn($item) => $item['code'] === '公司代號');
    }

    private function parseToInt($str): int
    {
        return (int)str_replace(',', '', $str);
    }
}
