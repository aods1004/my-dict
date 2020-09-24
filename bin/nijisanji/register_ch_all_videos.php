<?php

use Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\BookmarkEntry;

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$apiClient = get_bookmark_api_client();
$itemFetcher = new BookmarkApiClient($apiClient);
$tagExchanger = get_tag_exchanger();

// $channel_id = 'UCD-miitqNY3nyukJ4Fnf4_A'; // 月ノ美兎
// $include_description_flag = false;
// $channel_id = 'UCb6ObE-XGCctO3WrjRZC-cw'; // ルイス・キャミー
// $include_description_flag = false;
// $channel_id = 'UCRqBKoKuX30ruKAq05pCeRQ'; // 北小路ヒスイ
// $include_description_flag = false;
// $channel_id = 'UC3JF7ZR2uc5j4tJy_kVwGhw'; // Vtuber切り抜キング
// $include_description_flag = false;
// $channel_id = 'UCe_p3YEuYJb8Np0Ip9dk-FQ'; // 朝日南アカネ
// $include_description_flag = false;
// $channel_id = 'UCfd2kwYa-7Kt5XRomZAq9bg'; // Vが好きすぎる者
// $include_description_flag = true;
// $channel_id = 'UC_82HBGtvwN1hcGeOGHzUBQ'; // 空星きらめ
// $include_description_flag = true;
// $channel_id = 'UCo7TRj3cS-f_1D9ZDmuTsjw'; // 町田ちま
// $include_description_flag = false;
//$channel_id = 'UCX7YkU9nEeaoZbkVLVajcMg'; // にじさんじ
//$include_description_flag = true;
//$channel_id = 'UCSy4ovsHKC3OsmO3gfSupLw'; // カワセミ
//$include_description_flag = true;
//$channel_id = 'UC4z3IHrs62HwEpW-4D9hJ-w'; // そらーむ
//$include_description_flag = true;
//$channel_id = 'UCOzL9movn_q9l_qNE2pVrZA'; // にじ切りの民
//$include_description_flag = true;
//$channel_id = 'UCL_O_HXgLJx3Auteer0n0pA'; // 周央サンゴ
//$include_description_flag = false;
//$channel_id = 'UCtnO2N4kPTXmyvedjGWdx3Q'; // レヴィエリファ
//$include_description_flag = true;
//$channel_id = 'UC-fO5FbHoBNNhtfGh5gBYew'; // 毎日にじさんじ生活
//$include_description_flag = true;
//$channel_id = 'UC1QgXt46-GEvtNjEC1paHnw'; // グウェル・オス・ガール
//$include_description_flag = true;
//$channel_id = 'UCL34fAoFim9oHLbVzMKFavQ'; // 夜見れな
//$include_description_flag = true;
//$channel_id = 'UCOCxiAF2iz67wKHCf5xSatg'; // ばーちゃるタイム
//$include_description_flag = true;
//$channel_id = 'UCvzVB-EYuHFXHZrObB8a_Og'; // 矢車りね - Rine Yaguruma -
//$include_description_flag = false;
//$channel_id = 'UCRWOdwLRsenx2jLaiCAIU4A'; // 雨森小夜
//$include_description_flag = false;
//$channel_id = 'UC58K340b1nlljzhzlN3uo1w'; // まるい【にじさんじ切り抜き】
//$include_description_flag = true;
// $channel_id = 'UCfQVs_KuXeNAlGa3fb8rlnQ'; // 桜凛月
// $include_description_flag = true;
//$channel_id = 'UCbYfRwFlqe3WAyZsA57MFOg'; // ひまvlog
//$include_description_flag = true;
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
        echo "# No. {$no} ####################################################################################" . PHP_EOL;
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
    echo "# POST TO HATEBU################################################################################" . PHP_EOL;
    foreach ($register_set as $item) {
        $apiClient->post("my/bookmark",
            ["form_params" => ["url" => $item['url'], "comment" => $item['comment']]]);
    }
} catch (Throwable $exception) {
    var_dump($exception);
}
$output = array_reverse($output);
file_put_contents(ROOT_DIR . "/output/output.tsv", implode(PHP_EOL, $output));