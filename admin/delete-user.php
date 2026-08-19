<?php

session_start();

include "../config/database.php";

// =========================================================
// ADMIN ACCESS
// =========================================================

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");

    exit();

}

if ($_SESSION['role'] !== 'admin') {

    echo "<script>
        alert('Akses ditolak! Halaman ini hanya untuk admin.');
        window.location='../index.php';
    </script>";

    exit();

}

// =========================================================
// GET USER ID
// =========================================================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    echo "<script>
        alert('ID user tidak valid!');
        window.location='users.php';
    </script>";

    exit();

}

$userId = (int) $_GET['id'];

// =========================================================
// PREVENT SELF DELETE
// =========================================================

if ($userId === (int) $_SESSION['user_id']) {

    echo "<script>
        alert('Anda tidak dapat menghapus akun yang sedang digunakan!');
        window.location='users.php';
    </script>";

    exit();

}

// =========================================================
// CHECK USER
// =========================================================

$userStmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        fullname,
        role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $userStmt,
    "i",
    $userId
);

mysqli_stmt_execute($userStmt);

$userResult = mysqli_stmt_get_result($userStmt);

if (mysqli_num_rows($userResult) === 0) {

    mysqli_stmt_close($userStmt);

    echo "<script>
        alert('User tidak ditemukan!');
        window.location='users.php';
    </script>";

    exit();

}

$user = mysqli_fetch_assoc($userResult);

mysqli_stmt_close($userStmt);

// =========================================================
// CHECK USER ORDERS
// =========================================================

$orderStmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total_orders
     FROM orders
     WHERE user_id = ?"
);

mysqli_stmt_bind_param(
    $orderStmt,
    "i",
    $userId
);

mysqli_stmt_execute($orderStmt);

$orderResult = mysqli_stmt_get_result($orderStmt);

$orderData = mysqli_fetch_assoc($orderResult);

mysqli_stmt_close($orderStmt);

// =========================================================
// PREVENT DELETE IF USER HAS ORDERS
// =========================================================

if ((int) $orderData['total_orders'] > 0) {

    echo "<script>
        alert('User tidak dapat dihapus karena memiliki riwayat pesanan!');
        window.location='users.php';
    </script>";

    exit();

}

// =========================================================
// DELETE USER
// =========================================================

$deleteStmt = mysqli_prepare(
    $conn,
    "DELETE FROM users
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $deleteStmt,
    "i",
    $userId
);

if (mysqli_stmt_execute($deleteStmt)) {

    mysqli_stmt_close($deleteStmt);

    echo "<script>
        alert('User berhasil dihapus!');
        window.location='users.php';
    </script>";

    exit();

}

mysqli_stmt_close($deleteStmt);

echo "<script>
    alert('Gagal menghapus user!');
    window.location='users.php';
</script>";

exit();

?>