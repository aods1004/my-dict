<?php

use GuzzleHttp\Client;

function load_tsv($file_path)
{
    $file = file($file_path);
    foreach ($file as $row) {
        if (substr($row, 0, 1) === '!' || empty(trim($row))) {
            continue;
        }
        $ret = [];
        $data = explode("\t", $row);
        foreach ($data as $datum) {
            $ret[] = trim($datum);
        }
        yield $ret;
    }
}

function array_equal($a, $b)
{
    if (array_diff($a, $b) || array_diff($b, $a)) {
        return false;
    }
    return true;
}

function get_http_client()
{
    return new Client(['http_errors' => false,]);
}
function get($url, array $option = []) {
    $client = get_http_client();
    try {
        $ret = $client->get($url, $option);
        return json_decode($ret->getBody()->getContents(), true);
    } catch (Throwable $e) {
        return null;
    }
}

function check_url_status($url)
{
    $client = get_http_client();
    try {
        $ret = $client->get($url);
        return $ret->getStatusCode();
    } catch (Throwable $e) {
        return null;
    }
}