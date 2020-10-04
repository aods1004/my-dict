<?php

namespace Aods1004\MyDict;

class UrlNormalizer
{
    /**
     * @param $url
     * @return string
     */
    static function normalize($url): string
    {
        // ハッシュ判定
        $url = UrlNormalizer::removeHash($url);
        // Twitter処理
        if (UrlNormalizer::isTwitterUrl($url)) {
            $url = UrlNormalizer::normalizeTwitterUrl($url);
        }
        // YouTube 処理
        if (UrlNormalizer::isYouTubeVideoUrl($url)) {
            $url = UrlNormalizer::normalizeYouTubeVideoUrl($url);
        }
        // Amazon 処理
        if (UrlNormalizer::isAmazonProductUrl($url)) {
            $url = UrlNormalizer::normalizeAmazonProductUrl($url);
        }
        // TikTok 処理
        if (UrlNormalizer::isTikTokUrl($url)) {
            $url = UrlNormalizer::normalizeTikTokUrl($url);
        }
        return $url;
    }

    /**
     * @param $url
     * @return bool
     */
    static function isTwitterUrl($url): bool
    {
        if (preg_match("|^https://twitter.com/|", $url)) {
            return true;
        }
        return false;
    }

    static function normalizeTwitterUrl($url)
    {
        return $url;
    }

    /**
     * @param $url
     * @return bool
     */
    static function isYouTubeVideoUrl($url): bool
    {
        if (preg_match("|^https://www.youtube.com/watch|", $url)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $url
     * @return string
     */
    static function normalizeYouTubeVideoUrl(string $url): string
    {
        parse_str(parse_url($url)["query"], $query);
        if (!empty($query['v']) && count($query) > 1) {
            $url = "https\://www.youtube.com/watch?v=" . $query['v'];
        }
        return $url;
    }
    /**
     * @param $url
     * @return bool
     */
    static function isAmazonProductUrl($url): bool
    {
        if (preg_match("|^https://www.amazon.co.jp/|", $url)) {
            return true;
        }
        return false;
    }
    /**
     * @param $url
     * @return string
     */
    static function normalizeAmazonProductUrl($url): string
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
    /**
     * @param $url
     * @return bool
     */
    static function isTikTokUrl($url): bool
    {
        if (preg_match("|^https://www.tiktok.com/|", $url)) {
            return true;
        }
        return false;
    }
    /**
     * @param $url
     * @return string
     */
    static function normalizeTikTokUrl($url): string
    {
        return $url;
    }
    /**
     * @param $url
     * @return string
     */
    static function removeHash($url): string
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