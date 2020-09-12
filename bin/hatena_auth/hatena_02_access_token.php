<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$url = 'https://www.hatena.com/oauth/token';
$method = 'POST';
$nonce = md5(uniqid(rand(), true));
$timestamp = time();
$authorization = [
    'oauth_nonce' => $nonce,
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_timestamp' => $timestamp,
    'oauth_consumer_key' => HATENA_CONSUMER_KEY,
    'oauth_token' => HATENA_OAUTH_TOKEN,
    'oauth_verifier' => HATENA_OAUTH_PIN,
    'oauth_version' => '1.0'
];
ksort($authorization);
$signatureBaseString = '';
foreach ($authorization as $key => $val) {
    $signatureBaseString .= $key . '=' . rawurlencode($val) . '&';
}
$signatureBaseString = substr($signatureBaseString, 0, -1);
$signatureBaseString = $method
    . '&' . rawurlencode($url)
    . '&' . rawurlencode($signatureBaseString);

$signingKey = rawurlencode(HATENA_CONSUMER_SECRET)
    . '&' . rawurlencode(HATENA_OAUTH_TOKEN_SECRET);

$authorization['oauth_signature'] = base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, true));

$oauthHeader = 'OAuth ';
foreach ($authorization as $key => $val) {
    $oauthHeader .= $key . '="' . rawurlencode($val) . '",';
}
$oauthHeader = substr($oauthHeader, 0, -1);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($curl, CURLOPT_TIMEOUT, 30);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization:' . $oauthHeader,
    'Content-Length:',
    'Expect:',
    'Content-Type:'
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POST, true);

$response = curl_exec($curl);
$accessToken = [];
foreach (explode('&', $response) as $v) {
    $param = explode('=', $v);
    $accessToken[$param[0]] = rawurldecode($param[1]);
}
echo "-------------------------------------" . PHP_EOL;
var_dump($accessToken);