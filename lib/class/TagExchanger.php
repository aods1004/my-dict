<?php

namespace Aods1004\MyDict;

class TagExchanger
{
    private $exchange = [];
    private $exclude = [];
    private $replace = [];

    public function __construct(array $exchange, array $replace, array $exclude)
    {
        $this->exchange = $exchange;
        $this->exclude = $exclude;
        $this->replace = $replace;
    }

    public function exchange($tags)
    {
        if (!empty($tags)) {
            foreach ($tags as $key => $tag) {
                foreach ($this->exchange as $from => $to) {
                    if ($tag == $from) {
                        $tag = $to;
                    }
                }
                foreach ($this->replace as $from => $to) {
                    $tag = str_replace($from, $to, $tag);
                }
                $tags[$key] = $tag;
                if (in_array($tag, $this->exclude)) {
                    unset($tags[$key]);
                }
            }
        }
        return $tags;
    }
}