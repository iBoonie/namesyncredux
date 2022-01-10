<?php
require_once('config.php');
require_once('FileCacher.php');
require_once('inc.func.php');

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4;dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $ex) {
    throw new Exception($ex->getMessage());
}

$cache = new FileCacher(CACHE_FOLDER);

$boards = $cache->get('boards', 'b'); // Default to /b/ if file doesnt exist
$boards = explode('|', $boards);

foreach ($boards as $board)
{
    $file = curl_url("https://a.4cdn.org/$board/1.json");

    $post = null;
    // There wont be more than 5 stickies... right?
    for ($i = 0; $i <= 5; $i++)
    {
        // Skip if sticky
        if (isset($file->threads[$i]->posts[0]->sticky))
        {
            continue;
        }

        $postno = end($file->threads[$i]->posts)->no;
        if (!empty($postno))
        {
            $post = $postno;
            break;
        }
    }

    if (!is_null($post))
    {
        $stmt = $pdo->prepare("REPLACE INTO board_data (board, post, time) VALUES (:board, :post, :time)");
        $stmt->bindValue(':board', $board, PDO::PARAM_STR);
        $stmt->bindValue(':post', $post, PDO::PARAM_INT);
        $stmt->bindValue(':time', time(), PDO::PARAM_INT);
        $stmt->execute();
    } else {
        error_log('Failed updating board data');
    }
}