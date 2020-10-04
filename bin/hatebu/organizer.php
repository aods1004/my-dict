<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

use \Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\UrlNormalizer;
use Aods1004\MyDict\HatenaBookmark\Organizer as Sub;

START:
$bookmarkApiClient = new BookmarkApiClient(get_bookmark_api_client(), new PDO(DSN_BOOKMARK));

$totalTagCount = 0;
$totalBookmarkCount = 1;
$usedTagCount = [];


foreach (get_all_bookmarks() as $rss) {
    Sub::outputLineStart($totalBookmarkCount);
    try {
        [$init_url, $url, $title, $tags, $comment_raw] = Sub::extractRssData($rss);
        $cache_check_basis = strtotime("-1 days");
        if ($bookmarkApiClient->beNotChange($url, $tags, $comment_raw, $cache_check_basis)) {
            echo "S";
            $item = $rss;
            $init_comment = $rss['comment_raw'];
        } else {
            echo ".";
            $item = $bookmarkApiClient->fetch($url, $tags);
            if ($item === null) {
                echo " ##### FAIL TO FETCH BOOKMARK #####" . PHP_EOL;
                echo " * URL: {$url}" . PHP_EOL;
                continue;
            }
            // エントリーデータの取得・判定
            $entry = $bookmarkApiClient->fetchEntry($url);
            if ($entry === null) {
                echo " ##### FAIL TO FETCH ENTRY #####" . PHP_EOL;
                echo " * URL: {$url}" . PHP_EOL;
                continue;
            }
            // URLの置き換え
            $url = $entry->takeOverUrl($url);
            $init_comment = $item['comment_raw'];
        }
        $url = UrlNormalizer::normalize($url);
        $item['tags'] = create_tags($url, $title, $item['tags']);
        $overflowTagsCountFlag = (count_helpful_tag($item['tags']) > 10);
        [$comment, $tags] = build_hatena_bookmark_comment($item);

        foreach ($tags ?: [] as $tag) {
            $usedTagCount[$tag] = (empty($usedTagCount[$tag])) ? 1 : ($usedTagCount[$tag] + 1);
        }

        $totalTagCount += count_helpful_tag($tags ?: []);
        ++$totalBookmarkCount;

        $moveUrlFlag = ($init_url !== $url);
        $lessTagCountFlag = (count_helpful_tag($tags ?: []) < 1);
        $changeCommentFlag = ($init_comment !== $comment && !empty($comment));

        if (!$moveUrlFlag && !$lessTagCountFlag && !$changeCommentFlag) {
            continue;
        }

        Sub::outputEditHeader($totalBookmarkCount);
        if ($moveUrlFlag) {
            echo " * AFTER  URL: $url" . PHP_EOL;
            echo " * BEFORE URL: $init_url" . PHP_EOL;
            $ret = $bookmarkApiClient->delete($init_url);
            echo " * DELETE STATUS CODE: " . $ret->getStatusCode() . PHP_EOL;
        }
        if ($lessTagCountFlag || $changeCommentFlag) {
            $entryUrl = get_hatebu_entry_url($url);
            echo " URL: {$entryUrl}" . PHP_EOL;
            if ($lessTagCountFlag) {
                $count = count_helpful_tag($tags);
                echo "※※※ タグの数が少ないです ($count) ※※※" . PHP_EOL;
            }
            if ($changeCommentFlag) {
                echo " TITLE: " . $title . PHP_EOL;
                if ($overflowTagsCountFlag) {
                    $count = count_helpful_tag($item['tags']);
                    echo "※※※ タグの数が多いです ($count) ※※※" . PHP_EOL;
                } else {
                    $bookmarkApiClient->put($url, $comment, $tags);
                    echo " AFTER  COMMENT: " . $comment . PHP_EOL;
                    echo " BEFORE COMMENT: " . $init_comment . PHP_EOL;
                }
            }
        }
    } catch (Throwable $exception) {
        var_dump($exception);
        exit;
    }
}

Sub::recordResults($usedTagCount);

goto START;

