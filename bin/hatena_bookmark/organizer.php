<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";
require_once ROOT_DIR . "/bin/hatena_bookmark/lib/tag_exchanger.php";

use \Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\UrlNormalizer;

$tagExchanger = get_tag_exchanger();
$bookmarkApiClient = new BookmarkApiClient(get_bookmark_api_client());

$totalTagCount = 0;
$totalBookmarkCount = 1;
foreach (get_all_bookmarks() as $bookmark) {
    hr("[$totalBookmarkCount]");
    try {
        $url = $bookmark['url'];
        $initUrl = $bookmark['url'];
        // データ取得
        $item = $bookmarkApiClient->fetch($url, $bookmark['tags']);
        $initComment = isset($item['comment_raw']) ? $item['comment_raw'] : "";
        if (empty($item)) {
            hr("SKIP");
            continue;
        }
        // エントリーデータの取得・判定
        $entry = $bookmarkApiClient->fetchEntry($url);
        echo " TITLE: " . mb_substr($entry->getTitle(), 0, 100). PHP_EOL;
        echo " URL: " . $url . PHP_EOL;
        // URLの置き換え
        $url = $entry->takeOverUrl($url);
        // エントリーからのデータ取得処理
        $item['tags'] = $tagExchanger->extractKeywords($item['tags'], $entry);
        // タグ変換処理
        $item['tags'] = $tagExchanger->exchange($item['tags']);
        // Twitter処理
        if (UrlNormalizer::isTwitterUrl($url)) {
            $url = UrlNormalizer::normalizeTwitterUrl($url);
            if (UrlNormalizer::isTwitterUrl($url)) {
                hatena_bookmark_try_to_append_tag($item['tags'], "💬つぶやき");
            }
        }
        // YouTube 処理
        if (UrlNormalizer::isYouTubeVideoUrl($url)) {
            $item['tags'] = hatena_bookmark_try_to_append_tag($item['tags'], "🌐YouTube");
            $url = UrlNormalizer::normalizeYouTubeVideoUrl($url);
        }
        // Amazon 処理
        if (UrlNormalizer::isAmazonProductUrl($url)) {
            $item['tags'] = hatena_bookmark_try_to_append_tag($item['tags'], "🌐Amazon");
            $url = UrlNormalizer::normalizeAmazonProductUrl($url);
        }
        // ハッシュ判定
        $url = UrlNormalizer::removeHash($url);
        // 削除処理
        if ($initUrl != $url) {
            echo " * DEL URL: " . $initUrl . PHP_EOL;
            echo " * NEW URL: " . $url . PHP_EOL;
            $bookmarkApiClient->delete($initUrl);
            $item['tags'] = $tagExchanger->markAsMove($item['tags']);
        }
        // 更新処理
        $comment = build_hatena_bookmark_comment($item);
        if ($initComment != $comment) {
            echo " * UPDATED COMMENT: " . $comment . PHP_EOL;
            $resData = $bookmarkApiClient->post($url, $comment);
            if ($resData['comment_raw'] != $comment) {
                hr("FAILED TO UPDATE");
                echo " * POST COMMENT: " . $comment . PHP_EOL;
                echo " * RES  COMMENT: " . $resData['comment_raw'] . PHP_EOL;
            }
        }
        $totalTagCount += count_tag($comment);
        $totalBookmarkCount += 1;
    } catch (Throwable $exception) {
        var_dump($exception);
        exit;
    }
}
echo "TOTAL BOOKMARK COUNT: " . $totalBookmarkCount . PHP_EOL;
echo "TOTAL TAG COUNT: " . $totalTagCount . PHP_EOL;