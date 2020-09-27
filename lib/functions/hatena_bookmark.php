<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Aods1004\MyDict\TagExchanger;

function get_bookmark_feed_client($account)
{
    return new Client(['base_uri' => 'https://b.hatena.ne.jp/' . $account . '/']);
}

function get_all_bookmarks($useOfflineRss = false)
{
    if ($useOfflineRss) {
        // download from https://b.hatena.ne.jp/-/my/config/data_management
        $data = simplexml_load_file(ROOT_DIR . "/data/" . HATENA_MY_ACCOUNT . ".bookmarks.rss");
        foreach (isset($data->item) ? $data->item : [] as $item) {
            $tags = [];
            $ns = $item->getNamespaces(true);
            $dc = $item->children($ns["dc"]);
            $subjects = isset($dc->subject) ? $dc->subject : [];
            foreach ($subjects as $tag) {
                $tags[] = (string)$tag;
            }
            list($comment, $tags) = build_hatena_bookmark_comment([
                'tags' => $tags,
                'comment' => strval($item->description),
                'created_epoch' => strtotime(strval($item->date)),
            ]);
            yield [
                'url' => strval($item->link),
                'title' => strval($item->title),
                'tags' => $tags,
                'comment_raw' => $comment,
                'comment' => strval($item->description),
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
                list($comment, $tags) = build_hatena_bookmark_comment([
                    'tags' => $tags,
                    'comment' => strval($item->description),
                    'created_epoch' => strtotime(strval($item->date)),
                ]);
                yield [
                    'url' => strval($item->link),
                    'title' => strval($item->title),
                    'tags' => $tags,
                    'comment_raw' => $comment,
                    'comment' => strval($item->description),
                ];
            }
        }
    }
}

function tag_compare($a, $b)
{
    // 先頭に置く
    $ret = pattern_up_compare(["🔖","🌈👥"], $a, $b);
    if (!is_null($ret)) {
        return $ret;
    }

    // 先頭に置く
    $ret = pattern_fav_tags($a, $b);
    if (!is_null($ret)) {
        return $ret;
    }

    // 後ろに下げる
    $ret = pattern_down_compare(
        explode(",", "".
            "🍀,🚪,🌐,🗣,💿,💬,🛒,🎨,✂,➕,📋,📓,📚," .
            "☕,💪,🍴,🍚,💊,💰,🍬,🎧,🔧,📰,🤣,🎮"),
        $a, $b);
    if (!is_null($ret)) {
        return $ret;
    }
    $personEmoji = ["🌈", "⚓", "🎥", "🎤", "👥", "🀄"];
    $a = strtr($a, array_combine($personEmoji, array_pad([], count($personEmoji), "")));
    $b = strtr($b, array_combine($personEmoji, array_pad([], count($personEmoji), "")));
    return ($a > $b) ? 0 : 1;
}

function pattern_fav_tags($a, $b)
{
    static $favTags;
    if (empty($favTags)) {
        foreach (file(ROOT_DIR . "/data/hatebu_fav_tags.tsv") as $i => $tag) {
            $favTags[trim($tag)] = $i;
        }
    }
    $ia = isset($favTags[$a]);
    $ib = isset($favTags[$b]);
    if ($ia && !$ib) {
        return 0;
    }
    if (!$ia && $ib) {
        return 1;
    }
    if ($ia && $ib) {
        return ($favTags[$a] < $favTags[$b]) ? 0 : 1;
    }
    return null;
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
    $item['comment'] = !empty($item['comment']) ? $item['comment'] : "";
    $item["created_epoch"] = !empty($item['created_epoch']) ? $item['created_epoch'] : time();
    $item['tags'] = !empty($item['tags']) ? $item['tags'] : [];
    $tags = [];
    foreach ($item['tags'] as $i => $tag) {
        $tags[] = optimise_tag_text($tag);
    }
    $tags = array_unique($tags);
    usort($tags, 'tag_compare');
    // $tags = hatena_bookmark_try_to_append_tag($tags, "✅");
    $tags = array_slice($tags, 0, 10);
    if (! preg_match("/ ⌚\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}$/m", $item['comment'])) {
        $item['comment'] = $item['comment'] . " ⌚" . date("Y/m/d H:i", $item["created_epoch"]);
    }
    $tag_text = !empty($tags) ? "[" . implode("][", $tags) . "]" : "";

    return [$tag_text . $item['comment'], $tags];
}

function count_helpful_tag(array $tags)
{
    $count = 0;
    $list = explode(",", "🍀,🚪,💬,🌐,🎨,✂");
    foreach ($tags as $tag) {
        if (!in_array(mb_substr($tag, 0, 1), $list)) {
            $count++;
        }
    }
    return $count;
}

function optimise_tag_text($text = "")
{
    $text = trim($text);
    $text = strtr($text, ["[" => "", "]" => "", "　" => " "]);
    while (strlen($text) > 33) {
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

function get_tag_exchanger()
{
    $exchange = [];
    foreach (load_tsv(ROOT_DIR . "/data/tags_exchange.tsv") as $row) {
        list($from, $to) = $row + ["", ""];
        if (empty($from) || empty($to)) {
            continue;
        }
        $from = optimise_tag_text($from);
        $to = optimise_tag_text($to);
        $exchange[$from] = $to;
    }
    $exclude = [];
    foreach (load_tsv(ROOT_DIR . "/data/tags_unnecessary.tsv") as $row) {
        $exclude[] = $row[0];
    }
    $redundant = [];
    foreach (load_tsv(ROOT_DIR . "/data/tags_redundant.tsv") as $row) {
        list($necessary, $unnecessary) = $row + ["", ""];
        if (empty($necessary) || empty($unnecessary)) continue;
        $redundant[] = compact('necessary', 'unnecessary');
    }

    $extractKeywords = [];
    foreach (load_tsv(ROOT_DIR . "/data/tags_extract_keywords.tsv") as $row) {
        list($from, $to, $excludeWords) = $row + ["", "", ""];
        if (empty($from) || empty($to)) {
            continue;
        }
        $fromList = [$from];
        $fromList[] = str_replace(" ", "", $from);
        foreach (array_unique($fromList) as $from) {
            if (!isset($extractKeywords[$from])) {
                $extractKeywords[$from] = [];
            }
            $extractKeywords[$from][] = [
                'to' => optimise_tag_text($to),
                'exclude' => array_filter(explode(',', $excludeWords))
            ];
        }
    }
    $replace = [
        "📽" => "🎥",
    ];
    return new TagExchanger($extractKeywords, $exchange, $replace, $exclude, $redundant);
}

/**
 * @param $url
 * @return string
 */
function get_hatebu_entry_url($url)
{
    return strtr($url, [
        "https://" => "https://b.hatena.ne.jp/entry/s/",
        "http://" => "https://b.hatena.ne.jp/entry/",
    ]);
}