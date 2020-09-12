<?php

use Aods1004\MyDict\TagExchanger;

function get_tag_exchanger() {
    $exchange = [];
    foreach (load_tsv(__DIR__ . "/../data/変換表.tsv") as $row) {
        list($from, $to) = $row;
        $from = optimise_tag_text($from);
        $to = optimise_tag_text($to);
        $exchange[$from] = $to;
    }
    $exclude = [];
    foreach (load_tsv(__DIR__ . "/../data/不要タグ.tsv") as $row) {
        $exclude[] = $row[0];
    }
    $extractKeywords = [];
    foreach (load_tsv(__DIR__ . "/../data/抽出キーワード.tsv") as $row) {
        list($from, $to) = $row;
        if (!isset($row[1])) {
            exit;
        }
        $fromList = [$from];
        $fromList[] = str_replace(" ", "", $from);
        $fromList[] = strtolower($from);
        $fromList[] = strtoupper($from);
        foreach (array_unique($fromList) as $from) {
            if (!isset($extractKeywords[$from])) {
                $extractKeywords[$from] = [];
            }
            $extractKeywords[$from][] = $to;
        }
    }
    $replace = [
        "📽" => "🎥",
    ];
    return new TagExchanger($extractKeywords, $exchange, $replace, $exclude);
}
