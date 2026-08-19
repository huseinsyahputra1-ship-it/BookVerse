<?php

session_start();

include "../config/database.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    echo "<script>
        alert('Silakan login terlebih dahulu!');
        window.location='../login.php';
    </script>";

    exit();

}

if ($_SESSION['role'] !== 'admin') {

    echo "<script>
        alert('Akses hanya untuk admin!');
        window.location='../index.php';
    </script>";

    exit();

}

/*
|--------------------------------------------------------------------------
| GET BOOK ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    echo "<script>
        alert('ID buku tidak valid!');
        window.location='books.php';
    </script>";

    exit();

}

$bookId = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| GET BOOK DATA
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT image
     FROM books
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $bookId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    echo "<script>
        alert('Buku tidak ditemukan!');
        window.location='books.php';
    </script>";

    exit();

}

$book = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| CHECK ORDER ITEMS
|--------------------------------------------------------------------------
|
| Buku yang sudah pernah masuk pesanan tidak boleh dihapus
| agar riwayat transaksi tetap aman.
|
*/

$orderStmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM order_items
     WHERE book_id = ?"
);

mysqli_stmt_bind_param(
    $orderStmt,
    "i",
    $bookId
);

mysqli_stmt_execute($orderStmt);

$orderResult = mysqli_stmt_get_result($orderStmt);

$orderData = mysqli_fetch_assoc($orderResult);

mysqli_stmt_close($orderStmt);

if ($orderData['total'] > 0) {

    echo "<script>
        alert('Buku tidak dapat dihapus karena sudah digunakan dalam riwayat pesanan!');
        window.location='books.php';
    </script>";

    exit();

}

/*
|--------------------------------------------------------------------------
| DELETE BOOK FROM DATABASE
|--------------------------------------------------------------------------
*/

$deleteStmt = mysqli_prepare(
    $conn,
    "DELETE FROM books
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $deleteStmt,
    "i",
    $bookId
);

if (mysqli_stmt_execute($deleteStmt)) {

    /*
    |--------------------------------------------------------------------------
    | DELETE BOOK COVER
    |--------------------------------------------------------------------------
    */

    if (
        !empty($book['image']) &&
        file_exists("../" . $book['image'])
    ) {

        unlink("../" . $book['image']);

    }

    mysqli_stmt_close($deleteStmt);

    echo "<script>
        alert('Buku berhasil dihapus!');
        window.location='books.php';
    </script>";

    exit();

}

/*
|--------------------------------------------------------------------------
| DELETE FAILED
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($deleteStmt);

echo "<script>
    alert('Gagal menghapus buku!');
    window.location='books.php';
</script>";

exit();

?>