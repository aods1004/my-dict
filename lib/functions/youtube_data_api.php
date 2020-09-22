<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

define('YOUTUBE_TSUKINO_MITO_CH_ID', "UCD-miitqNY3nyukJ4Fnf4_A");

function get_all_upload_videos_by_channel_id($channelId) {
    $client = new Google_Client();
    $client->setDeveloperKey(YOUTUBE_API_KEY);
    $youtube = new Google_Service_YouTube($client);
    $response = $youtube->channels->listChannels('id,snippet,contentDetails');
    $channel_title = '';
    $upload_list_id = null;
    foreach($response->getItems() as $item) {
        $upload_list_id =
            $item->getContentDetails()
                ->getRelatedPlaylists()
                ->getUploads();
        $channel_title =
            $item->getSnippet()->getTitle();
    }
    if (empty($upload_list_id)) {
        return [];
    }
    $ret = [];
    $pageToken = null;
    $part = 'id,snippet,contentDetails';
    while(1) {
        $response = $youtube->playlistItems->listPlaylistItems($part, array_filter([
            'playlistId' => $upload_list_id,
            'maxResults' => 50,
            'pageToken' => $pageToken,
        ]));
        foreach($response->getItems() as $item) {
            $title = $item->getSnippet()->getTitle();
            $id = $item->getContentDetails()->getVideoId();
            $url = "https://www.youtube.com/watch?v=" . $id;
            $ret[] = compact('url', 'id', 'title', 'channel_title');
        };
        $pageToken = $response->getNextPageToken() ?: null;
        if (! $pageToken) {
            break;
        }
    }
}



