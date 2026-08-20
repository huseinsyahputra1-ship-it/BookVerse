<?php

session_start();

include "config/database.php";

/*
|--------------------------------------------------------------------------
| Validasi ID Buku
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$book_id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| Ambil Data Buku
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, title, author, price, image
     FROM books
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $book_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$book = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| Jika Buku Tidak Ditemukan
|--------------------------------------------------------------------------
*/

if (!$book) {
    header("Location: index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Buat Session Cart Jika Belum Ada
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/*
|--------------------------------------------------------------------------
| Tambahkan Buku ke Cart
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['cart'][$book_id])) {

    $_SESSION['cart'][$book_id]['quantity']++;

} else {

    $_SESSION['cart'][$book_id] = [
        'id'       => $book['id'],
        'title'    => $book['title'],
        'author'   => $book['author'],
        'price'    => $book['price'],
        'image'    => $book['image'],
        'quantity' => 1
    ];

}

/*
|--------------------------------------------------------------------------
| Redirect Setelah Berhasil Menambahkan Cart
|--------------------------------------------------------------------------
*/

$redirect = $_GET['redirect'] ?? 'home';

if ($redirect === 'detail') {

    header(
        "Location: book-detail.php?id=" .
        $book_id .
        "&added=success"
    );

    exit();
}

header("Location: index.php?added=success");
exit();