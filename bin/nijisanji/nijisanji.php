<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$name_dict = [];
$yomigana_dict = [];
foreach (load_tsv(__DIR__ . "/data_raw/name.tsv") as $row) {
    list($yomigana, $name) = $row;
    $data = new StdClass();
    $data->name = $name;
    $data->name_notations = [
        ['notation' => $name, 'type' => 'japanese'],
        ['notation' => $name, 'type' => 'japanese'],
        ['notation' => null, 'type' => 'alphabet'],
        ['notation' => null, 'type' => 'romaji'],
        ['notation' => $name, 'type' => 'hiragana'],
        ['notation' => $name, 'type' => 'katakana'],
    ];
    $data->name_formal = $name;
    $data->name_formal_langage = "ja";
    $data->name_kanji = $name;
    $data->name_hiragana = $yomigana;
    $data->name_chinese = null;
    $data->name_alphabet = null;
    $data->last_name_alphabet = null;
    $data->first_name_alphabet = null;
    $data->fanmark = null;
    $data->fanmarks = [];
    $data->links = [
        'twitter' => ['url' => null, 'title' => null, 'primary' => null],
        'twitter_sub' =>  ['url' => null, 'title' => null, 'primary' => null],
        'youtube' =>  ['url' => null, 'title' => null, 'primary' => null],
        'youtube_sub' =>  ['url' => null, 'title' => null, 'primary' => null],
        'niconico' => ['url' => null, 'title' => null, 'primary' => null],
        'twitcasting' =>  ['url' => null, 'title' => null, 'primary' => null],
    ];
    $name_dict[$name] = $data;
    $yomigana_dict[$yomigana] = $data;
}

foreach (load_tsv(__DIR__ . "/data_raw/fanmark.tsv") as $row) {
    list($name, $mark) = $row;
    $name_dict[$name]->fanmarks[] = $mark;
}
foreach (load_tsv(__DIR__ . "/data_raw/twitter.tsv") as $row) {
    list($yomigana, $twitter_url, $no) = $row;
    $yomigana_dict[$yomigana]->twitter_links[] = [
        'href' => $twitter_url,
        'primary' => ($no == 1) ? true : false,
    ];
    if ($no == 1) {
        if ($yomigana_dict[$yomigana]->twitter_url) {
            throw new Error("duplicate primary twitter url: $yomigana");
        }
        $yomigana_dict[$yomigana]->twitter_url = $twitter_url;
    }
    if ($no == 2) {
        if ($yomigana_dict[$yomigana]->twitter_sub_url) {
            throw new Error('duplicate sub twitter url');
        }
        $yomigana_dict[$yomigana]->twitter_sub_url = $twitter_url;
    }
}

foreach ($yomigana_dict as $name => $data) {
    if (!empty($data->twitter_url)) {
        echo $data->twitter_url . PHP_EOL;
        echo $data->twitter_url . "/".PHP_EOL;
        echo strtolower($data->twitter_url) . PHP_EOL;
        echo strtolower($data->twitter_url) . "/".PHP_EOL;
    }
    if (!empty($data->twitter_sub_url)) {
        echo $data->twitter_sub_url . PHP_EOL;
        echo $data->twitter_sub_url . "/".PHP_EOL;
        echo strtolower($data->twitter_sub_url) . PHP_EOL;
        echo strtolower($data->twitter_sub_url) . "/".PHP_EOL;
    }
}