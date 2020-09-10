<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

$fh = fopen(ROOT_DIR . "/dict/絵文字_生成.txt", "w");

$nijisanji_fanmark = file(ROOT_DIR . "/data/nijisanji_fanmark.tsv");
foreach ($nijisanji_fanmark as $row) {
    list($reading, $notation) = explode("\t", trim($row));
    fwrite($fh, "：$reading\t$notation\t記号" . PHP_EOL);
}

fwrite($fh, PHP_EOL);
fclose($fh);