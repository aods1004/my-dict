<?php

use Aods1004\MyDict\BookmarkApiClient;

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
$itemFetcher = new BookmarkApiClient($apiClient);
$tagExchanger = get_tag_exchanger();
$no = 0;
foreach (load_csv(ROOT_DIR . "/data/hatebu_bulk_register.tsv") as $row) {
    try {
        echo "### [$no] ######################################################" . PHP_EOL;
        [$url, $tags] = $row;
        echo "URL: " . $url . PHP_EOL;
        $tags = explode(',', $tags);
        if ($itemFetcher->exist($url)) {
            echo " ***** すでに登録されています ($url) *****" . PHP_EOL;
            continue;
        }
        $status = check_url_status($url);
        if ((int) $status !== 200) {
            echo " ***** 対象ページがありません ($status) *****" . PHP_EOL;
            continue;
        }
        $tags = $tagExchanger->exchange($tags);
        $tags = $tagExchanger->optimise($tags);
        list($comment, $tags) = build_hatena_bookmark_comment(compact('tags'));
        echo "COMMENT: " . $comment . PHP_EOL;
        $apiClient->post("my/bookmark",  ["form_params" => ["url" => $url, "comment" => $comment]]);
    } catch (Throwable $exception) {
        var_dump($exception);
    }
}