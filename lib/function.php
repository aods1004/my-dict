<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

require_once dirname(__DIR__) . "/vendor/autoload.php";

function get_bookmark_api_client()
{
    $stack = HandlerStack::create();
    $middleware = new Oauth1([
        'consumer_key'    => HATENA_CONSUMER_KEY,
        'consumer_secret' => HATENA_CONSUMER_SECRET,
        'token'           => HATENA_TOKEN,
        'token_secret'    => HATENA_TOKEN_SECRET
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
    $authorization['oauth_signature'] =
        base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, true));
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

