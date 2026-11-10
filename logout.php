<?php

require_once __DIR__ . '/config/user-session.php';

$params = session_get_cookie_params();
$sessionName = session_name();

$_SESSION = [];
session_destroy();

setcookie($sessionName, '', [
    'expires' => time() - 42000,
    'path' => $params['path'],
    'domain' => $params['domain'],
    'secure' => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => $params['samesite'] ?? 'Lax'
]);

header("Location: login.php");
exit();

?>