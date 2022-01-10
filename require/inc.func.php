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

    if (is_null($cache->get($id)))
    {
        $data = array('time' => time(), 'limit' => 1);
        $cache->set($id, $data);
    } else {
        $time = $cache->get($id)['time'];
        $limit = $cache->get($id)['limit'];

        if ((time() - $time) > $_time)
        {
            $data = array('time' => time(), 'limit' => 1);
            $cache->set($id, $data);
            return false;
        }

        if ($limit >= $_hits)
        {
            return true;
        }

        $limit += 1;

        $data = array('time' => $time, 'limit' => $limit);
        $cache->set($id, $data);
    }

    return false;
}

function exit_error($output)
{
    http_response_code(406);
    exit($output);
}