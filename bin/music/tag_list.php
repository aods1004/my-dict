<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$output = [];
foreach (load_csv(MUSIC_DIR . "\\TrackList.csv", ";") as $row) {
    $row[5] = str_replace("音楽 - 邦楽", "JPop", $row[5]);
    $output[] = implode(";", $row);
}
file_put_contents(MUSIC_DIR . "\\TrackList.csv", implode(PHP_EOL, $output));