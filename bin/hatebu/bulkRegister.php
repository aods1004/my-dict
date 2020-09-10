<?php
require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
foreach (file(__DIR__ . "/一括登録.tsv") as $row) {
    list($url, $tags) = explode("\t", trim($row));
    $tags = explode(',', $tags);
    foreach ($tags as $key => $tag) {
        $tags[$key] = optimise_tag_text($tag);
    }
    $comment = build_hatena_bookmark_comment(compact('tags'));
    try {
        echo "URL: " . $url . PHP_EOL;
        echo "COMMENT: " . $comment . PHP_EOL;
        $apiClient->post("my/bookmark?url=" . $url, ["form_params" => ["comment" => $comment]]);
    } catch (\Throwable $exception) {
        var_dump($exception);
    }
}