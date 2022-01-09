<?php
require_once('func.curl.php');
require_once('func.cache.php');

$file = implode('|', build());
set_cache($file);

function build()
{
    $url = curl_url('https://a.4cdn.org/boards.json');

    if (is_null($url))
    {
        return array('b');
    }

    $build = array();
    foreach ($url->boards as $board)
    {
        if (isset($board->forced_anon))
        {
            array_push($build, $board->board);
        }
    }

    return empty($build) ? array('b') : $build;
}