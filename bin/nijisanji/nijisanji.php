<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$name_dict = [];
$ruby_dict = [];
foreach (load_tsv(__DIR__ . "/data_raw/name.tsv") as $row) {
    list($ruby, $name) = $row;
    $data = new StdClass();
    $data->name = $name;
    $data->name = $name;
    $data->name_ruby = $ruby;
    $data->name_jpn = $name;
    $data->name_alphabet = null;
    $data->funmark = null;
    $data->funmarks = [];
    $data->twitter_url = null;
    $data->twitter_links = [];
    $data->youtube_links = [];
    $data->niconico_links = [];
    $name_dict[$name] = $data;
    $ruby_dict[$ruby] = $data;
}

foreach (load_tsv(__DIR__ . "/data_raw/funmark.tsv") as $row) {
    list($ruby, $mark) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: funmark.tsv [$ruby]");
    }
    if(empty($mark)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->funmark = $mark;
    $data->funmarks = explode(',', $mark);
}
foreach (load_tsv(__DIR__ . "/data_raw/twitter.tsv") as $row) {
    list($ruby, $twitter_url, $primary) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: twitter.tsv [$ruby]");
        continue;
    }
    if(empty($twitter_url)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->twitter_links[] = [
        'url' => $twitter_url,
        'primary' => ($primary == 1) ? true : false,
    ];
    if ($primary == 1) {
        $data->twitter_url = $twitter_url;
    }
}

$data = [];
foreach ($ruby_dict as $item) {
    $data[] = get_object_vars($item);
}

file_put_contents(__DIR__ . "/data/nijisanji_liver.json", json_encode($data));