<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$name_dict = [];
$ruby_dict = [];
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/name.tsv") as $row) {
    list($ruby, $name) = $row;
    $data = new StdClass();
    $data->name = $name;
    $data->name_global = $name;
    $data->name_ruby = $ruby;
    $data->name_jpn = $name;
    $data->name_eng = null;
    $data->fanmark = null;
    $data->fanmarks = [];
    $data->youtube_url = null;
    $data->youtube_links = [];
    $data->twitter_url = null;
    $data->twitter_links = [];
    $data->twitcasting_url = null;
    $data->twitcasting_links = [];
    $data->openrec_url = null;
    $data->openrec_links = [];
    $data->niconico_url = null;
    $data->niconico_links = [];
    $data->bilibili_url = null;
    $data->bilibili_links = [];
    $data->facebook_url = null;
    $data->facebook_links = [];
    $data->instagram_url = null;
    $data->instagram_links = [];
    $data->tiktok_url = [];
    $data->tiktok_links = [];
    $name_dict[$name] = $data;
    $ruby_dict[$ruby] = $data;
}
/**
 *   Fanmark
 * ----------------------------------------------------------------------------------------------
 */
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/name_fanmark.tsv") as $row) {
    list($ruby, $mark) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: name_fanmark.tsv [$ruby]");
    }
    if(empty($mark)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->fanmark = $mark;
    $data->fanmarks = explode(',', $mark);
}

/**
 *   YouTube
 * ----------------------------------------------------------------------------------------------
 */
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/links_mov_youtube.tsv") as $row) {
    list($ruby, $url, $title, $primary) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: links_mov_youtube.tsv [$ruby]");
    }
    if(empty($url)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->youtube_links[] = [
        'url' => $url,
        'title' => $title,
        'primary' => ($primary == 1) ? true : false,
    ];
    if ($primary == 1) {
        $data->youtube_url = $url;
    }
}

/**
 *   Twitter
 * ----------------------------------------------------------------------------------------------
 */
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/links_sentence_twitter.tsv") as $row) {
    list($ruby, $url, $primary) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: links_sentence_twitter.tsv [$ruby]");
    }
    if(empty($url)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->twitter_links[] = [
        'url' => $url,
        'primary' => ($primary == 1) ? true : false,
    ];
    if ($primary == 1) {
        $data->twitter_url = $url;
    }
}

/**
 *   Twitchasting
 * ----------------------------------------------------------------------------------------------
 */
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/links_live_twitcasting.tsv") as $row) {
    list($ruby, $url, $primary) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: links_live_twitcasting.tsv [$ruby]");
    }
    if(empty($url)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->twitcasting_url = $url;
    $data->twitcasting_links[] = [
        'url' => $url,
    ];
}
/**
 *   OPENREC
 * ----------------------------------------------------------------------------------------------
 */
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/links_live_openrec.tsv") as $row) {
    list($ruby, $url, $primary) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: links_live_openrec.tsv [$ruby]");
    }
    if(empty($url)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->openrec_url = $url;
    $data->openrec_links[] = [
        'url' => $url,
    ];
}
/**
 *   Niconico
 * ----------------------------------------------------------------------------------------------
 */
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/links_mov_niconico.tsv") as $row) {
    list($ruby, $url, $primary) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: links_mov_niconico.tsv [$ruby]");
    }
    if(empty($url)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->niconico_url = $url;
    $data->niconico_links[] = [
        'url' => $url,
    ];
}

/**
 *   English
 * ----------------------------------------------------------------------------------------------
 */
$dic = [];
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/name_eng.tsv") as $row) {
    list($ruby, $item) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: name_eng.tsv [$ruby]");
    }
    if(empty($item)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->name_eng = $item;
    foreach (preg_split('|[. ]|', $item) as $word) {
        $dic[] = strtolower($word);
    }
}

sort($dic);
array_unique($dic);
file_put_contents(ROOT_DIR . "/dict/nijisanji_english_words.dic", implode(PHP_EOL, $dic));

/**
 *   Japanese
 * ----------------------------------------------------------------------------------------------
 */
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/name_jpn.tsv") as $row) {
    list($ruby, $item) = $row;
    if (!isset($ruby_dict[$ruby])) {
        throw new Error("ベースがありません: name_jpn.tsv [$ruby]");
    }
    if(empty($item)) {
        continue;
    }
    $data = $ruby_dict[$ruby];
    $data->name_jpn = $item;
}

$template = [];
$data = [];
foreach ($ruby_dict as $item) {
    $data[] = get_object_vars($item);
    $template = [];
}
file_put_contents(ROOT_DIR . "/output/nijisanji_liver.json", json_encode($data));