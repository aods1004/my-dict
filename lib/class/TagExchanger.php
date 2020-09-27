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
     * @var array
     */
    private $redundant = [];
    /**
     * TagExchanger constructor.
     * @param array $extractKeywords
     * @param array $exchange
     * @param array $replace
     * @param array $exclude
     * @param array $redundant
     */
    public function __construct(array $extractKeywords, array $exchange, array $replace, array $exclude, array $redundant)
    {
        $this->extractKeywords = $extractKeywords;
        $this->exchange = $exchange;
        $this->exclude = $exclude;
        $this->replace = $replace;
        $this->redundant = $redundant;
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

    public function removeRedundant(array $tags)
    {
        $unnecessaries = [];
        foreach ($this->redundant as $item) {
            $necessary = trim($item['necessary']);
            $unnecessary = trim($item['unnecessary']);
            if (in_array($necessary, $tags) && in_array($unnecessary, $tags)) {
                $unnecessaries[] = $unnecessary;
            }
        }
        return array_values(array_diff($tags, $unnecessaries));
    }
}