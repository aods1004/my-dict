<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

/**
 *   urlencode
 * ----------------------------------------------------------------------------------------------
 */
$data = [];
foreach (load_tsv(ROOT_DIR . "/data/nijisanji_members/name.tsv") as $row) {
    list($ruby, $name) = $row;
    $data[] = $ruby . "\t" . trim($name) . "\t" . rawurlencode(trim($name));
}
file_put_contents(ROOT_DIR . "/output/urlencoded_name.tsv", implode(PHP_EOL, $data));