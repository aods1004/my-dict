<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$results = [];
$resultSjis = [];
foreach (load_tsv(ROOT_DIR . "/data/tags_extract_keywords.tsv") as $line) {
    foreach (explode(",", $line[0]) as $word) {
        if (trim($word)) {
            $results[] = trim($word);
            $resultSjis[] = mb_convert_encoding(trim($word), "SJIS-win");
        }
    }
}
foreach (load_tsv(ROOT_DIR . "/data/tags_extract_keywords_fixed.tsv") as $line) {
    foreach (explode(",", $line[0]) as $word) {
        if (trim($word)) {
            $results[] = trim($word);
            $resultSjis[] = sjis_win(trim($word));
        }
    }
}

$resultSjis = array_unique($resultSjis);

$output = sjis_win("// はてブキーワード") . PHP_EOL .
    sjis_win("// CASE=True") . PHP_EOL .
    implode(PHP_EOL, $resultSjis);
file_put_contents(ROOT_DIR . "/dict/tags_extract_target_keywords.kwd", $output);

$results = array_unique($results);
$alphabetWords = [];
foreach ($results as $item) {
    preg_match_all("/\w+/", $item, $matches);
    foreach ($matches[0] as $match) {
        $alphabetWords[] = $match;
    }
}
$alphabetWords = array_unique($alphabetWords);
sort($alphabetWords);
file_put_contents(ROOT_DIR . "/dict/tags_extract_target_keywords.dic", implode(PHP_EOL, $alphabetWords));