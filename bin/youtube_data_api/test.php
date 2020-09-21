<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

//$ret = get(
//    "https://www.googleapis.com/youtube/v3/channels",
//    ["query" => [
//        "key" => YOUTUBE_API_KEY,
//        "id" => "UCD-miitqNY3nyukJ4Fnf4_A",
//        //"part" => "snippet,contentDetails,statistics",
//        // "part" => "contentDetails,statistics,topicDetails,status,brandingSettings",
//        "part" => "contentDetails",
//    ]
//    ]);

$ret = get(
    "https://www.googleapis.com/youtube/v3/playlists",
    ["query" => [
        "key" => YOUTUBE_API_KEY,
        "channelId" => "UCD-miitqNY3nyukJ4Fnf4_A",
        "part" => "snippet",
    ]
    ]);

foreach ($ret["items"] as $item) {
    echo "{$item["snippet"]["publishedAt"]} {$item["snippet"]["title"]}" . PHP_EOL;
}

//$ret = get(
//    "https://www.googleapis.com/youtube/v3/playlistItems",
//    ["query" => [
//        "key" => YOUTUBE_API_KEY,
//        "id" => "UCD-miitqNY3nyukJ4Fnf4_A",
//        "part" => "snippet",
//    ]
//    ]);

$ret = get(
    "https://www.googleapis.com/youtube/v3/search", [
        "query" => [
            "key" => YOUTUBE_API_KEY,
            "id" => "UCD-miitqNY3nyukJ4Fnf4_A",
            "part" => "snippet",
            "channelId" => "UCD-miitqNY3nyukJ4Fnf4_A",
            "type" => "video",
            "field" => "snippet.title"
        ]
    ]);

var_dump($ret);
