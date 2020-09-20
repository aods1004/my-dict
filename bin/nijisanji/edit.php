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
    list($url, $title, ) = $row;
    preg_match("|\((.*)\)|", $title, $match);
    if (empty($match[1])) {
        var_dump($row);
        exit;
    }
    $ruby = str_replace(" ", "", $match[1]);
    $tag = $tags_dict[$ruby];
    $data[] = "! " . str_replace("https://", "https://b.hatena.ne.jp/entry/s/", $url) . "\t" . $title;
    $data[] = "$url\t$tag,🌐VNUMA";

}
file_put_contents(__DIR__ . "/data/output.tsv", implode(PHP_EOL, $data));
/**
 *   データ生成2
 * ----------------------------------------------------------------------------------------------
 */
//$data = [];
//foreach (load_tsv(__DIR__ . "/data_raw/name.tsv") as $row) {
//    list($ruby, $item) = $row;
//    $tag = $tags_dict[$ruby];
//    $data[] = "https://dic.pixiv.net/a/" . rawurlencode(trim($item)) . "\t" . $tag . ",🌐ピクシブ百科辞典,🌐ピクシブ,🍀人名,🍀単語記事";
//}
//file_put_contents(__DIR__ . "/data/output2.tsv", implode(PHP_EOL, $data));