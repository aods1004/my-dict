<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";
$pdo = new PDO(DSN_BOOKMARK);
$pdo->exec("create table bookmark(url,tags);");
