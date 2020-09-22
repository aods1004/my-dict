<?php

use Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\BookmarkEntry;

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
$itemFetcher = new BookmarkApiClient($apiClient);
$tagExchanger = get_tag_exchanger();
$list = get_all_upload_videos_by_channel_id('UCRqBKoKuX30ruKAq05pCeRQ');


foreach (array_reverse($list) as $video) {
    try {
        $url = $video['url'];
        $title = $video['channel_title'] . " / " . $video['title'];
        $item = $itemFetcher->fetch($url) ?: ['tags' => []];
        ob_start();
        echo "##################################################################################" . PHP_EOL;
        echo " + {$title}" . PHP_EOL;
        echo " + {$url}" . PHP_EOL;
        // $title .= $video['description'];
        $entry = new BookmarkEntry(compact('title', 'url'));
        $item['tags'] =  $tagExchanger->extractKeywords($item['tags'], $entry);
        $item['tags'] = $tagExchanger->exchange($item['tags']);
        $item['tags'] = $tagExchanger->optimise($item['tags']);
        list($comment, $tags) = build_hatena_bookmark_comment($item);
        echo " + " . $comment . PHP_EOL;
        $output[] = ob_get_flush();
        $apiClient->post("my/bookmark",  ["form_params" => ["url" => $url, "comment" => $comment]]);
    } catch (Throwable $exception) {
        var_dump($exception);
    }
}
file_put_contents(ROOT_DIR . "/output/output.tsv", implode(PHP_EOL, $output));