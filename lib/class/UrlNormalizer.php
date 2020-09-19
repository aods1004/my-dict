<?php

namespace Aods1004\MyDict;

class UrlNormalizer
{
    static function isTwitterUrl($url)
    {
        if (preg_match("|^https://twitter.com/|", $url)) {
            return true;
        }
        return false;
    }
    static function isTweetUrl($url)
    {
        if (static::isTwitterUrl($url)) {
            if (strpos($url, 'status')) {
                return true;
            }
        }
        return false;
    }
    static function normalizeTwitterUrl($url)
    {
        return $url;
    }
    static function isYouTubeVideoUrl($url)
    {
        if (preg_match("|^https\://www.youtube.com/watch|", $url)) {
            return true;
        }
        return false;
    }
    static function normalizeYouTubeVideoUrl($url)
    {
        parse_str(parse_url($url)["query"], $query);
        if (!empty($query['v']) && count($query) > 1) {
            $url = "https\://www.youtube.com/watch?v=" . $query['v'];
        }
        return $url;
    }
    static function isAmazonProductUrl($url)
    {
        if (preg_match("|^https://www.amazon.co.jp/|", $url)) {
            return true;
        }
        return false;
    }

    static function normalizeAmazonProductUrl($url)
    {
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
        return $url;
    }
    static function isTikTokUrl($url)
    {
        if (preg_match("|^https://www.tiktok.com/|", $url)) {
            return true;
        }
        return false;
    }
    static function normalizeTikTokUrl($url)
    {
        return $url;
    }

    static function removeHash($url)
    {
        // ハッシュでSPAしているサイト一覧
        $excludes = [
            "https://www.yumenographia.com/"
        ];

        foreach ($excludes as $exclude) {
            if (strpos($url, $exclude) !== false) {
                return $url;
            }
        }
        if (preg_match("|^(.*)#|", $url, $match)) {
            $url = $match[1];
        }
        return $url;
    }
}