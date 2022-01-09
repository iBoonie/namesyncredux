<?php
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: https://boards.4chan.org');
header('Access-Control-Allow-Headers: x-requested-with, if-modified-since');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('[]');

require_once('require/config.php');
require_once('require/rate_limiter.php');
require_once('require/func.cache.php');

$board   = filter_input(INPUT_POST, 'b', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^(' . get_cache('require/board.cache') . ')$/')));
$post    = filter_input(INPUT_POST, 'p', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => PHP_INT_MAX)));
$thread  = filter_input(INPUT_POST, 't', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => PHP_INT_MAX)));
$name    = filter_input(INPUT_POST, 'n', FILTER_CALLBACK, array('options' => 'validate_strings'));
$subject = filter_input(INPUT_POST, 's', FILTER_CALLBACK, array('options' => 'validate_strings'));
$email   = filter_input(INPUT_POST, 'e', FILTER_CALLBACK, array('options' => 'validate_strings'));
$ip      = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);

if (!$board)        exit_error('Invalid Board');
if (!$post)         exit_error('Invalid Post');
if (!$thread)       exit_error('Invalid Thread');
if (is_null($name)) exit_error('Invalid Name');
if (is_null($ip))   exit_error('Invalid IP');

if (!check_within_rate_limit($board, $ip, SUBMIT_MAX_HITS, SUBMIT_TIME, 1))
{
    error_log("FloodProtection " . $board);
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

$stmt = $pdo->prepare("SELECT post, time FROM board_data WHERE board=:board");
$stmt->bindValue(':board', $board, PDO::PARAM_STR);
$stmt->execute();
$fetch = $stmt->fetch(PDO::FETCH_OBJ);

if (!empty($fetch) && $fetch->time > 0)
{
    // Dont allow people to add stuff to far back
    if ($post < ($fetch->post - 10))
    {
        exit_error('Invalid post range');
    }
}

$stmt = $pdo->prepare("
    INSERT IGNORE INTO data (board, post, thread, name, trip, subject, email, ip, uid, time)
    VALUES (:board, :post, :thread, :name, :trip, :subject, :email, :ip, :uid, :time)
");

list($name, $trip) = trip($name);
$uid = crc32($board . $thread . $post);
$stmt->bindValue(':board', $board, PDO::PARAM_STR);
$stmt->bindValue(':post', $post, PDO::PARAM_INT);
$stmt->bindValue(':thread', $thread, PDO::PARAM_INT);
$stmt->bindValue(':name', $name, PDO::PARAM_STR);
$stmt->bindValue(':trip', $trip, PDO::PARAM_STR);
$stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
$stmt->bindValue(':email', $email, PDO::PARAM_STR);
$stmt->bindValue(':ip', md5($ip), PDO::PARAM_STR);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->bindValue(':time', time(), PDO::PARAM_INT);
$stmt->execute();

function exit_error($output)
{
    http_response_code(406);
    exit($output);
}

function validate_strings($str)
{
    $str = htmlspecialchars($str);

    if (strlen($str) == 0)
    {
        return null;
    }

    return $str;
}

function trip($name)
{
    // Return name if non-valid trip
    if (!preg_match('/^([^#]+)?(##|#)(.+)$/', $name, $match))
    {
        return array($name, null);
    }

    $name = $match[1];
    $secure = $match[2];
    $trip = $match[3];

    if (strcmp($secure, '##') == 0)
    {
        // This will never be a 1:1 with 4chan, so whatever
        $salt = md5($name . SECURE_TRIP_SALT . $trip);
        $trip = '!!' . substr(crypt($trip, $salt), -10);
    } else {
        // UTF-8 > SJIS
        $trip = mb_convert_encoding($trip, 'Shift_JIS', 'UTF-8');
        $salt = substr($trip . 'H..', 1, 2);
        $salt = preg_replace('/[^.-z]/', '.', $salt);
        $salt = strtr($salt, ':;<=>?@[\]^_`', 'ABCDEFGabcdef');
        $trip = '!' . substr(crypt($trip, $salt), -10);
    }

    return array($name, $trip);
}