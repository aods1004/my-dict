<?php

use Aods1004\MyDict\BookmarkApiClient;

require_once dirname(__DIR__) . "/../vendor/autoload.php";
load_config();
$list = get_all_upload_videos_by_channel_ids(get_youtube_channel_ids());

START:
echo "! START ########################################################" . PHP_EOL;

$config = load_config();
$skip_register_phase_flag = $config['skip_register_phase'];
$skip_registered_entry_flag = $config['skip_registered_entry'];
$include_description_flag = $config['include_description'];
$minimum_tag_count = $config['minimum_tag_count'];

$bookmarkClient = new BookmarkApiClient(get_bookmark_api_client(), new PDO(DSN_BOOKMARK));
$no = 0;
$output = [];
try {
    $register_set = [];
    $count = 0;
    foreach (get_all_bookmarks() as $bookmark) {
        if ($count > 50) break;
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
        echo "! No. {$no} ===================================================================== " . PHP_EOL;
        // echo " + " . get_hatebu_add_url($url) . PHP_EOL;
        // タグの生成
        $tags = create_tags($url, $extract_base, $tags);
        $tags = array_diff($tags, ["🌐YouTube"]);
        if (!check_over_tag_limit($tags)) {
            usort($tags, 'tag_compare');
            $comment = "[" . implode("][", $tags) . "]";
            goto OUTPUT_INFO;
        }
        // 投稿内容の組み立て
        list($comment, $tags) = build_hatena_bookmark_comment(compact('tags', 'comment', 'created_epoch'));
        // 更新する事項があるか？
        if ($bookmarkClient->beNotChange($url, $tags, $comment)) {
            echo "! ***** Bookmarkは更新されていません *****" . PHP_EOL;
            goto OUTPUT_INFO;
        }
        // タグが最低限設定されているか？
        if (!check_fulfill_tag_count_condition($tags)) {
            goto OUTPUT_INFO;
        }
        // 準備フラグがたっていれば、登録をスキップ
        if ($skip_register_phase_flag) {
            echo "! ***** 登録内容のテストです *****" . PHP_EOL;
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
    if ($register_set) {
        echo "# POST TO HATEBU ###############################################" . PHP_EOL;
        foreach ($register_set as $set) {
            $bookmarkClient->put($set['url'], $set['comment'], $set['tags']);
        }
    }
} catch (Throwable $exception) {
    var_dump($exception);
}
$output = array_reverse($output);
file_put_contents(ROOT_DIR . "/output/output.tsv", implode(PHP_EOL, $output));

if ($skip_register_phase_flag) {
    sleep(3);
    goto START;
}
set_config("skip_register_phase", 1);
exit;

/**
 * @param $bookmark
 * @return array
 */
function extract_bookmark($bookmark): array
{
    $comment = _elm($bookmark, 'comment','');
    $created_epoch = _elm($bookmark, 'created_epoch');
    $tags = _elm($bookmark, 'tags', []);
    return [$comment, $created_epoch, $tags];
}

/**
 * @param $url
 * @return bool
 */
function check_exclude_url($url): bool
{
    $exclude_urls = get_exclude_url();
    if (isset($exclude_urls[$url])) {
        return true;
    }
    return false;
}

/**
 * @param $tags
 * @return bool
 */
function check_over_tag_limit($tags): bool
{
    $tagCount = count_helpful_tag($tags);
    if ($tagCount > 10) {
        echo "! ***** ERROR ****************" . PHP_EOL;
        echo "! ***** タグが多いです ($tagCount)*****" . PHP_EOL;
        return false;
    }
    return true;
}

/**
 * @param $tags
 * @return bool
 */
function check_fulfill_tag_count_condition($tags): bool
{
    $config = load_config();
    $tagCount = count_helpful_tag($tags);
    if ($tagCount < $config['minimum_tag_count']) {
        echo "! ***** ERROR ****************" . PHP_EOL;
        echo "! ***** タグが少ないです ($tagCount) *****" . PHP_EOL;
        return false;
    }
    return true;
}

/**
 * @param $url
 * @param $title
 * @param $comment
 */
function output_info($url, $title, $comment)
{
    echo $url . PHP_EOL;
    echo $comment . PHP_EOL;
//    $list = explode("🎦", $comment);
//    echo $list[0] . PHP_EOL;
//    echo "! 🎦" . $list[1] . PHP_EOL;
    echo $title;
}

/**
 *
 */
function clean_up()
{
    echo PHP_EOL;
}

/**
 * @return array
 */
function load_config(): array
{
    $ret = [
        'skip_register_phase' => true,
        'skip_registered_entry' => true,
        'include_description' => false,
        'minimum_tag_count' => 2,
    ];
    foreach (load_tsv(__DIR__ . "/crawl_channel_videos_config.tsv") as $row) {
        $ret[$row[0]] = $row[1];
    }
    return $ret;
}

function set_config($key, $value)
{
    $data = file_get_contents(__DIR__ . "/crawl_channel_videos_config.tsv");
    $ret = [];
    foreach (array_filter(preg_split("/[\r,\n]/", $data)) as $line) {
        $ret[] = preg_replace("/^{$key}\t.*$/", "$key\t{$value}", trim($line));
    }
    file_put_contents(__DIR__ . "/crawl_channel_videos_config.tsv", implode(PHP_EOL, $ret));
}