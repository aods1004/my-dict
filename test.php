<?php

$comment = "";
if (! preg_match("/^🎦\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}$/m", $comment, $match)) {
    $comment = $published_at . " " . $comment;
}