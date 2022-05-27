<?php
require_once('require/config.php');
require_once('require/FileCacher.php');
require_once('require/inc.func.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: https://boards.4chan.org');
header('Access-Control-Allow-Headers: x-requested-with, if-modified-since');

// Frensync uses this to see if the api is up
if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'OPTIONS') exit('[]');

$cache = new FileCacher(CACHE_FOLDER);

$method  = filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'GET';
$origin  = rtrim(filter_input(INPUT_SERVER, 'HTTP_ORIGIN'), '/') === 'https://boards.4chan.org';
$request = substr(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH'), 0, 8) === 'NameSync';
$board   = filter_input(INPUT_GET, 'b', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^(' . $cache->get('boards', 'b') . ')$/')));
$thread  = filter_input(INPUT_GET, 't', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^[\d,]*$/')));
$ip      = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);

if (!$origin)                   exit_error('Invalid Origin');
if (!$request)                  exit_error('Invalid Requested With');
if (!$method)                   exit_error('Invalid Request Method');
if (!$board)                    exit_error('Invalid Board');
if (!$thread)                   exit_error('Invalid Thread');
if (strlen($thread) > 1500)     exit_error('Thread String to Large');
if (is_null($ip))               exit_error('Invalid IP');

$floodID = "$board-qp-" . md5($ip);
if (is_flooding($floodID, GET_MAX_HITS, GET_TIME))
{
    exit(http_response_code(429));
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4;dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $ex) {
    echo 'Database error!';
    throw new Exception($ex->getMessage());
}

// If we are querying the catalog, thread numbers will be comma seperated
if (preg_match('/,/', $thread))
{
    $stmt = $pdo->prepare("SELECT DISTINCT(`thread`) FROM data WHERE board=:board AND FIND_IN_SET(`thread`, :thread)");

    $stmt->bindValue(':board', $board, PDO::PARAM_STR);
    $stmt->bindValue(':thread', $thread, PDO::PARAM_STR);
    $stmt->execute();
    $fetch = $stmt->fetchAll();

    if (empty($fetch))
    {
        exit('[]');
    }

    $build = array();
    foreach($fetch as $row)
    {
        // m = thread marker in icons
        // s = don't empty the subject line
        $subArr = array('m' => true, 's' => false);
        foreach(array('thread' => 'p') as $key => $key_min)
        {
            if(is_null($row[$key]))
            {
                continue;
            }

            $subArr[$key_min] = $row[$key];
        }
        array_push($build, $subArr);
    }
}
else
{
    $stmt = $pdo->prepare("SELECT board, post, name, color, hue, trip, subject, email FROM data WHERE board=:board AND thread=:thread");
    $stmt->bindValue(':board', $board, PDO::PARAM_STR);
    $stmt->bindValue(':thread', $thread, PDO::PARAM_INT);
    $stmt->execute();
    $fetch = $stmt->fetchAll();

    if (empty($fetch))
    {
        exit('[]');
    }

    $build = array();
    foreach($fetch as $row)
    {
        $subArr = array();
        foreach(array('board' => 'b', 'post' => 'p', 'name' => 'n', 'color' => 'ca', 'hue' => 'ch', 'trip' => 't', 'subject'=> 's', 'email' => 'e') as $key => $key_min)
        {
            if(is_null($row[$key]))
            {
                continue;
            }

            $subArr[$key_min] = $row[$key];
        }
        array_push($build, $subArr);
    }
}

echo json_encode($build);