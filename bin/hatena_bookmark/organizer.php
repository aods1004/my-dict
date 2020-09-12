<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";
require_once ROOT_DIR . "/bin/hatena_bookmark/lib/tag_exchanger.php";

use \Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\UrlNormalizer;

$tagExchanger = get_tag_exchanger();
$bookmarkApiClient = new BookmarkApiClient(get_bookmark_api_client());

$totalTagCount = 0;
$totalBookmarkCount = 1;
foreach (get_all_bookmarks(true) as $bookmark) {
    echo "#### [$totalBookmarkCount] ####################################################" . PHP_EOL;
    try {
        $url = $bookmark['url'];
        $initUrl = $bookmark['url'];
        echo " URL: {$url}" . PHP_EOL;
        // データ取得
        $item = $bookmarkApiClient->fetch($url, $bookmark['tags']);
        $initComment = isset($item['comment_raw']) ? $item['comment_raw'] : "";
        if (empty($item)) {
            echo " ***** FAIL TO FETCH ITEM *****";
            continue;
        }
        // エントリーデータの取得・判定
        $entry = $bookmarkApiClient->fetchEntry($url);
        // URLの置き換え
        $url = $entry->takeOverUrl($url);
        // エントリーからのデータ取得処理
        $item['tags'] = $tagExchanger->extractKeywords($item['tags'], $entry);
        // タグ変換処理
        $item['tags'] = $tagExchanger->exchange($item['tags']);
        // Twitter処理
        if (UrlNormalizer::isTwitterUrl($url)) {
            $url = UrlNormalizer::normalizeTwitterUrl($url);
            if (UrlNormalizer::isTweetUrl($url)) {
                $item['tags'] = hatena_bookmark_try_to_append_tag($item['tags'], "💬つぶやき");
            } else {
                $item['tags'] = hatena_bookmark_try_to_append_tag($item['tags'], "🌐Twitter");
            }
        }
        // YouTube 処理
        if (UrlNormalizer::isYouTubeVideoUrl($url)) {
            $url = UrlNormalizer::normalizeYouTubeVideoUrl($url);
        }
        // Amazon 処理
        if (UrlNormalizer::isAmazonProductUrl($url)) {
            $url = UrlNormalizer::normalizeAmazonProductUrl($url);
        }
        // ハッシュ判定
        $url = UrlNormalizer::removeHash($url);
        // 削除処理
        if ($initUrl != $url) {
            echo " * DEL URL: $initUrl" . PHP_EOL;
            echo " * NEW URL: $url" . PHP_EOL;
            $bookmarkApiClient->delete($initUrl);
            $item['tags'] = $tagExchanger->markAsMove($item['tags']);
        }
        // 更新処理
        if (count($item['tags']) > 10) {
            echo " ***** タグの数が多いです *****" . PHP_EOL;
        }
        list($comment, $tags) = build_hatena_bookmark_comment($item);
        if (count_helpful_tag($tags) < 1) {
            echo " ***** タグの数が少ないです *****" . PHP_EOL;
        }
        if ($initComment != $comment) {
            echo " TITLE: " . mb_substr($entry->getTitle(), 0, 100). PHP_EOL;
            $resData = $bookmarkApiClient->post($url, $comment);
            if ($resData['comment_raw'] != $comment) {
                echo " ***** FAILED TO UPDATE *****" . PHP_EOL;
                echo " POST COMMENT: " . $comment . PHP_EOL;
                echo " RES  COMMENT: " . $resData['comment_raw'] . PHP_EOL;
            } else {
                echo " UPDATED COMMENT: " . $comment . PHP_EOL;
            }
        }
        $totalTagCount += count_helpful_tag($tags);
        $totalBookmarkCount += 1;
    } catch (Throwable $exception) {
        var_dump($exception);
        exit;
    }
}
echo "TOTAL BOOKMARK COUNT: " . $totalBookmarkCount . PHP_EOL;
echo "TOTAL TAG COUNT: " . $totalTagCount . PHP_EOL;