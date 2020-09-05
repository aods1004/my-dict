<?php

// https://www.pressmantech.com/tech/programming/1137
// http://developer.hatena.ne.jp/ja/documents/auth/apis/oauth/consumer

//titterでアプリ登録するともらえる情報
$oauthConfig = array(
    'callbackUrl' => 'oob',
    'authorizeUrl' => 'https://www.hatena.ne.jp/oauth/authorize',
    'requestTokenUrl' => 'https://www.hatena.com/oauth/initiate',
    'accessTokenUrl' => 'https://www.hatena.com/oauth/token',
    'consumerKey' => '74SeJLFmHsZtrg==',
    'consumerSecret' => 'gazrSgEL1uAjwSLjKCmXObTwPh8='
);
//今回はGETでアクセスしてリクエストトークンを取得する。
$method = 'GET';
//ランダムな文字列。
$nonce =  md5(uniqid(rand(), true));
//Unixタイムスタンプ
$timestamp = time();
//リクエストトークンを取得するのに必要な情報をまとめる。
$authorization = array(
    'oauth_nonce' => $nonce,
    'oauth_signature_method' => 'HMAC-SHA1', //TwitterではHMAC-SHA1に固定
    'oauth_timestamp' => $timestamp,
    'oauth_consumer_key' => $oauthConfig['consumerKey'],
    'oauth_version' => '1.0'
);
/**
 * コールバックURL
 * コールバックURLは「アプリを認証」ボタンを押したときにリダイレクトされるURL
 * リダイレクトされるときにリクエストトークンと検証用のキーがパラメーター
 * としてついてきます。
 * コールバックURLが無いときはoobという文字列が入ってout of band modeで
 * 動作します。これはリダイレクトされずにPINコードが表示されてそれを使って
 * 次のステップへ進むモードです。
 */
if($oauthConfig['callbackUrl']){
    $authorization['oauth_callback'] = $oauthConfig['callbackUrl'];
}else{
    $authorization['oauth_callback'] = 'oob';
}
/**
 * キーのアルファベット順でソートします。
 * $authorization配列の中身を使ってリクエストトークンを発行してもらうための
 * 署名(oauth_signature）を作ります。
 * その際にアルファベット順で並べて文字列にしたものを署名用のメッセージとして
 * 使う決まりがあるためソートします。
 */
ksort($authorization);
/**
 * signature base string の生成（oauth_signatureを作るときのメッセージ部分）
 * rawurlencodeはPHP5.3とそれより前では仕様が違う(5.2以前では~を%7Eに変換）
 * ので注意。ここでは5.3でのコードです。
 * やっていることは簡単で
 * 配列の中身のキーと値をURLエンコードしたものをイコールで&で結合する。
 * それをさらにURLエンコードする。
 * HTTPメソッドとリクエストトークンURLをURLエンコードして&で結合
 * 下記のURLが非常に分かり易い。
 * http://developer.yahoo.co.jp/other/oauth/signinrequest.html
 *
 * こんな感じになります。(適当に改行をいれてあります。）
 * GET&https%3A%2F%2Fapi.twitter.com%2Foauth%2Frequest_token&
 * oauth_callback%3Dhttp%253A%252F%252Fnet.pressmantech.com%252F
 * %26oauth_consumer_key%3DpD4dm6IQHa6jhtge82Fg
 * %26oauth_nonce%3D174bd408ddbc0e3bf3603898f0f3d97b
 * %26oauth_signature_method%3DHMAC-SHA1
 * %26oauth_timestamp%3D1307515593
 * %26oauth_version%3D1.0
 */
$signatureBaseString = '';
foreach($authorization as $key => $val){
    $signatureBaseString .= $key . '=' . rawurlencode($val) . '&';
}
$signatureBaseString = substr($signatureBaseString,0,-1);
$signatureBaseString = $method . '&' .
    rawurlencode($oauthConfig['requestTokenUrl']) . '&' .
    rawurlencode($signatureBaseString);
/**
 * signing keyの生成（oauth_signatureを作るときのキー部分）
 * oauth_token_secretがあれば"&"の後ろにつけるのだが今はまだ無い。
 * よって&で終わる変なカタチ
 */
$signingKey = rawurlencode($oauthConfig['consumerSecret']) . '&';
/**
 * oauth_signatureの生成base64_encodeとhash_hmacの3番目の引数を
 * trueにするのを忘れないように。
 */
$authorization['oauth_signature'] =
    base64_encode(hash_hmac('sha1',$signatureBaseString,$signingKey,true));
//リクエストトークンをリクエストするURLを生成
$requestUrl = $oauthConfig['requestTokenUrl'] . '?';
foreach($authorization as $key => $val){
    $requestUrl .= $key . '=' . rawurlencode($val) .'&';
}
$requestUrl = substr($requestUrl,0,-1);
var_dump(file_get_contents($requestUrl));