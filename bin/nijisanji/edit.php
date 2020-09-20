<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

/**
 *   urlencode
 * ----------------------------------------------------------------------------------------------
 */
$data = [];
$ruby_dict = [];
foreach (load_tsv(__DIR__ . "/data_raw/name.tsv") as $row) {
    list($ruby, $name) = $row;
    $data[] = $ruby . "\t" . trim($name) . "\t" . rawurlencode(trim($name));
    $ruby_dict[$ruby] = $data;
}
file_put_contents(__DIR__ . "/data/urlencoded_name.tsv", implode(PHP_EOL, $data));
/**
 *   タグのロード
 * ----------------------------------------------------------------------------------------------
 */
$tags_dict = [];
foreach (load_tsv(__DIR__ . "/data_raw/name_hatebu_tags.tsv") as $row) {
    list($ruby, $tag) = $row;
    $tags_dict[$ruby] = $tag;
}
/**
 *   データ生成
 * ----------------------------------------------------------------------------------------------
 */
$data = [];
foreach (load_tsv(__DIR__ . "/data/input.tsv") as $row) {
    list($ruby, $name, $enc_name) = $row;
    $tag = $tags_dict[$ruby];
    $url = "https://dic.nicovideo.jp/a/$enc_name";

    $status = check_url_status($url);
    // echo "$url $name $status" . PHP_EOL;
    $data[] = "! $ruby\t$url\t" . ($status == '200' ? 1 : 0);
    // $data[] = "! " . str_replace("https://", "https://b.hatena.ne.jp/entry/s/", $url) . "\t" . $name;
    $data[] = "$url\t" . $tag . ",🌐ニコニコ大百科,🌐niconico,🍀人名,🍀単語記事";
}
file_put_contents(__DIR__ . "/data/output.tsv", implode(PHP_EOL, $data));
