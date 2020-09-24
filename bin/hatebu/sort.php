<?php
require_once dirname(__DIR__) . "/../vendor/autoload.php";

$data = ['🌈戌亥とこ', '🌈月ノ美兎', '🌈リゼ・ヘルエスタ', '🌈ルイス・キャミー'];

usort($data, 'tag_compare');

var_dump($data);