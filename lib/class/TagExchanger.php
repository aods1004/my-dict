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
        $haystack = $entry->getTitle() . " " . urldecode($entry->getUrl());
        $appendTags = [];
        foreach ($this->extractKeywords as $from => $toItems) {
            foreach ($toItems as $item) {
                $to = $item['to'];
                if ($this->checkIncludeWord($haystack, $from)) {
                    if ($this->checkExcludeWord($haystack, $item['exclude'])) {
                        $tags = array_filter($tags, function ($value) use ($to) {
                            return ($value != $to);
                        });
                    } else {
                        $appendTags[] = $to;
                    }
                }
            }
        }
        $tags = array_values($tags);
        $appendTags = array_values($appendTags);
        $ret = array_unique(array_merge($tags, $appendTags));
        return $ret;
    }

    /**
     * @param $haystack
     * @param $wordStr
     * @return bool
     */
    private function checkIncludeWord($haystack, $wordStr)
    {
        $words = array_filter(explode(',', $wordStr));
        $count = count($words);
        $result = 0;
        if ($count) {
            foreach ($words as $word) {
                if (strpos($haystack, $word) !== false) {
                    $result += 1;
                }
            }
        }
        return ($result > 0 && $count == $result);
    }

    private function checkExcludeWord($haystack, array $exclude)
    {
        $flag = false;
        if ($exclude) {
            foreach ($exclude as $exclude_word) {
                if (strpos($haystack, $exclude_word) !== false) {
                    $flag = true;
                }
            }
        }
        return $flag;
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
}