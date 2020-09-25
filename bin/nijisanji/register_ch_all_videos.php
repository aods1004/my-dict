<?php

use Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\BookmarkEntry;

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$channel_id = 'UCkIimWZ9gBJRamKF0rmPU8w';  //天宮こころ
// キーワード抽出に説明欄を加えるか？
$include_description_flag = false;
// テストならはてなに投稿しない
$preparation_flag = false; // true or false
// はてなに登録ずみのエントリーをスキップする
$skip_registered_entry = true; // true or false

$list = get_all_upload_videos_by_channel_id($channel_id);

START:
echo "# START ########################################################" . PHP_EOL;
$bookmarkClient = new BookmarkApiClient(get_bookmark_api_client(), new PDO(DSN_BOOKMARK));
$tagExchanger = get_tag_exchanger();
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
        $item = $bookmarkClient->fetch($url);
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
        if ($preparation_flag) {
            echo " ***** 登録内容のテストです *****" . PHP_EOL;
            goto OUTPUT_INFO;
        }
        $register_set[] = compact('url', 'comment', 'tags');
        OUTPUT_INFO:
        echo $url . PHP_EOL;
        echo $title . PHP_EOL;
        echo $comment . PHP_EOL;
        CLEAN_UP:
        echo PHP_EOL;
        $output[] = ob_get_flush();
    }
    echo "# POST TO HATEBU ###############################################" . PHP_EOL;
    foreach ($register_set as $item) {
        $bookmarkClient->put($item['url'], $item['comment'], $item['tags']);
    }
} catch (Throwable $exception) {
    var_dump($exception);
}
$output = array_reverse($output);
file_put_contents(ROOT_DIR . "/output/output.tsv", implode(PHP_EOL, $output));

if ($preparation_flag) {
    goto START;
}
