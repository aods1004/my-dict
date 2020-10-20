<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

use \Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\UrlNormalizer;
use Aods1004\MyDict\HatenaBookmark\Organizer as Sub;

empty_file("hatebu_set_titles.txt");

START:
$bookmarkApiClient = new BookmarkApiClient(get_bookmark_api_client(), new PDO(DSN_BOOKMARK));
Sub::setBookmarkApiClient($bookmarkApiClient);

$totalTagCount = 0;
$totalBookmarkCount = 1;
$usedTagCount = [];
$urls = [];

foreach (get_all_bookmarks() as $rss) {
    Sub::outputLineStart($totalBookmarkCount);
    ++$totalBookmarkCount;
    [$init_url, $url, $title, $tags, $comment_raw] = Sub::extractRssData($rss);

    $load_from_hatena_flag = false;
    if (Sub::checkNotChange($url, $tags, $comment_raw)) {
        echo ".";
        $item = $rss;
        $init_comment = $rss['comment_raw'];
    } else {
        echo "L";
        $item = $bookmarkApiClient->fetch($url, $tags);
        if ($item === null) {
            echo "※※※ ブックマークの取得に失敗しました ※※※" . PHP_EOL;
            echo " * URL: {$url}" . PHP_EOL;
            continue;
        }
        // エントリーデータの取得・判定
        $entry = $bookmarkApiClient->fetchEntry($url);
        if ($entry === null) {
            echo "※※※ エントリーの取得に失敗しました ※※※" . PHP_EOL;
            echo " * URL: {$url}" . PHP_EOL;
            continue;
        }
        // URLの置き換え
        $url = $entry->takeOverUrl($url);
        $init_comment = $item['comment_raw'];
        $load_from_hatena_flag = true;
    }

//    if (isset($urls[$url]) && $urls[$url] !== $rss['bookmark_url']) {
//        echo PHP_EOL . "※※※ URLが重複しています ※※※" . PHP_EOL;
//        echo "TITLE: {$rss['title']}" . PHP_EOL;
//        echo "URL: $url" . PHP_EOL;
//        echo "INIT URL(1): {$urls[$url]}" . PHP_EOL;
//        echo "INIT URL(2): {$rss['bookmark_url']}" . PHP_EOL;
//        echo "ENTRY URL: " . get_hatebu_entry_url($url) . PHP_EOL;
//        continue;
//    }

    $alt_title = get_alt_title($title, $url);
    if ($alt_title) {
        echo PHP_EOL . "※※※ TITLEがうまく設定されていません ※※※" . PHP_EOL;
        echo "TITLE: {$rss['title']}" . PHP_EOL;
        $entry_url = get_hatebu_entry_url($url) . "#" . rawurlencode($alt_title);
        echo "ENTRY URL: " . tee($entry_url, "hatebu_set_titles.txt") . PHP_EOL;
    }

    $url = UrlNormalizer::normalize($url);
    $urls[$url] = $rss['bookmark_url'] ?? true;
    $item['tags'] = create_tags($url, $title, $item['tags']);
    [$comment, $tags] = build_hatena_bookmark_comment($item);
    foreach ($tags ?: [] as $tag) {
        $usedTagCount[$tag] = (empty($usedTagCount[$tag])) ? 1 : ($usedTagCount[$tag] + 1);
    }
    $totalTagCount += count_helpful_tag($tags ?: []);
    $moveUrlFlag = ($init_url !== $url);
    $less_tag_count_flag = (count_helpful_tag($tags ?: []) < 1);
    $change_comment_flag = ($init_comment !== $comment && !empty($comment));
    if (!$moveUrlFlag && !$less_tag_count_flag && !$change_comment_flag && !$load_from_hatena_flag) {
        continue;
    }
    Sub::outputEditHeader($totalBookmarkCount);
    if ($less_tag_count_flag || $change_comment_flag || $load_from_hatena_flag) {
        $entryUrl = get_hatebu_entry_url($url);
        echo " URL:   {$entryUrl}" . PHP_EOL;
        if ($moveUrlFlag) {
            echo "※※※ URL 正規化前のブックマークを削除します ※※※" . PHP_EOL;
            echo " * DELETE URL: $init_url" . PHP_EOL;
            $ret = $bookmarkApiClient->delete($init_url);
        }
        echo " TITLE: {$title}" . PHP_EOL;
        if ($less_tag_count_flag) {
            $count = count_helpful_tag($tags);
            echo "※※※ タグの数が少ないです ($count) ※※※" . PHP_EOL;
        }
        if ($load_from_hatena_flag) {
            echo "※※※ はてなからデータがロードされました。 ※※※" . PHP_EOL;
        }
        if ($change_comment_flag || $load_from_hatena_flag) {
            $bookmarkApiClient->put($url, $comment, $tags);
        }
        echo " AFTER  COMMENT: " . $comment . PHP_EOL;
        echo " BEFORE COMMENT: " . $init_comment . PHP_EOL;
    }
}
Sub::recordResults($usedTagCount);

goto START;

