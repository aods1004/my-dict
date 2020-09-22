<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$api_key = YOUTUBE_API_KEY;
$channelId = "UCD-miitqNY3nyukJ4Fnf4_A";
$part = "snippet";
$page = "";
while (1) {
    $url = "https://www.googleapis.com/youtube/v3/playlists?key={$api_key}&channelId={$channelId}&part=$part&maxResults=50";
    echo $url . PHP_EOL;
    $ret = get($url . "&$page");
    foreach ($ret["items"] as $item) {
        echo "{$item["id"]} {$item["snippet"]["title"]}" . PHP_EOL;
    }
    if (empty($ret["nextPageToken"])) {
        break;
    }
    $page = "pageToken=" . $ret["nextPageToken"];
}
