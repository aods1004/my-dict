<?php
require_once dirname(__DIR__) . "/../vendor/autoload.php";
$apiClient = get_bookmark_api_client();
$res = $apiClient->get("my/tags");


$res = $apiClient->get("my/tags?page=3");
$myTags = json_decode($res->getBody()->getContents(), true);
foreach ($myTags as $tag) {
    var_dump($tag);
}