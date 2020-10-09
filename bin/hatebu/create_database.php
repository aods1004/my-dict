<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";
//$pdo = new PDO(DSN_BOOKMARK);
//$pdo->exec("create table bookmark(url,tags);");

//$pdo = new PDO(DSN_YOUTUBE_VIDEO);
//$pdo->exec("create table video(id,data);");

$pdo = new PDO(DSN_BOOKMARK_EXCLUDE_URL);
$pdo->exec("create table exclude(url);");