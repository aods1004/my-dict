<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$fh = fopen(ROOT_DIR . "/dict/SNS_生成.txt", "w");

foreach (load_tsv(dirname(__FILE__) . "/data/SNS単語帳.tsv") as $row) {
    list($reading, $notation) = $row;
    fwrite($fh, "「$reading\t[" . optimise_tag_text($notation) . "]\t固有名詞" . PHP_EOL);
}

fwrite($fh, PHP_EOL);
fclose($fh);