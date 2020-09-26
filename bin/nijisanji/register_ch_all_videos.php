<?php

use Aods1004\MyDict\BookmarkApiClient;
use \Aods1004\MyDict\BookmarkEntry;

require_once dirname(__DIR__) . "/../vendor/autoload.php";

$channel_id = 'UCl1oLKcAq93p-pwKfDGhiYQ';
// キーワード抽出に説明欄を加えるか？
$include_description_flag = false;
// テストならはてなに投稿しない
$preparation_flag = true; // true or false
// はてなに登録ずみのエントリーをスキップする
$skip_registered_entry_flag = true; // true or false

$list = get_all_upload_videos_by_channel_id($channel_id);

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
        $count ++;
    }
    foreach (array_reverse($list) as $video) {
        $url = $video['url'];
        $title = $video['channel_title'] . " : " . $video['title'];
        $comment = '';
        $tags = [];
        $registered_flag = $bookmarkClient->exist($url);
        if ($skip_registered_entry_flag && $registered_flag) {
            continue;
        }
        if (check_exclude_url($url)) {
            continue;
        }
        ob_start();
        $no++;
        echo "# No. {$no} #########################################################" . PHP_EOL;
        echo " + " . get_hatebu_entry_url($url) . PHP_EOL;
        // 抽出用文章を生成
        $extract_base = $title . ($include_description_flag ? $video['description'] : $title);
        // コメント
        $bookmark = $registered_flag ? $bookmarkClient->fetch($url) : [];
        $comment = isset($set['comment']) ? $bookmark['comment'] : '';
        $created_epoch = isset($set['created_epoch']) ? $bookmark['created_epoch'] : null;
        $tags = isset($bookmark['tags']) ? $bookmark['tags'] : [];
        // タグの生成
        $tags = create_tags($url, $extract_base, $tags);
        if (! check_over_tag_limit($tags)) {
            usort($tags, 'tag_compare');
            $comment = "[".implode("][", $tags)."]";
            goto OUTPUT_INFO;
        }
        // 投稿内容の組み立て
        list($comment, $tags) = build_hatena_bookmark_comment(compact('tags', 'comment', 'created_epoch'));
        // 更新する事項があるか？
        if ($bookmarkClient->beNotChange($url, $comment, $tags)) {
            echo " ***** Bookmarkは更新されていません *****" . PHP_EOL;
            goto OUTPUT_INFO;
        }
        // タグが最低限設定されているか？
        if (! check_fulfill_tag_count_condition($tags)) {
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

function check_exclude_url($url) {
    $exclude_urls = get_exclude_url();
    if (isset($exclude_urls[$url])) {
        echo " ***** URLがスキップ対象です *****" . PHP_EOL;
        return true;
    }
    return false;
}

function create_tags($url, $title, $tags) {
    static $tagExchanger, $ltvCount = 100;
    if (empty($tagExchanger) || $ltvCount < 1) {
        $tagExchanger = get_tag_exchanger();
        $ltvCount = 100;
    }
    $ltvCount--;
    $tags =  $tagExchanger->extractKeywords($tags, new BookmarkEntry(compact('url', 'title')));
    $tags = $tagExchanger->exchange($tags);
    return $tagExchanger->optimise($tags);
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

function clean_up() {
    echo PHP_EOL;
}