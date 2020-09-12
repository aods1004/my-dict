<?php

namespace Aods1004\MyDict;

use GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;

/**
 * Class BookmarkApiClient
 * @package Aods1004\MyDict
 */
class BookmarkApiClient
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
    public function fetch($url, array $initialTags = [])
    {
        $res = $this->client->get("my/bookmark", ['query' => ['url' => $url]]);
        if ($res->getStatusCode() == '200') {
            $item = json_decode($res->getBody()->getContents(), true);
            $item['status'] = $res->getStatusCode();
            $item['tags'] = isset($item['tags']) && is_array($item['tags']) ? $item['tags'] : [];
            $item['tags'] = array_unique(array_merge($item['tags'], $initialTags ?: []));
            return $item;
        }
        return null;
    }

    /**
     * @param $url
     * @return mixed
     * @throws GuzzleException
     */
    public function delete($url)
    {
        $res = $this->client->delete("my/bookmark", ['query' => ['url' => $url]]);
        return json_decode($res->getBody()->getContents(), true);
    }

    /**
     * @param $url
     * @param $comment
     * @return mixed
     * @throws GuzzleException
     */
    public function post($url, $comment)
    {
        $res = $this->client->post("my/bookmark?url=" . $url, ["form_params" => ["comment" => $comment]]);
        return json_decode($res->getBody()->getContents(), true);;
    }

    /**
     * @param $url
     * @return BookmarkEntry
     * @throws GuzzleException
     */
    public function fetchEntry($url)
    {
        $res = $this->client->get("entry", ['query' => ['url' => $url]]);
        $entry = json_decode($res->getBody()->getContents(), true);
        return new BookmarkEntry($entry);
    }

}