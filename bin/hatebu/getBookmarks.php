<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

// $data = simplexml_load_file(ROOT_DIR . "/_tmp/bookmarks.rss");
$data = simplexml_load_file(ROOT_DIR . "/_tmp/bookmarks.rss");
$client = get_bookmark_api_client();

$no = 0;
foreach($data->item as $datum) {
    $url = (string) $datum->link;
    $res = $client->get("my/bookmark", [
        'query' => ['url' => rawurldecode($url)], 'auth' => 'oauth',
    ]);
    $item = json_decode($res->getBody()->getContents(), true);
    if ($res->getStatusCode() == '200') {
        if (empty($item['tags'])) {
            $no++;
            echo "{$no}【タグなし】" . $item['permalink'] . PHP_EOL;
        }
    }
}