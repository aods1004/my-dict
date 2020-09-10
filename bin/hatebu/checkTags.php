<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
$res = $apiClient->get("my/tags");
$entry = json_decode($res->getBody()->getContents(), true);
var_dump(count($entry['tags']));
exit;

