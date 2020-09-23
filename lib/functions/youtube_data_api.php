<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

function get_youtube_client()
{
    static $youtube;
    if (! $youtube) {
        $client = new Google_Client();
        $client->setDeveloperKey(YOUTUBE_API_KEY);
        $youtube = new Google_Service_YouTube($client);
    }
    return $youtube;
}

/**
 * @param $channel_id
 * @return array
 */
function get_all_upload_videos_by_channel_id($channel_id) {

    $youtube = get_youtube_client();
    $part = 'id,snippet,contentDetails';
    $response = $youtube->channels->listChannels($part, ['id' => $channel_id]);
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
    return get_all_upload_videos_by_playlist_id($upload_list_id, $channel_title);
}

/**
 * @param $playlist_id
 * @param null $channel_title
 * @return array
 */
function get_all_upload_videos_by_playlist_id($playlist_id, $channel_title = null) {
    $youtube = get_youtube_client();
    $ret = [];
    $pageToken = null;
    $part = 'id,snippet,contentDetails';
    while(1) {
        $response = $youtube->playlistItems->listPlaylistItems($part, array_filter([
            'playlistId' => $playlist_id,
            'maxResults' => 50,
            'pageToken' => $pageToken,
        ]));
        foreach($response->getItems() as $item) {
            $title = $item->getSnippet()->getTitle();
            $description = $item->getSnippet()->getDescription();
            $id = $item->getContentDetails()->getVideoId();
            $url = "https://www.youtube.com/watch?v=" . $id;
            $ret[] = compact('url', 'id', 'title', 'channel_title', 'description');
        };
        $pageToken = $response->getNextPageToken() ?: null;
        if (! $pageToken) {
            break;
        }
    }
    return $ret;
}

/**
 *
 * ----------------------------------------------------------------------------------------------
 */

function get_exclude_url() {
    $ret = [];
    foreach (load_tsv(ROOT_DIR . "/data/exclude_url.tsv") as $row) {
        if (isset($row[0])) {
            $ret[trim($row[0])] = true;
        }
    }
    return $ret;
}
