<?php

namespace App\Service;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class GuzzleClient
{
    private Client $client;

    public function __construct(string $baseUri)
    {
        $this->client = new Client([
            'base_uri' => $baseUri,
        ]);
    }

    public function get(string $uri): ResponseInterface
    {
        return $this->client->request('GET', $uri);
    }
}
