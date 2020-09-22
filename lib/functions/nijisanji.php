<?php

require_once dirname(__DIR__) . "/../vendor/autoload.php";

/**
 *   タグ辞書ロード
 * ----------------------------------------------------------------------------------------------
 */

function get_ruby_tag_dict() {
    $tags_dict = [];
    foreach (load_tsv(ROOT_DIR . "/data/nijisanji_members/name_hatebu_tags.tsv") as $row) {
        list($ruby, $tag) = $row;
        $tags_dict[$ruby] = $tag;
    }
    return $tags_dict;
}

/**
 *   ルビ・名前辞書ロード
 * ----------------------------------------------------------------------------------------------
 */

function get_name_ruby_dict() {
    $name_ruby_dict = [];
    foreach (load_tsv(ROOT_DIR . "/data/nijisanji_members/name.tsv") as $row) {
        list($ruby, $name) = $row;
        $data[] = $ruby . "\t" . trim($name) . "\t" . rawurlencode(trim($name));
        $name_ruby_dict[$name] = $ruby;
    }
    return $name_ruby_dict;
}
