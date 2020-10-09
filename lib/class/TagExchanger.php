<?php

namespace Aods1004\MyDict;

class TagExchanger
{
    /**
     * @var array
     */
    private array $extractKeywords;
    /**
     * @var array
     */
    private array $exchange;
    /**
     * @var array
     */
    private array $exclude;
    /**
     * @var array
     */
    private array $replace;
    /**
     * @var array
     */
    private array $redundant;
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
     * @param $title
     * @param $url
     * @return array
     */
    public function extractKeywords($tags, $title, $url): array
    {
        $haystack = $title . " " . urldecode($url);
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
        return array_unique(array_merge($tags, $appendTags));
    }

    /**
     * @param $haystack
     * @param $wordStr
     * @return bool
     */
    private function checkIncludeWord($haystack, $wordStr): bool
    {
        $words = array_filter(explode(',', $wordStr));
        $count = count($words);
        $result = 0;
        if ($count) {
            foreach ($words as $word) {
                if (strpos($haystack, $word) !== false) {
                    ++$result;
                }
            }
        }
        return ($result > 0 && $count === $result);
    }

    /**
     * @param $haystack
     * @param array $exclude
     * @return bool
     */
    private function checkExcludeWord($haystack, array $exclude): bool
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
    public function exchange(array $tags): array
    {
        if (!empty($tags)) {
            $altTags = [];
            foreach ($tags as $key => $tag) {
                if (! in_array($tag, $this->exclude, true)) {
                    foreach ($this->exchange as $from => $to) {
                        if ($tag === $from) {
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
    public function optimise(array $tags): array
    {
        foreach ($tags as $key => $tag) {
            $tags[$key] = optimise_tag_text($tag);
        }
        return $tags;
    }
    public function removeRedundant(array $tags): array
    {
        $unnecessaries = [];
        foreach ($this->redundant as $item) {
            $necessary = trim($item['necessary']);
            $unnecessary = trim($item['unnecessary']);
            if (in_array($necessary, $tags, true) && in_array($unnecessary, $tags, true)) {
                $unnecessaries[] = $unnecessary;
            }
        }
        return array_values(array_diff($tags, $unnecessaries));
    }

    public function organize(array $tags): array
    {
        usort($tags, 'tag_compare');
    }

}