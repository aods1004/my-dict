<?php

namespace Aods1004\MyDict\HatenaBookmark;

class Organizer
{
    /**
     * @param $rss
     * @return array
     */
    public static function extractRssData($rss): array
    {
        $initUrl = $rss['url'] ?? "";
        $url = $rss['url'] ?? "";
        $title = $rss['title'] ?? "";
        $tags =  $rss['tags'] ?? [];
        $comment_raw =  $rss['comment_raw'];
        return [$initUrl, $url, $title, $tags, $comment_raw];
    }

    /**
     * @param $i
     */
    public static function outputLineStart($i): void
    {
        if ($i % 100 === 1) {
            echo PHP_EOL;
            echo str_pad($i, 7, " ", STR_PAD_LEFT) . " ";
        }
    }

    /**
     * @param $i
     */
    public static function outputEditHeader($i): void
    {
        $no = str_pad($i, 7, " ", STR_PAD_LEFT);
        echo PHP_EOL . str_pad("### $no ", 100, "#", STR_PAD_RIGHT) . PHP_EOL;
    }

    /**
     * @param $usedTagCount
     */
    public static function recordResults($usedTagCount): void
    {
        asort($usedTagCount, SORT_NUMERIC);
        $usedTagStat = [];
        foreach ($usedTagCount as $tag => $key) {
            $usedTagStat[] = $tag . "\t" . $usedTagCount[$tag];
        }
        $timestamp = date('Y-m-d_Hi');
        $body = implode(PHP_EOL, $usedTagStat);
        file_put_contents(ROOT_DIR . "/_logs/hatebu_used_tags_{$timestamp}.tsv", $body);
        file_put_contents(ROOT_DIR . "/dict/hatebu_tags_use_count.tsv", $body);
        pattern_used_count("", "", true);
    }
}