<?php

use Aods1004\MyDict\BookmarkApiClient;

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$channel_id = 'UCeShTCVgZyq2lsBW9QwIJcw';

// テストならはてなに投稿しない
$preparation_flag = true; // true or false
// はてなに登録ずみのエントリーをスキップする
$skip_registered_entry_flag = true; // true or false
// キーワード抽出に説明欄を加えるか？
$include_description_flag = false;
$list = get_all_upload_videos_by_channel_id($channel_id);
// $list = get_all_upload_videos_by_channel_ids(get_youtube_channel_ids());

START:
echo "# START ########################################################" . PHP_EOL;
$bookmarkClient = new BookmarkApiClient(get_bookmark_api_client(), new PDO(DSN_BOOKMARK));

$no = 0;
$output = [];
try {
    $register_set = [];
    $count = 0;
    foreach (get_all_bookmarks() as $bookmark) {
        if ($count > 5) break;
        $bookmarkClient->fetch($bookmark['url']);
        $count++;
    }
    foreach (array_reverse($list) as $video) {
        $url = $video['url'];
        $title = $video['channel_title'] . PHP_EOL . $video['title'];
        $published_at = '🎦' . date("Y/m/d H:i", $video['published_at']);

        $bookmark = [];
        if ($bookmarkClient->exist($url)) {
            if ($skip_registered_entry_flag) continue;
            $bookmark = $bookmarkClient->fetch($url);
        }
        list($comment, $created_epoch, $tags) = extract_bookmark($bookmark);
        if (! preg_match("/^🎦\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}/m", $comment, $match)) {
            $comment = $published_at . " " . $comment;
        }
        $extract_base = $title . ($include_description_flag ? $video['description'] : $title);
        if (check_exclude_url($url)) continue;
        ob_start();
        $no++;
        echo "# No. {$no} #########################################################" . PHP_EOL;
        echo " + " . get_hatebu_entry_url($url) . PHP_EOL;
        // タグの生成
        $tags = create_tags($url, $extract_base, $tags);
        if (!check_over_tag_limit($tags)) {
            usort($tags, 'tag_compare');
            $comment = "[" . implode("][", $tags) . "]";
            goto OUTPUT_INFO;
        }
        // 投稿内容の組み立て
        list($comment, $tags) = build_hatena_bookmark_comment(
            compact('tags', 'comment', 'created_epoch'));
        // 更新する事項があるか？
        if ($bookmarkClient->beNotChange($url, $tags, $comment)) {
            echo " ***** Bookmarkは更新されていません *****" . PHP_EOL;
            goto OUTPUT_INFO;
        }
        // タグが最低限設定されているか？
        if (!check_fulfill_tag_count_condition($tags)) {
            goto OUTPUT_INFO;
        }
        // 準備フラグがたっていれば、登録をスキップ
        if ($preparation_flag) {
            echo " ***** 登録内容のテストです *****" . PHP_EOL;
            goto OUTPUT_INFO;
        }
        // 登録用配列に設定
        $register_set[] = compact('url', 'comment', 'tags');
        OUTPUT_INFO:
        output_info($url, $title, $comment);
        CLEAN_UP:
        clean_up();
        $output[] = ob_get_flush();
    }
    echo "# POST TO HATEBU ###############################################" . PHP_EOL;
    foreach ($register_set as $set) {
        $bookmarkClient->put($set['url'], $set['comment'], $set['tags']);
    }
} catch (Throwable $exception) {
    var_dump($exception);
}
$output = array_reverse($output);
file_put_contents(ROOT_DIR . "/output/output.tsv", implode(PHP_EOL, $output));

if ($preparation_flag) {
    sleep(3);
    goto START;
}
exit;

function extract_bookmark($bookmark) {
    $comment = isset($bookmark['comment']) ? $bookmark['comment'] : '';
    $created_epoch = isset($bookmark['created_epoch']) ? $bookmark['created_epoch'] : null;
    $tags = isset($bookmark['tags']) ? $bookmark['tags'] : [];
    return [$comment, $created_epoch, $tags];
}

function check_exclude_url($url)
{
    $exclude_urls = get_exclude_url();
    if (isset($exclude_urls[$url])) {
        return true;
    }
    return false;
}

function check_over_tag_limit($tags)
{
    $tagCount = count_helpful_tag($tags);
    if ($tagCount > 10) {
        echo " ***** ERROR ****************" . PHP_EOL;
        echo " ***** タグが多いです ($tagCount)*****" . PHP_EOL;
        return false;
    }
    return true;
}

function check_fulfill_tag_count_condition($tags)
{
    $tagCount = count_helpful_tag($tags);
    if ($tagCount < 1) {
        echo " ***** ERROR ****************" . PHP_EOL;
        echo " ***** タグが少ないです ($tagCount) *****" . PHP_EOL;
        return false;
    }
    return true;
}

function output_info($url, $title, $comment)
{
    echo $url . PHP_EOL;
    echo $title . PHP_EOL;
    echo $comment . PHP_EOL;
}

function clean_up()
{
    echo PHP_EOL;
}