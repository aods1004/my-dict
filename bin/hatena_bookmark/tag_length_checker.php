<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$list = file(__DIR__ . "/data/タグ候補.tsv");

foreach ($list as $item) {
    echo optimise_tag_text(trim($item)) . PHP_EOL;
}
