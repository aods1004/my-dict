<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

use GuzzleHttp\Client;

function empty_file($file)
{
    file_put_contents(ROOT_DIR . "/output/" . $file, "");
}

function tee($line, $file = "output.tsv")
{
    file_put_contents(ROOT_DIR . "/output/" . $file, $line . PHP_EOL, FILE_APPEND);
    return $line;
}

function sjis_win($word)
{
    return mb_convert_encoding($word, "SJIS-win");
}

/**
 * @param $file_path
 * @param string $delimiter
 * @return Generator
 */
function load_csv($file_path, $delimiter = "\t"): Generator
{
    $file = file($file_path);
    foreach ($file as $row) {
        if (strpos($row, '!') === 0 || empty(trim($row))) {
            continue;
        }
        $ret = [];
        $data = explode($delimiter, $row);
        foreach ($data as $datum) {
            $ret[] = trim($datum);
        }
        yield $ret;
    }
}

/**
 * @return Client
 */
function get_http_client(): Client
{
    return new Client(['http_errors' => false,]);
}

function get($url, array $option = [])
{
    $client = get_http_client();
    try {
        $ret = $client->get($url, $option);
        return json_decode($ret->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return null;
    }
}

function check_url_status($url): ?int
{
    $client = get_http_client();
    try {
        $ret = $client->get($url);
        return $ret->getStatusCode();
    } catch (Throwable $e) {
        return null;
    }
}

function _elm($data, $key, $default = null)
{
    return $data[$key] ?? $default;
}