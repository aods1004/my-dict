<?php

namespace Aods1004\MyDict;

class TagExchanger
{
    /**
     * @var array
     */
    private $extractKeywords = [];
    /**
     * @var array
     */
    private $exchange = [];
    /**
     * @var array
     */
    private $exclude = [];
    /**
     * @var array
     */
    private $replace = [];

    /**
     * TagExchanger constructor.
     * @param array $extractKeywords
     * @param array $exchange
     * @param array $replace
     * @param array $exclude
     */
    public function __construct(array $extractKeywords, array $exchange, array $replace, array $exclude)
    {
        $this->extractKeywords = $extractKeywords;
        $this->exchange = $exchange;
        $this->exclude = $exclude;
        $this->replace = $replace;
    }

    /**
     * @param $tags
     * @param BookmarkEntry $entry
     * @return array
     */
    public function extractKeywords($tags, BookmarkEntry $entry)
    {
        $title = $entry->getTitle();
        $url =  urldecode($entry->getUrl());
        $appendTags = [];
        foreach ($this->extractKeywords as $from => $to) {
            if (strpos($title, $from) !== false) {
                $appendTags = array_merge($appendTags, $to);
            }
            if (strpos($url, $from) !== false && strlen($from) > 3) {
                $appendTags = array_merge($appendTags, $to);
            }
        }
        $tags = array_values($tags);
        $ret = array_unique(array_merge($tags, $appendTags));
        return $ret;
    }

    /**
     * @param array $tags
     * @return array
     */
    public function exchange(array $tags)
    {
        if (!empty($tags)) {
            $altTags = [];
            foreach ($tags as $key => $tag) {
                if (! in_array($tag, $this->exclude)) {
                    foreach ($this->exchange as $from => $to) {
                        if ($tag == $from) {
                            $tag = $to;
                        }
                    }
                    foreach ($this->replace as $from => $to) {
                        $tag = str_replace($from, $to, $tag);
                    }
                    $altTags[] = $tag;
                }
            }
            $tags = array_unique($altTags);
        }
        return $tags;
    }

    /**
     * @param array $tags
     * @return array
     */
    public function optimise(array $tags)
    {
        foreach ($tags as $key => $tag) {
            $tags[$key] = optimise_tag_text($tag);
        }
        return $tags;
    }

    /**
     * @param array $tags
     * @return array
     */
    public function markAsMove(array $tags)
    {
        return hatena_bookmark_try_to_append_tag($tags, "🚪移動");
    }

}