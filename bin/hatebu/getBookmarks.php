<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
$no = 0;
$history = [];
foreach (get_all_bookmarks(true) as $entry) {
    $no++;
    echo "[$no] ---------------------------------" . PHP_EOL;
    try {
        $url = $entry['url'];
        $currentUrl = $url;
        $res = $apiClient->get("my/bookmark", ['query' => ['url' => $url]]);
        $item = json_decode($res->getBody()->getContents(), true);
        $currentComment = isset($item['comment_raw']) ? $item['comment_raw'] : "";
        if ($res->getStatusCode() == '200') {
            /**
             * タグ変換処理
             */
            if (! empty($item['tags'])) {
                foreach ($item['tags'] as $key => $value) {
                    $exchange = [
                        "居室整備" => "🔖快適に家で過ごす",
                        "つぶやき" => "💬つぶやき",
                        "グウェルオスガール" => "グウェル・オス・ガ…",
                        "ベルモンドバンデラス" => "ベルモンド・バンデ…",
                        "シェリンバーガンディ" => "シェリン・バーガン…",
                    ];
                    foreach ($exchange as $from => $to) {
                        if ($value === $from) {
                            $item['tags'][$key] = $to;
                        }
                    }
                }
            }
            $comment = build_hatena_bookmark_comment($item);
            /**
             * YouTube 処理
             */
            if (preg_match("|^https\://www.youtube.com/watch|", $url)) {
                parse_str(parse_url($url)["query"], $query);
                if (!empty($query['v']) && count($query) > 1) {
                    $currentUrl = $url;
                    $url = "https\://www.youtube.com/watch?v=" . $query['v'];
                }
            }

            /**
             * YouTube 処理
             */
            if (preg_match("|^https\://www.youtube.com/watch|", $url)) {
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
                $res = $apiClient->delete("my/bookmark", ['query' => ['url' => $currentUrl]]);
            }
            /**
             * 更新処理
             */
            if ($currentComment != $comment || $currentUrl != $url) {
                if (! isset($history[$url])) {
                    $history[$url] = ['title' => $entry['title'], 'url' => $url, 'comment' => $comment];
                    echo "TITLE: " . $entry['title'] . PHP_EOL;
                    echo "URL: " . $url . PHP_EOL;
                    echo "COMMENT: " . $comment . PHP_EOL;
                    $res = $apiClient->post("my/bookmark?url=" . $url, ["form_params" => ["comment" => $comment]]);
                } else {
                    echo "*** DUPLICATE ENTRY! ***" . PHP_EOL;
                    echo "URL: " . $url . PHP_EOL;
                    echo "COMMENT: " . $comment . PHP_EOL;
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