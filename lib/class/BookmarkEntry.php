<?php
namespace Aods1004\MyDict;
/**
 * Class BookmarkEntry
 * @package Aods1004\MyDict
 */
class BookmarkEntry
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $url;

    /**
     * BookmarkEntry constructor.
     * @param array $entry
     */
    public function __construct(array $entry)
    {
        $this->title = isset($entry['title']) ? strval($entry['title']) : "";
        $this->url = isset($entry['url']) ? strval($entry['url']) : "";
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return !empty($this->title) && !empty($this->url);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param $url
     * @return string
     */
    public function takeOverUrl($url)
    {

        if ($this->isValid() || $url != $this->getUrl()) {
            $url = $this->getUrl();

            $query = parse_url($url, PHP_URL_QUERY);
            if ($query) {
                $newQueries = [];
                foreach (explode("&", $query) as $pair) {
                    $queryItems = [];
                    foreach (explode("=", $pair) as $i) {
                        $queryItems[] = $i;
                        // $queryItems[] = rawurlencode($i);
                    }
                    $newQueries[] = implode("=", $queryItems);
                }
                $url = str_replace($query, implode("&", $newQueries), $url);
            }
        }
        return $url;
    }
}