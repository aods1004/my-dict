<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";
$api_key = YOUTUBE_API_KEY;
$channelId = "UCD-miitqNY3nyukJ4Fnf4_A";

$client = new Google_Client();
$client->setDeveloperKey($api_key);
$youtube = new Google_Service_YouTube($client);
$i = 0;
$pageToken = null;

while(1) {
    $searchResponse = $youtube->search->listSearch('id,snippet', array_filter([
        'channelId' => $channelId,
//        'type' => 'video',
        'maxResults' => 50,
        'pageToken' => $pageToken,
        'order' => 'date',
        'safeSearch' => 'none',
        'videoCaption' => 'any',
        'videoEmbeddable' => 'any',
        'videoLicense' => 'any',
        'videoSyndicated' => 'any',
        'videoType' => 'any'
    ]));
    foreach($searchResponse->getItems() as $item) {
        /**
         * @var Google_Service_YouTube_Video $item
         * @var Google_Service_YouTube_ResourceId $id
         */
        $i++;
        $title = $item->getSnippet()->getTitle();
        $id = $item->getId();
        $videoId = $id->getVideoId();
        echo "https://www.youtube.com/watch?v={$videoId}\t{$title}\t$videoId\t$i" . PHP_EOL;
        //echo $title . PHP_EOL;
    };
    $pageToken = $searchResponse->getNextPageToken() ?: null;
    if (! $pageToken) {
        break;
    }
}
