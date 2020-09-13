<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

require_once dirname(__DIR__) . "/vendor/autoload.php";

function get_http_client()
{
    return new Client(['http_errors' => false,]);
}

function array_equal($a, $b)
{
    if (array_diff($a, $b) || array_diff($b, $a)) {
        return false;
    }
    return true;
}

function load_tsv($file_path)
{
    $file = file($file_path);
    foreach ($file as $row) {
        if (substr($row, 0, 1) === '!' || empty(trim($row))) {
            continue;
        }
        yield explode("\t", trim($row));
    }
}

function get_bookmark_feed_client($account)
{
    return new Client(['base_uri' => 'https://b.hatena.ne.jp/' . $account . '/']);
}

function get_all_bookmarks($useOfflineRss = false)
{
    if ($useOfflineRss) {
        // download from https://b.hatena.ne.jp/-/my/config/data_management
        $data = simplexml_load_file(ROOT_DIR . "/_tmp/" . HATENA_MY_ACCOUNT . ".bookmarks.rss");
        foreach (isset($data->item) ? $data->item : [] as $item) {
            $tags = [];
            $ns = $item->getNamespaces(true);
            $dc = $item->children($ns["dc"]);
            $subjects = isset($dc->subject) ? $dc->subject : [];
            foreach ($subjects as $tag) {
                $tags[] = (string)$tag;
            }
            yield [
                'url' => strval($item->link),
                'title' => strval($item->title),
                'tags' => $tags
            ];
        }
    } else {
        $client = get_bookmark_feed_client(HATENA_MY_ACCOUNT);
        $page = 1;
        while (true) {
            try {
                $ret = $client->get("rss", ['query' => ["page" => $page++]]);
            } catch (Throwable $exception) {
                var_dump($exception);
                break;
            }
            $data = simplexml_load_string($ret->getBody()->getContents());
            $items = isset($data->item) ? $data->item : [];
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                $tags = [];
                $ns = $item->getNamespaces(true);
                foreach ($item->children($ns["dc"])->subject ?: [] as $tag) {
                    $tags[] = $tag;
                }
                yield [
                    'url' => strval($item->link),
                    'title' => strval($item->title),
                    'tags' => $tags
                ];
            }
        }
    }
}

function tag_compare($a, $b)
{
    // 先頭に置く
    $ret = pattern_up_compare(["🔖"], $a, $b);
    if (! is_null($ret)) {
        return $ret;
    }
    // 後ろに下げる
    $ret = pattern_down_compare(
        explode(",",
            "✅,🍀,🚪,🌐,💿,💬,🛒,🎨,✂,➕,📋,📓,📚,☕,💪,🍙,🍚,💊,💰,🍬,🎧,🔧,📰,🤣,🎮"),
        $a, $b);
    if (! is_null($ret)) {
        return $ret;
    }
    $personEmoji = ["🌈", "⚓", "🎥", "🎤", "👥", "🀄"];
    $a = strtr($a, array_combine($personEmoji, array_pad([], count($personEmoji), "")));
    $b = strtr($b, array_combine($personEmoji, array_pad([], count($personEmoji), "")));
    return ($a > $b) ? 0 : 1;
}

function pattern_up_compare($chars, $a, $b)
{
    foreach ($chars as $char) {
        if (strpos($a, $char) === 0 && strpos($b, $char) === false) {
            return 0;
        }
        if (strpos($a, $char) === false && strpos($b, $char) === 0) {
            return 1;
        }
        if (strpos($a, $char) === 0 && strpos($b, $char) === 0) {
            return ($a > $b) ? 0 : 1;
        }
    }
    return null;
}
function pattern_down_compare($chars, $a, $b)
{
    foreach ($chars as $char) {
        if (strpos($a, $char) === 0 && strpos($b, $char) === false) {
            return 1;
        }
        if (strpos($a, $char) === false && strpos($b, $char) === 0) {
            return 0;
        }
        if (strpos($a, $char) === 0 && strpos($b, $char) === 0) {
            return ($a > $b) ? 1 : 0;
        }
    }
    return null;
}


function build_hatena_bookmark_comment($item)
{
    $item['comment'] = isset($item['comment']) ? $item['comment'] : "";
    if (empty($item['tags'])) {
        return $item['comment'];
    }
    $tags = [];
    foreach ($item['tags'] as $i => $tag) {
        $tags[] = optimise_tag_text($tag);
    }
    $tags = array_unique($tags);
    usort($tags, 'tag_compare');
    $tags = hatena_bookmark_try_to_append_tag($tags, "✅");
    $tags = array_slice($tags, 0, 10);
    if (!preg_match("/ ⌚\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}$/m", $item['comment'])) {
        $item['comment'] = $item['comment'] . " ⌚" . date("Y/m/d H:i", $item["created_epoch"]);
    }
    return ["[" . implode("][", $tags) . "]" . $item['comment'], $tags];
}

function count_helpful_tag(array $tags)
{
    $count = 0;
    $list = explode(",", "✅,🍀,🚪,🌐,💬,🎨,✂");
    foreach ($tags as $tag) {
        if (! in_array(mb_substr($tag, 0, 1), $list)) {
            $count++;
        }
    }
    return $count;
}

function optimise_tag_text($text = "")
{
    while (strlen($text) > 32) {
        $text = mb_substr($text, 0, mb_strlen($text) - 2) . "…";
    }
    return $text;
}

function hatena_bookmark_try_to_append_tag($tags, $addTag)
{
    foreach ($tags as $key => $tag) {
        if ($tag == $addTag) {
            unset($tags[$key]);
        }
    }
    if (count($tags) < 10 && !in_array($addTag, $tags)) {
        array_push($tags, $addTag);
    }
    return $tags;
}

function get_bookmark_api_client()
{
    $stack = HandlerStack::create();
    $stack->push(new Oauth1([
        'consumer_key' => HATENA_CONSUMER_KEY,
        'consumer_secret' => HATENA_CONSUMER_SECRET,
        'token' => HATENA_TOKEN,
        'token_secret' => HATENA_TOKEN_SECRET
    ]));
    return new Client([
        'base_uri' => 'https://bookmark.hatenaapis.com/rest/1/',
        'handler' => $stack,
        'auth' => 'oauth',
        'http_errors' => false,
    ]);
}

function hr($str) {
    echo str_pad('*** ' . $str . " ", 60, "*") . PHP_EOL;
}