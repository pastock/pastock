<?php

namespace App\Stock;

use App\HttpClient\ClientFactory;
use Illuminate\Support\Collection;

class Price
{
    private ClientFactory $client;

    public function __construct(ClientFactory $client)
    {
        $this->client = $client;
    }

    public function __invoke(string $code, bool $noCache = false): Collection
    {
        if ($noCache) {
            $client = $this->client->noCached();
        } else {
            $client = $this->client;
        }

        return $client->get("https://pastock.github.io/stock/{$code}.json")->collect();
    }
}
