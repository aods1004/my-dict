<?php

namespace Aods1004\MyDict;

use GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;
use \Psr\Http\Message\ResponseInterface;
use PDO;

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
    /**
     * @var PDO
     */
    private $pdo;

    public function __construct(Client $client, PDO $pdo = null)
    {
        $this->client = $client;
        $this->pdo = $pdo;
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
            $this->updateDatabase($url, $item['tags'], $item['comment_raw']);
            return $item;
        }
        return null;
    }

    /**
     * @param $url
     * @return ResponseInterface
     * @throws GuzzleException
     *
     */
    public function delete($url)
    {
        $res = $this->client->delete("my/bookmark", [
            // 'query' => ['url' => $url],
            "form_params" => ["url" => $url],
        ]);
        return $res;
    }

    /**
     * @param $url
     * @param $comment
     * @param $tags
     * @return mixed
     * @throws GuzzleException
     */
    public function put($url, $comment, $tags)
    {
        if (! $this->beNotChange($url, $tags, $comment)) {
            $res = $this->client->post("my/bookmark", ["form_params" => ["url" => $url, "comment" => $comment]]);
            $this->updateDatabase($url, $tags, $comment);
            return json_decode($res->getBody()->getContents(), true);
        }
        return null;
    }

    /**
     * @param $url
     * @param $comment
     * @param $tags
     */
    public function updateDatabase($url, $tags, $comment)
    {
        if($this->pdo) {
            $st = $this->pdo->prepare("replace into bookmark (url, tags, comment_raw) values(:url,:tags,:comment_raw);");
            $st->bindValue(":url", $url);
            $st->bindValue(":tags", implode(",", $tags));
            $st->bindValue(":comment_raw", $comment);
            $st->execute();
        }
    }

    /**
     * @param $url
     * @param $comment
     * @param $tags
     * @return bool
     */
    public function beNotChange($url, $tags, $comment = null)
    {
        if($this->pdo) {
            $query = "select 1 from bookmark where url = :url and tags = :tags";
            if (! is_null($comment)) {
                $query .= " and comment_raw = :comment_raw";
            }
            $st = $this->pdo->prepare($query);
            $st->bindValue(":url", $url);
            $st->bindValue(":tags", implode(",", $tags));
            if (!is_null($comment)) {
                $st->bindValue(":comment_raw", $comment);
            }
            $st->execute();
            $data = $st->fetchAll();
            if (! empty($data)) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param $url
     * @return bool
     */
    public function exist($url)
    {
        if($this->pdo) {
            $st = $this->pdo->prepare("select 1 from bookmark where url = :url;");
            $st->bindValue(":url", $url);
            $st->execute();
            $data = $st->fetchAll();
            if (! empty($data)) {
                return true;
            }
        }
        return false;
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
        if ($entry) {
            return new BookmarkEntry($entry);
        }
        return null;
    }

};