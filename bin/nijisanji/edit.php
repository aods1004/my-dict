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
file_put_contents(__DIR__ . "/data/name_urlencode.tsv", implode(PHP_EOL, $data));

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
    $tag = $tags_dict[$ruby];
    if (trim($url)) {
        $url = str_replace(
            "https://www.youtube.com/channel/",
            "https://vtuber-post.com/database/detail.php?id=",
            $url
            );
        $data[] = $url . "\t" . $tag . ",🌐Vtuber post";
    }
}
file_put_contents(__DIR__ . "/data/output.tsv", implode(PHP_EOL, $data));
//$data = [];
//foreach (load_tsv(__DIR__ . "/data_raw/name.tsv") as $row) {
//    list($ruby, $item) = $row;
//    $tag = $tags_dict[$ruby];
//    $data[] = "https://dic.pixiv.net/a/" . rawurlencode(trim($item)) . "\t" . $tag . ",🌐ピクシブ百科辞典,🌐ピクシブ,🍀人名,🍀単語記事";
//}

