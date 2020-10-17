<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Aods1004\MyDict\TagExchanger;

function get_bookmark_feed_client($account): Client
{
    return new Client(['base_uri' => 'https://b.hatena.ne.jp/' . $account . '/']);
}

function get_high_priorty_mark(): array
{
    return ["🔖", "🌈👥"];
}

function get_row_priorty_mark(): array
{
    return [
        "🍀", "🚪", "🌐", "🗣", "💿", "💬", "🛒", "🎨", "✂", "➕", "📋", "📓", "📚",
        "☕", "💪", "🍴", "🍚", "💊", "💰", "🍬", "🎧", "🔧", "📰", "🤣", "🎮", "🌈にじさんじ"];
}

function get_veryrow_priorty_mark(): array
{
    return ["🍀", "🚪", "🌐", "💬", "🎨", "✂"];
}

function get_all_bookmarks(): Generator
{
    $client = get_bookmark_feed_client(HATENA_MY_ACCOUNT);
    $page = 1;
    while (true) {
        try {
            $ret = $client->get("rss", ['query' => ["page" => $page]]);
            $page++;
        } catch (Throwable $exception) {
            var_dump($exception);
            break;
        }
        $data = simplexml_load_string($ret->getBody()->getContents());
        $items = $data->item ?? null;
        if ($items === null) {
            break;
        }
        foreach ($items as $item) {
            $ns = $item->getNamespaces(true);
            $bookmark_url = (string) $item->attributes($ns["rdf"])->about;
            $dc = $item->children($ns["dc"]);
            $tags = [];
            if ($dc->subject) {
                foreach ($dc->subject as $tag) {
                    $tags[] = (string)$tag;
                }
            }
            [$comment_raw, $tags] = join_comment($tags, (string)$item->description, strtotime((string)$item->date));
            yield [
                'url' => (string)$item->link,
                'title' => (string)$item->title,
                'tags' => $tags,
                'comment_raw' => $comment_raw,
                'comment' => (string)$item->description,
                'created_epoch' => strtotime((string)$item->date),
                'bookmark_url' => $bookmark_url,
            ];
        }
    }
}

function tag_compare($a, $b): int
{
    // 先頭に置く
    $ret = pattern_up_compare(get_high_priorty_mark(), $a, $b);
    if (!is_null($ret)) {
        return $ret;
    }

    // 先頭に置く
    $ret = pattern_fav_tags($a, $b);
    if (!is_null($ret)) {
        return $ret;
    }

    // 後ろに下げる
    $ret = pattern_down_compare(get_row_priorty_mark(), $a, $b);
    if (!is_null($ret)) {
        return $ret;
    }

    // タグ利用数による比較
    $ret = pattern_used_count($a, $b);
    if (!is_null($ret)) {
        return $ret;
    }
    return ($a < $b) ? 0 : 1;
}

function pattern_used_count($a, $b, bool $resetFlag = false): ?int
{
    static $data;
    if (empty($data) || $resetFlag) {
        foreach (load_csv(ROOT_DIR . "/dict/hatebu_tags_use_count.tsv") as $row) {
            if (isset($row[0])) {
                $data[$row[0]] = $row[1] ?? 0;
            }
        }
    }
    $a_count = (int) ($data[$a] ?? 0);
    $b_count = (int) ($data[$b] ?? 0);
    if ($a_count === $b_count) {
        return ($a < $b) ? 0 : 1;
    }
    return null;
}

function pattern_fav_tags($a, $b): ?int
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

function pattern_up_compare($chars, $a, $b): ?int
{
    foreach ($chars as $char) {
        if (strpos($a, $char) === 0 && strpos($b, $char) === false) {
            return 0;
        }
        if (strpos($a, $char) === false && strpos($b, $char) === 0) {
            return 1;
        }
    }
    return null;
}

function pattern_down_compare($chars, $a, $b): ?int
{
    foreach ($chars as $char) {
        if (strpos($a, $char) === 0 && strpos($b, $char) === false) {
            return 1;
        }
        if (strpos($a, $char) === false && strpos($b, $char) === 0) {
            return 0;
        }
    }
    return null;
}

/**
 * @param array $item
 * @param bool $slice_flag
 * @return array
 */
