<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$fh = fopen(ROOT_DIR . "/dict/hatebu_tags.txt", "w");
$i = 0;
$notations = [];
foreach (load_tsv(ROOT_DIR . "/data/hatebu_noun_list.tsv") as $row) {
    list($reading, $notation) = $row;
    $i++;
    $reading = mb_convert_kana($reading, "aHc");
    if(empty($notation)) {
        throw new Error("error => " . $reading . "@" . $i);
    }
    fwrite($fh, "「$reading\t[" . optimise_tag_text(trim($notation)) . "]\t固有名詞" . PHP_EOL);
    $notations[] = trim($notation);
}

fwrite($fh, PHP_EOL);
fclose($fh);

$notations = array_unique($notations);
sort($notations);
file_put_contents(ROOT_DIR . "/output/hatebu_registered_tags.txt", implode(PHP_EOL, $notations));