<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$pdo = new PDO(DSN_BOOKMARK_EXCLUDE_URL);

foreach (load_tsv(ROOT_DIR . "/data/exclude_url.tsv") as $row) {
    $url = $row[0] ?? null;
    if (! $url) {
        continue;
    }
    $stmt = $pdo->prepare("insert into exclude_url (url) values (:url);");
    $stmt->bindValue(":url", trim($row[0]));
    $stmt->execute();
}

echo date("Y-m-d H:i:s") . PHP_EOL;