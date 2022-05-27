<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: https://boards.4chan.org');
header('Access-Control-Allow-Headers: x-requested-with, if-modified-since');

$method     = filter_input(INPUT_SERVER, 'REQUEST_METHOD') == 'GET';
$origin     = filter_input(INPUT_SERVER, 'HTTP_ORIGIN') == 'https://boards.4chan.org';
$request    = substr(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH'), 0, 8) == 'NameSync';

if (!$method || !$origin || !$request) exit;

require_once('require/config.php');
require_once('require/FileCacher.php');
require_once('require/inc.func.php');

$cache = new FileCacher(CACHE_FOLDER);

$board  = filter_input(INPUT_GET, 'b', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^(' . $cache->get('boards', 'b') . ')$/')));
$thread = filter_input(INPUT_GET, 't', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => PHP_INT_MAX)));
$ip     = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);

if (!$board)      exit_error('Invalid Board');
if (!$thread)     exit_error('Invalid Thread');
if (is_null($ip)) exit_error('Invalid IP');

$floodID = "$board-qp-" . md5($ip);
if (is_flooding($floodID, GET_MAX_HITS, GET_TIME))
{
    http_response_code(429);
    exit('[]');
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4;dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $ex) {
    echo 'Database error!';
    throw new Exception($ex->getMessage());
}

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
            // Do not sent empty stuff
            //$subArr[$key_min] = '';
            continue;
        }

        $subArr[$key_min] = $row[$key];
    }
    array_push($build, $subArr);
}

echo json_encode($build);