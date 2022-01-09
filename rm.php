<?php
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: https://boards.4chan.org');
header('Access-Control-Allow-Headers: x-requested-with, if-modified-since');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once('require/config.php');
require_once('require/rate_limiter.php');

$ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);

if (is_null($ip)) exit('Invalid IP');

if (!check_within_rate_limit('rm', $ip, REMOVE_MAX_HITS, REMOVE_TIME, 1))
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

// Replace with Anon instead?
$stmt = $pdo->prepare("DELETE FROM data WHERE ip=:ip");
$stmt->bindValue(':ip', md5($ip), PDO::PARAM_STR);
$stmt->execute();