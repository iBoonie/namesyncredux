<?php
require_once('config.php');
require_once('FileCacher.php');
require_once('inc.func.php');

$cache = new FileCacher(CACHE_FOLDER);

$data = implode('|', build());
$cache->set('boards', $data);

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