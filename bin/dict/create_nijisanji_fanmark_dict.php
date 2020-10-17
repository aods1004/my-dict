<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$fh = fopen(ROOT_DIR . "/dict/nijisanji_fanmark.txt", "w");
foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/name_fanmark.tsv") as $row) {
    list($reading, $notation) = $row;
    if ($reading && $notation) {
        fwrite($fh, "：$reading\t$notation\t記号" . PHP_EOL);
    }
}
fwrite($fh, PHP_EOL);
fclose($fh);