function build_hatena_bookmark_comment(array $item, bool $slice_flag = true): array
{
    $comment = !empty($item['comment']) ? trim($item['comment']) : "";
    $created_epoch = !empty($item['created_epoch']) ? $item['created_epoch'] : time();
    $tags = !empty($item['tags']) ? $item['tags'] : [];
    foreach ($tags as $i => $tag) {
        $tags[] = optimise_tag_text($tag);
    }
    $tags = array_unique($tags);
    usort($tags, 'tag_compare');
    if ($slice_flag) {
        $tags = array_slice($tags, 0, 10);
    }
    return join_comment($tags, $comment, $created_epoch);
}

/**
 * @param $tags
 * @param $comment
 * @param $created_epoch
 * @return array
 */
function join_comment($tags, $comment, $created_epoch): array
{
    $comment = trim($comment);
    if (preg_match("/⌚\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}$/mu", $comment, $matches)) {
        $comment_main = trim(str_replace($matches[0], "", $comment));
        $comment = implode(" ", array_filter([$comment_main, $matches[0]]));
    }
    if (!empty($created_epoch) && !preg_match("/⌚\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}$/mu", $comment)) {
        $comment = implode(" ", array_filter([trim($comment), "⌚" . date("Y/m/d H:i", $created_epoch)]));
    }
    $tag_text = !empty($tags) ? "[" . implode("][", $tags) . "]" : "";
    return [$tag_text . $comment, $tags];
}

/**
 * @param array $tags
 * @return int
 */
function count_helpful_tag(array $tags): int
{
    $count = 0;
    $list = get_veryrow_priorty_mark();
    foreach ($tags as $tag) {
        if (!in_array(mb_substr($tag, 0, 1), $list, true)) {
            $count++;
        }
    }
    return $count;
}

/**
 * @param string $text
 * @return string
 */
function optimise_tag_text($text = ""): string
{
    $text = trim($text);
    $text = strtr($text, ["[" => "", "]" => "", "　" => " "]);
    while (strlen($text) > 33) {
        $text = mb_substr($text, 0, -2) . "…";
    }
    return $text;
}

/**
 * @return Client
 */
function get_bookmark_api_client(): Client
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
        // 'http_errors' => false,
    ]);
}

/**
 * @return TagExchanger
 */
function get_tag_exchanger(): TagExchanger
{
    $exchange = [];
    foreach (load_csv(ROOT_DIR . "/data/tags_exchange.tsv") as $row) {
        [$from, $to] = $row + ["", ""];
        if (empty($from) || empty($to)) {
            continue;
        }
        $from = optimise_tag_text($from);
        $to = optimise_tag_text($to);
        $exchange[$from] = $to;
    }
    $exclude = [];
    foreach (load_csv(ROOT_DIR . "/data/tags_unnecessary.tsv") as $row) {
        $exclude[] = $row[0];
    }
    $redundant = [];
    foreach (load_csv(ROOT_DIR . "/data/tags_redundant.tsv") as $row) {
        [$necessary, $unnecessary] = $row + ["", ""];
        if (empty($necessary) || empty($unnecessary)) {
            continue;
        }
        $redundant[] = compact('necessary', 'unnecessary');
    }

    $extractKeywords = [];
    $extractKeywordReader = static function (&$extractKeywords, $row) {
        [$from, $to, $excludeWords] = $row + ["", "", ""];
        if (empty($from) || empty($to)) {
            return;
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
    };
    foreach (load_csv(ROOT_DIR . "/data/tags_extract_keywords_fixed.tsv") as $row) {
        $extractKeywordReader($extractKeywords, $row);
    }
    foreach (load_csv(ROOT_DIR . "/data/tags_extract_keywords.tsv") as $row) {
        $extractKeywordReader($extractKeywords, $row);
    }

    $replace = [
        "📽" => "🎥",
    ];
    return new TagExchanger($extractKeywords, $exchange, $replace, $exclude, $redundant);
}

/**
 * @param $url
 * @param $title
 * @param $tags
 * @return array
 */
function create_tags($url, $title, $tags): array
{
    static $tagExchanger, $ltvCount = 10;
    if (empty($tagExchanger) || $ltvCount < 1) {
        $tagExchanger = get_tag_exchanger();
        $ltvCount = 10;
    }
    $ltvCount--;
    $tags = $tagExchanger->extractKeywords($tags, $title, $url);
    $tags = $tagExchanger->exchange($tags);
    $tags = $tagExchanger->optimise($tags);
    $tags = $tagExchanger->removeRedundant($tags);
    return $tags;
}

/**
 * @param $url
 * @return string
 */
function get_hatebu_entry_url($url): string
{
    return strtr($url, [
        "https://" => "https://b.hatena.ne.jp/entry/s/",
        "http://" => "https://b.hatena.ne.jp/entry/",
    ]);
}