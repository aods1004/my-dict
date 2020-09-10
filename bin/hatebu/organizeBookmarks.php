<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
$client = get_http_client();
$exchange = [];
foreach (file(__DIR__."/変換表.tsv") as $row) {
    list($from, $to) = explode("\t", trim($row));
    $from = optimise_tag_text($from);
    $to = optimise_tag_text($to);
    $exchange[$from] = $to;
}

$no = 0;
$history = [];
foreach (get_all_bookmarks(true) as $entry) {
    $no++;
    echo "[$no] ---------------------------------" . PHP_EOL;
    try {
        $url = $entry['url'];
        $currentUrl = $url;
        echo "URL: " . $url . PHP_EOL;
        $res = $apiClient->get("my/bookmark", ['query' => ['url' => $url]]);
        $item = json_decode($res->getBody()->getContents(), true);
        $item['tags'] = array_unique(array_merge($item['tags'], $entry['tags']));
        $currentComment = isset($item['comment_raw']) ? $item['comment_raw'] : "";
        if ($res->getStatusCode() == '200') {
            /**
             * タグ変換処理
             */
            if (!empty($item['tags'])) {
                foreach ($item['tags'] as $key => $tag) {
                    foreach ($exchange as $from => $to) {
                        if ($tag == $from) {
                            $tag = $to;
                        }
                    }
                    $replace = [
                        "📽" => "🎥",
                    ];
                    foreach ($replace as $from => $to) {
                        $tag = str_replace($from, $to, $tag);
                    }
                    $item['tags'][$key] = $tag;
                }
            }

            /**
             * Twitter処理
             */
            if (preg_match("|^https://twitter.com/(\w+)$|", $url)) {
                $currentUrl = $url;
                $url = strtolower($url);
            }
            /**
             * YouTube 処理
             */
            if (preg_match("|^https\://www.youtube.com/watch|", $url)) {
                $item['tags'] = try_to_append_tag($item['tags'], "🌐YouTube");
                parse_str(parse_url($url)["query"], $query);
                if (!empty($query['v']) && count($query) > 1) {
                    $currentUrl = $url;
                    $url = "https\://www.youtube.com/watch?v=" . $query['v'];
                }
            }
            /**
             * Amazon 処理
             */
            if (preg_match("|^https://www.amazon.co.jp/|", $url)) {
                $item['tags'] = try_to_append_tag($item['tags'], "🌐Amazon");
                $amazonProductId = null;
                if (preg_match("|^https://www.amazon.co.jp/(.*/)?dp/(\w+)/?|", $url, $match)) {
                    if (isset($match[2])) {
                        $amazonProductId = $match[2];
                    }
                }
                if (preg_match("|^https://www.amazon.co.jp/(.*/)?gp/product/(\w+)/?|", $url, $match)) {
                    if (isset($match[2])) {
                        $amazonProductId = $match[2];
                    }
                }
                if ($amazonProductId) {
                    $url = "https://www.amazon.co.jp/gp/product/" . $amazonProductId;
                }
            }
            /**
             * Hash
             */
            if (preg_match("|^(.*)#|", $url, $match)) {
                $url = $match[1];
            }
            /**
             * 削除処理
             */
            if ($currentUrl != $url) {
                echo "DEL URL: " . $currentUrl . PHP_EOL;
                $apiClient->delete("my/bookmark", ['query' => ['url' => $currentUrl]]);
            }
            /**
             * 更新処理
             */
            $comment = build_hatena_bookmark_comment($item);
            if (empty($item['tags'])) {
                echo "*** EMPTY ENTRY! ***" . PHP_EOL;
                echo "COMMENT: " . $comment . PHP_EOL;
            }
            if ($currentComment != $comment || $currentUrl != $url) {
                $history[$url] = ['title' => $entry['title'], 'url' => $url, 'comment' => $comment];
                echo "TITLE: " . $entry['title'] . PHP_EOL;
                echo "COMMENT: " . $comment . PHP_EOL;
                $res = $apiClient->post("my/bookmark?url=" . $url, ["form_params" => ["comment" => $comment]]);
                $resData = json_decode($res->getBody()->getContents(), true);;
                if ($resData['comment_raw'] != $comment) {
                    echo "*** FAILED TO UPDATE ***" . PHP_EOL;
                    echo "RESULT: " . $resData['comment_raw'];
                    exit;
                }
            }
        } else {
            echo "NOT FOUND: " . $url . PHP_EOL;
        }
    } catch (Throwable $exception) {
        var_dump($exception);
        break;
    }
}