<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";
/**
 * @return Google_Service_YouTube
 */
function get_youtube_client()
{
    static $youtubeClients;
    if (! $youtubeClients) {
        foreach (YOUTUBE_API_LIST as $key) {
            $client = new Google_Client();
            $client->setDeveloperKey($key);
            $youtube = new Google_Service_YouTube($client);
            $youtube->status = true;
            $youtubeClients[] = $youtube;
        }
    }
    while (1) {
        $client = array_shift($youtubeClients);
        if ($client->status) {
            array_push($youtubeClients, $client);
            return $client;
        }
        if (count($youtubeClients) < 1) {
            break;
        }
    }
    throw new Error("YouTubeクライントを準備できませんでした");
}

function get_youtube_video_cache_db()
{
    static $pdo;
    if (empty($pdo)) {
        $pdo = new PDO(DSN_YOUTUBE_VIDEO);
    }
    return $pdo;
}

function get_youtube_video_cache($id)
{
    $pdo = get_youtube_video_cache_db();
    $st = $pdo->prepare("select data from video where id = :id");
    $st->bindValue(":id", $id);
    $st->execute();
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return isset($row['data']) ? json_decode($row['data'], true) : null;
}

function set_youtube_video_cache($id, $data)
{
    $pdo = get_youtube_video_cache_db();
    $st = $pdo->prepare("replace into video (id, data, create_ts) values (:id, :data, :create_ts)");
    $st->bindValue(":id", $id);
    $st->bindValue(":data", json_encode($data));
    $st->bindValue(":create_ts", time());
    $st->execute();
}

function set_cache($key1, $key2, $data, $width = "unlimited") {
    $dir = ROOT_DIR . "/_cache/$width/$key1/";
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }
    $path = $dir . "/" . md5(json_encode($key2)) . ".json";
    file_put_contents($path, json_encode($data));
}
function get_cache($key1, $key2, $width = "unlimited")
{
    $dir = ROOT_DIR . "/_cache/$width/$key1/";
    $path = $dir . "/" . md5(json_encode($key2)) . ".json";
    if (! is_file($path)) {
        return false;
    }
    $data = file_get_contents($path);
    if (empty($data)) {
        return false;
    }
    try {
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        return false;
    }
}


/**
 * @param $channel_id
 * @return array
 */
function get_all_upload_videos_by_channel_id($channel_id)
{
    START:
    try {
        $upload_list_id = get_cache(__FUNCTION__, $channel_id);
        if (! $upload_list_id) {
            $youtube = get_youtube_client();
            $part = 'contentDetails';
            $response = $youtube->channels->listChannels($part, ['id' => $channel_id]);
            $upload_list_id = null;
            foreach($response->getItems() as $item) {
                $upload_list_id =
                    $item->getContentDetails()
                        ->getRelatedPlaylists()
                        ->getUploads();
            }
            if (empty($upload_list_id)) {
                return [];
            }
            set_cache(__FUNCTION__, $channel_id, $upload_list_id);
        }
        return get_all_upload_videos_by_playlist_id($upload_list_id);;
    } catch (Throwable $exception) {
        if (isset($youtube) && isset($youtube->status)) {
            $youtube->status = false;
            goto START;
        }
        var_dump($exception);
        exit;
    }
}

/**
 * @param array $list
 * @return array
 */
function get_all_upload_videos_by_channel_ids(array $list)
{
    try {
        $ret = [];
        foreach ($list as $id) {
            echo "LOAD: " . $id . PHP_EOL;
            foreach (get_all_upload_videos_by_channel_id($id) ?? [] as $item) {
                $ret[] = $item;
            }
        }
        return $ret;
    } catch (Throwable $exception) {
        var_dump($exception);
        exit;
    }
}

/**
 * @param $channel_id
 * @return array
 * @throws Throwable
 */
function get_all_search_result($channel_id)
{
    START:
    try {
        $youtube = get_youtube_client();
        $part = 'id,snippet,contentDetails';
        $response = $youtube->search->listSearch($part, ['id' => $channel_id]);
        $upload_list_id = null;
        foreach($response->getItems() as $item) {
            $upload_list_id =
                $item->getContentDetails()
                    ->getRelatedPlaylists()
                    ->getUploads();
        }
        if (empty($upload_list_id)) {
            return [];
        }
    } catch (Throwable $exception) {
        if (isset($youtube) && isset($youtube->status)) {
            $youtube->status = false;
            goto START;
        }
        throw $exception;
    }
    return get_all_upload_videos_by_playlist_id($upload_list_id);
}

/**
 * @param $playlist_id
 * @return array
 * @throws Throwable
 */
function get_all_upload_videos_by_playlist_id($playlist_id) {
    $ret = get_cache(__FUNCTION__, $playlist_id, date("Ymd")) ?? [];
    if (! $ret) {
        $pageToken = null;
        $part = 'id,contentDetails';
        while(1) {
            START:
            try {
                $youtube = get_youtube_client();
                $response = $youtube->playlistItems->listPlaylistItems($part, array_filter([
                    'playlistId' => $playlist_id,
                    'maxResults' => 50,
                    'pageToken' => $pageToken,
                ]));
                foreach ($response->getItems() as $item) {
                    $data = get_youtube_video($item->getContentDetails()->getVideoId());
                    if ($data) {
                        $ret[] = $data;
                    }
                }
                $pageToken = $response->getNextPageToken() ?: null;
                if (!$pageToken) {
                    break;
                }
            } catch (Throwable $exception) {
                if (isset($youtube) && isset($youtube->status)) {
                    $youtube->status = false;
                    goto START;
                }
                throw $exception;
            }
        }
        set_cache(__FUNCTION__, $playlist_id, $ret,date("Ymd"));
    }
    return $ret;
}

function get_youtube_video($id)
{
    $data = get_youtube_video_cache($id) ?: null;
    if (! empty($data)) {
        if (! empty($data['title']) && !empty($data['description'])) {
            return $data;
        }
        return null;
    }
    $youtube = get_youtube_client();
    $videoResponse = $youtube->videos->listVideos('snippet', array_filter([
        'id' => $id,
    ]));
    foreach ($videoResponse->getItems() as $video) {
        $published_at = strtotime($video->getSnippet()->getPublishedAt());
        $title = $video->getSnippet()->getTitle();
        $description = $video->getSnippet()->getDescription();
        $channel_title = $video->getSnippet()->getChannelTitle();
        $url = "https://www.youtube.com/watch?v=" . $id;

        if (!empty($title) && ! empty($description) && ! empty($published_at)) {
            $data = compact('url', 'id', 'title', 'channel_title', 'description', 'published_at');
            set_youtube_video_cache($id, $data);
            return $data;
        }
    }
    return null;
}

/**
 *
 * ----------------------------------------------------------------------------------------------
 */

function get_exclude_url() {
    $ret = [];
    foreach (load_csv(ROOT_DIR . "/data/exclude_url.tsv") as $row) {
        if (isset($row[0])) {
            $ret[trim($row[0])] = true;
        }
    }
    return $ret;
}

function is_exclude_url($url): bool
{
    static $stmt;
    if (! $stmt) {
        $pdo = new PDO(DSN_BOOKMARK_EXCLUDE_URL);
        $stmt = $pdo->prepare("select 1 from exclude_url where url = :url");
    }
    $stmt->bindValue(":url", $url);
    $stmt->execute();
    $ret = $stmt->fetch();
    return ! empty($ret);
}
