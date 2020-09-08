<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$list = [
    "📽慎重勇者～この勇者が俺TUEEEくせに慎重すぎる～",

];

foreach ($list as $item) {
    echo optimise_tag_text($item) . PHP_EOL;
}