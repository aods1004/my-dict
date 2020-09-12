<?php

use Aods1004\MyDict\BookmarkApiClient;

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
$itemFetcher = new BookmarkApiClient($apiClient);
$tagExchanger = get_tag_exchanger();
$no = 0;
foreach (load_tsv(file(__DIR__ . "/data/一括登録.tsv")) as $row) {
    try {
        hr('[' . ++ $no . ']');
        echo "URL: " . $url . PHP_EOL;
        list($url, $tags) = $row;
        $tags = explode(',', $tags);
        if ($itemFetcher->fetch($url)) {
            continue;
        }
        $tags = $tagExchanger->exchange($tags);
        $tags = $tagExchanger->optimise($tags);
        $comment = build_hatena_bookmark_comment(compact('tags'));
        echo "COMMENT: " . $comment . PHP_EOL;
        $apiClient->post("my/bookmark?url=" . $url, ["form_params" => ["comment" => $comment]]);
    } catch (Throwable $exception) {
        var_dump($exception);
    }
}