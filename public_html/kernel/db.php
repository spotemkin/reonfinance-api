<?php
// Check if the function 'hi' does not exist
if (!function_exists('hi')) {
    // Redirect to the referring URL if available, otherwise redirect to index.php
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: index.php");
    }
}

try {
    // Attempt to create a new PDO database connection
    $db = new PDO('mysql:host=' . $dbhost . ';dbname=' . $dbname . ';charset=utf8mb4', $dbuser, $dbpass);
} catch (PDOException $e) {
    // If connection fails, return a JSON error message
    $params['error'] = 'DB_CONNECTION_ERROR';
    die(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
