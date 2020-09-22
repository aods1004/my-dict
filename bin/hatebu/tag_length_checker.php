<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

foreach (file(ROOT_DIR . "/data/tags_candidates.tsv") as $item) {
    echo optimise_tag_text(trim($item)) . PHP_EOL;
}
