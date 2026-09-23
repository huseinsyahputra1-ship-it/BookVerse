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

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM wishlist
     WHERE user_id = ?
     AND book_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $userId,
    $bookId
);

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';

header("Location: " . $redirect);
exit();