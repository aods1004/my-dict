<?php

define("ROOT", dirname(__DIR__));

$ex_data = file(ROOT . "/data/proper_noun_hatebu_ex.tsv");

$ex_notations = [];
foreach($ex_data as $row) {
    list($normal, $ex_notation) = explode("\t", trim($row));
    $ex_notations[$normal] = $ex_notation;
}

$liver_data = file(ROOT . "/data/proper_noun.tsv");
$fh = fopen(ROOT . "/dict/はてブタグ_固有名詞.txt", "w");
foreach($liver_data as $row) {
    // var_dump(explode("\t", trim($row)));
    list($reading, $notation) = explode("\t", trim($row));
    if (isset($ex_notations[$reading])) {
        $notation = $ex_notations[$reading];
    }
    fwrite($fh, "「$reading\t[$notation]\t固有名詞" . PHP_EOL);
}
fwrite($fh, PHP_EOL);
fclose($fh);