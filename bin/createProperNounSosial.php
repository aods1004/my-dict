<?php

define("ROOT", dirname(__DIR__));

$ex_data = file(ROOT . "/data/proper_noun_hatebu_ex.tsv");
$fh = fopen(ROOT . "/dict/SNS_生成.txt", "w");

$ex_notations = [];
foreach($ex_data as $row) {
    list($normal, $ex_notation) = explode("\t", trim($row));
    $ex_notations[$normal] = $ex_notation;
}

$proper_noun = file(ROOT . "/data/proper_noun.tsv");
foreach($proper_noun as $row) {
    if (substr($row, 0, 1) === '!') {
        continue;
    }
    if (empty(trim($row))) {
        continue;
    }
    list($reading, $notation) = explode("\t", trim($row));
    if (isset($ex_notations[$reading])) {
        $notation = $ex_notations[$reading];
    }
    fwrite($fh, "「$reading\t[$notation]\t固有名詞" . PHP_EOL);
}
$nijisanji_fanmark = file(ROOT . "/data/nijisanji_fanmark.tsv");
foreach($nijisanji_fanmark as $row) {
    list($reading, $notation) = explode("\t", trim($row));
    fwrite($fh, "：$reading\t$notation\t固有名詞" . PHP_EOL);
}

fwrite($fh, PHP_EOL);
fclose($fh);