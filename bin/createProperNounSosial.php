<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

$fh = fopen(ROOT_DIR . "/dict/SNS_生成.txt", "w");

$ex_notations = [];

$proper_noun = file(ROOT_DIR . "/data/proper_noun.tsv");
foreach ($proper_noun as $row) {
    if (substr($row, 0, 1) === '!' || empty(trim($row))) {
        continue;
    }
    list($reading, $notation) = explode("\t", trim($row));
    fwrite($fh, "「$reading\t[" . optimise_tag_text($notation) . "]\t固有名詞" . PHP_EOL);
}
$nijisanji_fanmark = file(ROOT_DIR . "/data/nijisanji_fanmark.tsv");
foreach ($nijisanji_fanmark as $row) {
    list($reading, $notation) = explode("\t", trim($row));
    fwrite($fh, "：$reading\t$notation\t固有名詞" . PHP_EOL);
}

fwrite($fh, PHP_EOL);
fclose($fh);