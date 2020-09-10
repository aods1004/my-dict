<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

require_once dirname(__DIR__) . "/vendor/autoload.php";

function get_bookmark_client()
{
    return new Client([
        'base_uri' => 'https://b.hatena.ne.jp/' . HATENA_MY_ACCOUNT . '/',
        'http_errors' => false,
    ]);
}

function get_http_client() {
    return new Client(['http_errors' => false,]);
}

function get_all_bookmarks($useOfflineRss = false)
{
    $client = get_bookmark_client();
    $page = 0;
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
            foreach($item->children($ns["dc"])->subject ?: [] as $tag) {
                $tags[] = $tag;
            }
            yield ['url' => strval($item->link), 'title' => strval($item->title), 'tags' => $tags];
        }
    }
    if ($useOfflineRss) {
        // Download from https://b.hatena.ne.jp/-/my/config/data_management
        $data = simplexml_load_file(ROOT_DIR . "/_tmp/" . HATENA_MY_ACCOUNT . ".bookmarks.rss");
        foreach (isset($data->item) ? $data->item : [] as $item) {
            $tags = [];
            $ns = $item->getNamespaces(true);
            foreach($item->children($ns["dc"])->subject ?: [] as $tag) {
                $tags[] = (string) $tag;
            }
            yield ['url' => strval($item->link), 'title' => strval($item->title), 'tags' => $tags];
        }
    }
}

function build_hatena_bookmark_comment($item)
{
    $item['comment'] = isset($item['comment']) ? $item['comment'] : "";
    if (empty($item['tags'])) {
        return  $item['comment'];
    }
    $tags = [];
    foreach ($item['tags'] as $i => $tag) {
        $tags[] = optimise_tag_text($tag);
    }
    sort($tags, SORT_LOCALE_STRING);
    $tags = array_unique($tags);
    return "[" . implode("][", $tags) . "]" . $item['comment'];
}

function optimise_tag_text($text = "")
{
    while (strlen($text) > 32) {
        $text = mb_substr($text, 0, mb_strlen($text) - 2) . "…";
    }
    return $text;
}

function try_to_append_tag($tags, $tag) {
    if (count($tags) < 10 && ! in_array($tag, $tags)) {
        $tags[] = $tag;
    }
    return $tags;
}

function get_bookmark_api_client()
{
    $stack = HandlerStack::create();
    $middleware = new Oauth1([
        'consumer_key' => HATENA_CONSUMER_KEY,
        'consumer_secret' => HATENA_CONSUMER_SECRET,
        'token' => HATENA_TOKEN,
        'token_secret' => HATENA_TOKEN_SECRET
    ]);
    $stack->push($middleware);
    return new Client([
        'base_uri' => 'https://bookmark.hatenaapis.com/rest/1/',
        'handler' => $stack,
        'auth' => 'oauth',
        'http_errors' => false,
    ]);
}

function show_hatena_authorize()
{
    $url = 'https://www.hatena.com/oauth/initiate';
    $authorization = [
        'oauth_callback' => 'oob',
        'oauth_consumer_key' => HATENA_CONSUMER_KEY,
        'oauth_nonce' => md5(uniqid(rand(), true)),
        'oauth_signature' => null,
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => time(),
        'oauth_version' => '1.0',
        'scope' => "read_public,read_private,write_public,write_private,"
    ];
    $signatureBaseString = '';
    foreach ($authorization as $key => $val) {
        if ($val) {
            $signatureBaseString .= $key . '=' . rawurlencode($val) . '&';
        }
    }
    $signatureBaseString = substr($signatureBaseString, 0, -1);
    $signatureBaseString = implode('&', ['GET', rawurlencode($url), rawurlencode($signatureBaseString)]);
    $signingKey = rawurlencode(HATENA_CONSUMER_SECRET) . '&';
    $authorization['oauth_signature'] = base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, true));
    $requestUrl = $url . '?';
    foreach ($authorization as $key => $val) {
        $requestUrl .= $key . '=' . rawurlencode($val) . '&';
    }
    $requestUrl = substr($requestUrl, 0, -1);
    $ret = trim(file_get_contents($requestUrl));
    parse_str($ret, $data);
    echo "TOKEN : " . $data["oauth_token"] . PHP_EOL;
    echo "SEC   : " . $data["oauth_token_secret"] . PHP_EOL;
    echo "URL   : https://www.hatena.ne.jp/oauth/authorize?oauth_token=" . urlencode($data["oauth_token"]) . PHP_EOL;
}

function array_equal($a, $b) {
    if (array_diff($a, $b) || array_diff($b, $a)) {
        return false;
    }
    return true;
}