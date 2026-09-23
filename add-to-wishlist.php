<?php

session_start();

include "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$bookId = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| CHECK BOOK
|--------------------------------------------------------------------------
*/

$bookStmt = mysqli_prepare(
    $conn,
    "SELECT id
     FROM books
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $bookStmt,
    "i",
    $bookId
);

mysqli_stmt_execute($bookStmt);

$bookResult = mysqli_stmt_get_result($bookStmt);

if (mysqli_num_rows($bookResult) === 0) {

    mysqli_stmt_close($bookStmt);

    header("Location: index.php");
    exit();
}

mysqli_stmt_close($bookStmt);

/*
|--------------------------------------------------------------------------
| CHECK WISHLIST
|--------------------------------------------------------------------------
*/

$checkStmt = mysqli_prepare(
    $conn,
    "SELECT id
     FROM wishlist
     WHERE user_id = ?
     AND book_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkStmt,
    "ii",
    $userId,
    $bookId
);

mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);

$alreadyExists = mysqli_num_rows($checkResult) > 0;

mysqli_stmt_close($checkStmt);

/*
|--------------------------------------------------------------------------
| ADD TO WISHLIST
|--------------------------------------------------------------------------
*/

if (!$alreadyExists) {

    $insertStmt = mysqli_prepare(
        $conn,
        "INSERT INTO wishlist (user_id, book_id)
         VALUES (?, ?)"
    );

    mysqli_stmt_bind_param(
        $insertStmt,
        "ii",
        $userId,
        $bookId
    );

    mysqli_stmt_execute($insertStmt);

    mysqli_stmt_close($insertStmt);
}

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if (strpos($redirect, '?') !== false)
    {
        $redirect .= '&added=wishlist';
    } else{
        $redirect .= '?added=wishlist';
    }

header("Location: " . $redirect);
exit();