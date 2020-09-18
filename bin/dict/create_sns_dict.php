<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$fh = fopen(ROOT_DIR . "/dict/SNS_生成.txt", "w");

$i = 0;
foreach (load_tsv(dirname(__FILE__) . "/data/SNS単語帳.tsv") as $row) {
    list($reading, $notation) = $row;
    $i++;
    $reading = mb_convert_kana($reading, "aHc");
    if(empty($notation)) {
        throw new Error("error => " . $reading . "@" . $i);
    }
    fwrite($fh, "「$reading\t[" . optimise_tag_text(trim($notation)) . "]\t固有名詞" . PHP_EOL);
}

fwrite($fh, PHP_EOL);
fclose($fh);