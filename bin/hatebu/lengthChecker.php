<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";


$list = [
    "この素晴らしい世界に祝福",
    "転生したらスライムだった件",
    "グウェル・オス・ガール",
    "シェリン・バーガンディ",
    "ベルモンド・バンデラス",
];

foreach ($list as $item) {
    echo optimise_tag_text($item) . PHP_EOL;
}