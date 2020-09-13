<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$list = [
    "📽慎重勇者～この勇者が俺TUEEEくせに慎重すぎる～",
    "🎮Ring Fit Adventure",
    "🎮Detroit：Become Human",
    "🔖APEX部2434キルリレー",
    "バーチャルYouTuber",
    "インターネット文化",
    "Apache Web Server",
    "Open Source License",
    "🎥ヴァイオレット・エヴァーガーデン",
    "🎥ヴァイオレット･エヴァーガーデン",
    "🌈グウェル・オス・ガール",
    "🌈グウェル･オス･ガール",
    "THE IDOLM@STER SHINY COLORS１１",
];

foreach ($list as $item) {
    echo optimise_tag_text($item) . PHP_EOL;
}

var_dump();