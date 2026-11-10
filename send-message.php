<?php

require_once __DIR__ . '/config/user-session.php';

require_once "config/database.php";

// Pastikan form dikirim menggunakan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contact.php");
    exit();
}

// Ambil dan bersihkan data form
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$message = trim($_POST["message"] ?? "");

// Validasi data
if (
    $name === "" ||
    $email === "" ||
    $subject === "" ||
    $message === ""
) {
    header("Location: contact.php?status=empty");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: contact.php?status=invalid_email");
    exit();
}

// Simpan pesan menggunakan prepared statement
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO messages (name, email, subject, message)
     VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    header("Location: contact.php?status=error");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $name,
    $email,
    $subject,
    $message
);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: contact.php?status=success");
    exit();
}

mysqli_stmt_close($stmt);

header("Location: contact.php?status=error");
exit();

?>