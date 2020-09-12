<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$fh = fopen(ROOT_DIR . "/dict/絵文字_生成.txt", "w");
foreach (load_tsv(dirname(__FILE__) . "/data/nijisanji_fanmark.tsv") as $row) {
    list($reading, $notation) = $row;
    fwrite($fh, "：$reading\t$notation\t記号" . PHP_EOL);
}
fwrite($fh, PHP_EOL);
fclose($fh);