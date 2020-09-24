<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

use \Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\UrlNormalizer;

START:
$tagExchanger = get_tag_exchanger();
$bookmarkApiClient = new BookmarkApiClient(get_bookmark_api_client(), new PDO(DSN_BOOKMARK));

$totalTagCount = 0;
$totalBookmarkCount = 1;
$usedTagCount = [];
foreach (get_all_bookmarks() as $bookmark) {
    echo "***** [$totalBookmarkCount] ****************************************************" . PHP_EOL;
    try {
        $url = $bookmark['url'];
        $initUrl = $bookmark['url'];
        // データ取得
        $item = $bookmarkApiClient->fetch($url, $bookmark['tags']);
        if (empty($item)) {
            echo " ##### FAIL TO FETCH ITEM #####" . PHP_EOL;
            echo " * URL: {$url}" . PHP_EOL;
            continue;
        }
        $initComment = isset($item['comment_raw']) ? $item['comment_raw'] : "";
        // エントリーデータの取得・判定
        $entry = $bookmarkApiClient->fetchEntry($url);
        if (empty($entry)) {
            echo " ##### FAIL TO FETCH ENTRY #####" . PHP_EOL;
            echo " * URL: {$url}" . PHP_EOL;
            continue;
        }
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
        // TikTok 処理
        if (UrlNormalizer::isTikTokUrl($url)) {
            $url = UrlNormalizer::normalizeTikTokUrl($url);
        }
        // ハッシュ判定
        $url = UrlNormalizer::removeHash($url);
        // 削除処理
        if ($initUrl != $url) {
            echo " * DEL URL: $initUrl" . PHP_EOL;
            echo " * NEW URL: $url" . PHP_EOL;
            $ret = $bookmarkApiClient->delete($initUrl);
            echo " * DELETE STATUS CODE: " . $ret->getStatusCode() . PHP_EOL;
        }
        list($comment, $tags) = build_hatena_bookmark_comment($item);
        if (count_helpful_tag($tags ?: []) < 1) {
            echo " URL: {$url}" . PHP_EOL;
            echo " ***** タグの数が少ないです *****" . PHP_EOL;
        }
        if ($initComment != $comment && ! empty($comment)) {
            echo " URL: {$url}" . PHP_EOL;
            echo " TITLE: " . mb_substr($entry->getTitle(), 0, 100) . PHP_EOL;
            $resData = $bookmarkApiClient->put($url, $comment, $tags);
            if ($resData['comment_raw'] != $comment) {
                echo " ***** FAILED TO UPDATE *****" . PHP_EOL;
                echo " POST COMMENT: " . $comment . PHP_EOL;
                echo " RES  COMMENT: " . $resData['comment_raw'] . PHP_EOL;
            } else {
                echo " UPDATED COMMENT: " . $comment . PHP_EOL;
                echo " BEFORE  COMMENT: " . $initComment . PHP_EOL;
            }
        }
        foreach ($tags ?: [] as $tag) {
            $usedTagCount[$tag] = (empty($usedTagCount[$tag])) ? 1 : ($usedTagCount[$tag] + 1);
        }
        $totalTagCount += count_helpful_tag($tags ?: []);
        $totalBookmarkCount += 1;
    } catch (Throwable $exception) {
        var_dump($exception);
        exit;
    }
}
echo "==========================================================" . PHP_EOL;
echo "  TOTAL BOOKMARK COUNT: " . $totalBookmarkCount . PHP_EOL;
echo "==========================================================" . PHP_EOL;
$apiClient = get_bookmark_api_client();
$res = $apiClient->get("my/tags");
$registeredTags = [];
$myTags = json_decode($res->getBody()->getContents(), true);
foreach ($myTags['tags'] as $item) {
    $registeredTags[] = $item['tag'];
}
$usedTags = array_keys($usedTagCount);
sort($usedTags);
sort($registeredTags);
$timestamp = date('Y-m-d_Hi');
file_put_contents(ROOT_DIR . "/_logs/hatebu_used_tags_{$timestamp}.txt", implode(PHP_EOL, $usedTags));
file_put_contents(ROOT_DIR . "/_logs/hatebu_diff_tags_{$timestamp}.txt", "" .
    implode(PHP_EOL, array_diff($registeredTags, $usedTags)) . PHP_EOL .
    "------------------------------------------------------------" . PHP_EOL .
    implode(PHP_EOL, array_diff($usedTags, $registeredTags)) . PHP_EOL);

goto START;

