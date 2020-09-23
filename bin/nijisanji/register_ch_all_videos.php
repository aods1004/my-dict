<?php

use Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\BookmarkEntry;

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
$itemFetcher = new BookmarkApiClient($apiClient);
$tagExchanger = get_tag_exchanger();


// $channel_id = 'UCD-miitqNY3nyukJ4Fnf4_A'; // 月ノ美兎
// $channel_id = 'UCb6ObE-XGCctO3WrjRZC-cw'; // ルイス・キャミー
// $channel_id = 'UCRqBKoKuX30ruKAq05pCeRQ'; // 北小路ヒスイ
$channel_id = 'UC3JF7ZR2uc5j4tJy_kVwGhw'; // Vtuber切り抜キング

$list = get_all_upload_videos_by_channel_id($channel_id);
$exclude_urls = get_exclude_url();

$no = 0;
try {
    foreach (array_reverse($list) as $video) {
        $no++;
        $url = $video['url'];
        $title = $video['channel_title'] . " : " . $video['title'];

        ob_start();
        echo "# No. {$no} ####################################################################################" . PHP_EOL;
        echo " + {$url}" . PHP_EOL;

        // 登録チェック
        $item = $itemFetcher->fetch($url);
        if (! empty($item)) {
            echo " ***** すでに登録されています *****" . PHP_EOL;
            $comment = $item['comment_raw'];
            goto OUTPUT_INFO;
        }
        // タグの生成
        $tags =  $tagExchanger->extractKeywords([],
            new BookmarkEntry(['title' => $title, 'url' => $url]));
        if (count($tags) < 1) {
            echo " ***** 抽出できたタグがないのでスキップします *****" . PHP_EOL;
            goto CLEAN_UP;
        }
        if (isset($exclude_urls[$url])) {
            echo " ***** URLがスキップ対象です *****" . PHP_EOL;
            goto CLEAN_UP;
        }
        $tags = $tagExchanger->exchange($tags);
        $tags = $tagExchanger->optimise($tags);
        list($comment, $tags) = build_hatena_bookmark_comment(compact('tags'));
        $apiClient->post("my/bookmark",  ["form_params" => ["url" => $url, "comment" => $comment]]);

        OUTPUT_INFO:
        echo " + " . get_hatebu_entry_url($url) . PHP_EOL;
        echo " + {$title}" . PHP_EOL;
        echo " + " . $comment . PHP_EOL;
        CLEAN_UP:
        echo PHP_EOL;
        $output[] = ob_get_flush();
    }
} catch (Throwable $exception) {
    var_dump($exception);
}
array_reverse($output);
file_put_contents(ROOT_DIR . "/output/output.tsv", implode(PHP_EOL, $output));