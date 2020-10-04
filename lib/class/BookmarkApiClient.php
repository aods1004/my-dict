<?php

namespace Aods1004\MyDict;

use GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;
use JsonException;
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
    private Client $client;
    /**
     * @var PDO|null
     */
    private ?PDO $pdo;

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
        if ((string) $res->getStatusCode() === '200') {
            try {
                $item = json_decode($res->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
                $item['tags'] = isset($item['tags']) && is_array($item['tags']) ? $item['tags'] : [];
                $item['tags'] = array_unique(array_merge($item['tags'], $initialTags ?: []));
                $this->updateDatabase($url, $item['tags'], $item['comment_raw']);
                return $item;
            } catch (JsonException $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * @param string $url
     * @return ResponseInterface
     * @throws GuzzleException
     *
     */
    public function delete(string $url): ResponseInterface
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
        if (! $this->beNotChange($url, $tags, $comment, strtotime("-1 day"))) {
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
    public function updateDatabase($url, $tags, $comment): void
    {
        if($this->pdo) {
            $st = $this->pdo->prepare("replace into bookmark (url, tags, comment_raw, stored_at) values(:url,:tags,:comment_raw, :stored_at);");
            $st->bindValue(":url", $url);
            $st->bindValue(":tags", implode(",", $tags));
            $st->bindValue(":comment_raw", $comment);
            $st->bindValue(":stored_at", time());
            $st->execute();
        }
    }

    /**
     * @param string $url
     * @param string $comment
     * @param array $tags
     * @param int $newThen
     * @return bool
     */
    public function beNotChange(string $url, $tags = [], $comment = null, int $newThen = 0): bool
    {
        if($this->pdo) {
            $query = "select 1 from bookmark where url = :url and tags = :tags and stored_at > :stored_at";
            if (! is_null($comment)) {
                $query .= " and comment_raw = :comment_raw";
            }
            $st = $this->pdo->prepare($query);
            $st->bindValue(":url", $url);
            $st->bindValue(":tags", implode(",", $tags));
            $st->bindValue(":stored_at", $newThen);
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
     * @param int $newThen
     * @return bool
     */
    public function exist($url, int $newThen = 0): bool
    {
        if($this->pdo) {
            $st = $this->pdo->prepare("select 1 from bookmark where url = :url and stored_at > :stored_at");
            $st->bindValue(":url", $url);
            $st->bindValue(":stored_at", $newThen);
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
     * @return BookmarkEntry|null
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