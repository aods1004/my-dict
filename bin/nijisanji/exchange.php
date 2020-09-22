<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$ruby_tag_dict = get_ruby_tag_dict();
$name_ruby_dict = get_name_ruby_dict();
$tagExchanger = get_tag_exchanger();

/**
 *   データ生成
 * ----------------------------------------------------------------------------------------------
 */
$output = [];
$input_dict = [];
foreach (load_tsv(__DIR__ . "/data/input.tsv") as $row) {
    list($url, $title,) = $row;
    preg_match("/^([^\(]*)(\(.*)? [|(]/", $title, $match);
    if (empty($name_ruby_dict[$match[1]])) {
        echo "not found: " . $match[1];
        exit;
    }
    $name = $match[1];
    $ruby = $name_ruby_dict[$name];
    $input_dict[$ruby] = "$ruby\t" . $url;
//    $tag = $tags_dict[$ruby];
//    if (trim($url)) {
//        $input_dict[$ruby] = "$url\t$tag,🗣フリーチャット";
//    }
//    preg_match("/^([^\(]*)(\(.*)? [|(]/", $title, $match);
//    if (empty($name_ruby_dict[$match[1]])) {
//        echo "not found: " . $match[1];
//        exit;
//    }
//    $name = $match[1];
//    $ruby = $name_ruby_dict[$name];
//    $tag = $tags_dict[$ruby];
//    $input_dict[$ruby] = "! " . str_replace("https://", "https://b.hatena.ne.jp/entry/s/", $url) . "\t" . $name . PHP_EOL;
//    $input_dict[$ruby] .= "$url\t" . $tag . ",🌐UserLocal";
//    $input_dict[$ruby] = "$ruby\t" . str_replace("https://", "https://b.hatena.ne.jp/entry/s/", $url);
}
if ($output) {
    file_put_contents(__DIR__ . "/data/output.tsv", implode(PHP_EOL, $output));
    exit;
}

$output = [];
foreach (load_tsv(__DIR__ . "/data_raw/name.tsv") as $row) {
    list($ruby, $name) = $row;
    if (! empty($input_dict[$ruby])) {
        $output[] = $input_dict[$ruby];
    }
    // $url = "https://dic.nicovideo.jp/a/$enc_name";
    // $url = "https://v-data.info/vsearch?q=$enc_name&kind=v";
    // $status = check_url_status($url);
    // echo "$url $name $status" . PHP_EOL;
    // $data[] = "! " . str_replace("https://", "https://b.hatena.ne.jp/entry/s/", $url) . "\t" . $name;
    // $data[] = "$url\t" . $tag . ",🌐v-data,🍀人名,🍀単語記事";
    // $data[] = "! $ruby\t$url\t" . ($status == '200' ? 1 : 0);
    // $data[] = "$url\t" . $tag . ",🌐ニコニコ大百科,🌐niconico,🍀人名,🍀単語記事";
    // $data[] = "$ruby\t" . $url;
}

file_put_contents(ROOT_DIR . "/output/output.tsv", implode(PHP_EOL, $output));
