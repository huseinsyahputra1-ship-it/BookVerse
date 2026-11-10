
<?php

// ==========================================
// BOOKVERSE ADMIN SESSION
// ==========================================

session_name("BOOKVERSE_ADMIN");

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/BookVerse/admin',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

?>
