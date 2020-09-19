<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

/**
 *   urlencode
 * ----------------------------------------------------------------------------------------------
 */
$data = [];
foreach (load_tsv(__DIR__ . "/data_raw/name.tsv") as $row) {
    list($ruby, $item) = $row;
    $data[] = $ruby . "\t" .  trim($item) ."\t" . rawurlencode(trim($item));
}
file_put_contents(__DIR__ . "/data/name_urlencode.tsv", implode(PHP_EOL, $data));

$data = [];
foreach (load_tsv(__DIR__ . "/data_raw/name.tsv") as $row) {
    list($ruby, $item) = $row;
    $data[] = $ruby . "\thttps://kai-you.net/word/" . rawurlencode(trim($item));
}
file_put_contents(__DIR__ . "/data/link_kai-you.tsv", implode(PHP_EOL, $data));