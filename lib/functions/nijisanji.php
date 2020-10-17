<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

/**
 *   タグ辞書ロード
 * ----------------------------------------------------------------------------------------------
 */

function get_ruby_tag_dict(): array
{
    $tags_dict = [];
    foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/name_hatebu_tags.tsv") as $row) {
        $ruby = $row[0] ?? null;
        $tag = $row[1] ?? null;
        if ($ruby && $tag) {
            $tags_dict[$ruby] = $tag;
        }
    }
    return $tags_dict;
}

/**
 *   ルビ・名前辞書ロード
 * ----------------------------------------------------------------------------------------------
 */

function get_name_ruby_dict(): array
{
    $name_ruby_dict = [];
    foreach (load_csv(ROOT_DIR . "/data/nijisanji_members/name.tsv") as $row) {
        $ruby = $row[0] ?? null;
        $name = $row[1] ?? null;
        if ($ruby && $name) {
            $data[] = $ruby . "\t" . trim($name) . "\t" . rawurlencode(trim($name));
            $name_ruby_dict[$name] = $ruby;
        }
    }
    return $name_ruby_dict;
}
/**
 *   ルビ・名前辞書ロード
 * ----------------------------------------------------------------------------------------------
 */
/**
 * @param string $surfix
 * @return array
 */
function get_youtube_channel_ids(string $surfix = ""): array
{
    $ret = [];
    foreach (load_csv(ROOT_DIR . "/data/youtube_nijisanji_channel{$surfix}.tsv") as $row) {
        $id = $row[1] ?? null;
        if ($id) {
            $ret[] = trim($id);
        }
    }
    return $ret;
}
