<?php

namespace Aods1004\MyDict;

use GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;

class ItemFetcher
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param $url
     * @param array $initialTags
     * @return mixed
     * @throws GuzzleException
     */
    public function fetchBookmark($url, array $initialTags = [])
    {
        $res = $this->client->get("my/bookmark", ['query' => ['url' => $url]]);
        $item = json_decode($res->getBody()->getContents(), true);
        $item['status'] = $res->getStatusCode();
        $item['tags'] = isset($item['tags']) && is_array($item['tags']) ? $item['tags'] : [];
        $item['tags'] = array_unique(array_merge($item['tags'], $initialTags ?: []));
        return $item;
    }

    /**
     * @param $url
     * @return mixed
     * @throws GuzzleException
     */
    public function fetchEntry($url)
    {
        $res = $this->client->get("entry", ['query' => ['url' => $url]]);
        $entry = json_decode($res->getBody()->getContents(), true);
        return $entry;
    }

}