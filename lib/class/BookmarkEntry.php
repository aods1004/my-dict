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
    private string $title;
    /**
     * @var string
     */
    private string $url;

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
    public function isValid(): bool
    {
        return !empty($this->title) && !empty($this->url);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param $url
     * @return string
     */
    public function takeOverUrl($url): string
    {

        if ($this->isValid() || $url != $this->getUrl()) {
            $url = $this->getUrl();
        }
        return $url;
    }
}