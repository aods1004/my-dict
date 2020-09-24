<?php

use Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\BookmarkEntry;

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
$itemFetcher = new BookmarkApiClient($apiClient);
$tagExchanger = get_tag_exchanger();

$channel_id = 'UC_a1ZYZ8ZTXpjg9xUY9sj8w'; // 鈴原るる
$include_description_flag = false;

// はてなに登録ずみのエントリーをスキップする
$skip_registered_entry = true;

// テストならはてなに投稿しない
$test_flag = true; // true or false
$list = get_all_upload_videos_by_channel_id($channel_id);
$exclude_urls = get_exclude_url();

$no = 0;
$output = [];
try {
    $register_set = [];
    foreach (array_reverse($list) as $video) {
        $url = $video['url'];
        $title = $video['channel_title'] . " : " . $video['title'];
        $comment = '';
        $tags = [];

        if (isset($exclude_urls[$url]) && $skip_registered_entry) {
            continue;
        }

        $item = $itemFetcher->fetch($url);

        if (! empty($item) && $skip_registered_entry) {
            continue;
        }

        ob_start();
        $no++;
        echo "# No. {$no} #########################################################" . PHP_EOL;
        echo " + " . get_hatebu_entry_url($url) . PHP_EOL;
        if (isset($exclude_urls[$url])) {
            echo " ***** URLがスキップ対象です *****" . PHP_EOL;
            goto CLEAN_UP;
        }
        if (! empty($item)) {
            echo " ***** すでに登録されています *****" . PHP_EOL;
            $comment = $item['comment_raw'];
            goto OUTPUT_INFO;
        }
        $extractBase = $title;
        if ($include_description_flag) {
            $extractBase .= $video['description'];
        }
        // タグの生成
        $tags =  $tagExchanger->extractKeywords([],
            new BookmarkEntry(['title' => $extractBase, 'url' => $url]));
        $tags = $tagExchanger->exchange($tags);
        $tags = $tagExchanger->optimise($tags);
        $tagCount = count_helpful_tag($tags);
        if ($tagCount > 10) {
            echo " ***** ERROR ****************" . PHP_EOL;
            echo " ***** タグが多いです ($tagCount)*****" . PHP_EOL;
            usort($tags, 'tag_compare');
            $comment = "[".implode("][", $tags)."]";
            goto OUTPUT_INFO;
        }
        list($comment, $tags) = build_hatena_bookmark_comment(compact('tags'));
        if (count_helpful_tag($tags) < 1) {
            echo " ***** ERROR ****************" . PHP_EOL;
            echo " ***** タグが少ないです *****" . PHP_EOL;
            goto OUTPUT_INFO;
        }
        if ($test_flag) {
            echo " ***** 登録内容のテストです *****" . PHP_EOL;
            goto OUTPUT_INFO;
        }
        $register_set[] = compact('url', 'comment');
        OUTPUT_INFO:
        echo " + {$url}" . PHP_EOL;
        echo " + {$title}" . PHP_EOL;
        echo " + " . $comment . PHP_EOL;
        CLEAN_UP:
        echo PHP_EOL;
        $output[] = ob_get_flush();
    }
    echo "# POST TO HATEBU ###############################################" . PHP_EOL;
    foreach ($register_set as $item) {
        $apiClient->post("my/bookmark",
            ["form_params" => ["url" => $item['url'], "comment" => $item['comment']]]);
    }
} catch (Throwable $exception) {
    var_dump($exception);
}
$output = array_reverse($output);
file_put_contents(ROOT_DIR . "/output/output.tsv", implode(PHP_EOL, $output));