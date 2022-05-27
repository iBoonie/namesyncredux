<?php
require_once('require/config.php');
require_once('require/FileCacher.php');
require_once('require/inc.func.php');

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: https://boards.4chan.org');
header('Access-Control-Allow-Headers: x-requested-with, if-modified-since');

// Frensync uses this to see if the api is up
if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'OPTIONS') exit('[]');

$ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);

if (is_null($ip))   exit_error('Invalid IP');

$floodID = 'rm-' . md5($ip);
if (is_flooding($floodID, REMOVE_MAX_HITS, REMOVE_TIME))
{
    exit(http_response_code(429));
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4;dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $ex) {
    throw new Exception($ex->getMessage());
}

// Replace with Anon instead?
$stmt = $pdo->prepare("DELETE FROM data WHERE ip=:ip");
$stmt->bindValue(':ip', md5($ip), PDO::PARAM_STR);
$stmt->execute();