<?php

function curl_url($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_TCP_FASTOPEN, true);
    curl_setopt($ch, CURLOPT_ENCODING,  '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $json = json_decode(curl_exec($ch));
    $response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curl_errno = curl_errno($ch);
    curl_close($ch);

    if($response_code !== 200 || empty($json) || $curl_errno > 0)
    {
        return null;
    }

    return $json;
}

function is_flooding($id, $_hits, $_time)
{
    global $cache;

    $hitCache = $cache->get($id);
    $default = array('time' => time(), 'hit' => 1);

    if (is_null($hitCache))
    {
        $cache->set($id, $default);
    } else {

        // I should fix this upstream...
        // Sometimes the output does not get unserialized
        if (!is_array($hitCache))
        {
            $hitCache = unserialize($hitCache);
        }

        $time = $hitCache['time'];
        $hit = $hitCache['hit'];

        if ((time() - $time) > $_time)
        {
            $cache->set($id, $default);
            return false;
        }

        if ($hit >= $_hits)
        {
            return true;
        }

        $hit += 1;

        $data = array('time' => $time, 'hit' => $hit);
        $cache->set($id, $data);
    }

    return false;
}

function exit_error($output)
{
    http_response_code(406);
    exit($output);
}