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
    "SELECT id, title, author, price, image, stock
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
| Ambil Stok Buku
|--------------------------------------------------------------------------
*/

$stock = (int) $book['stock'];

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
| Buy Now
|--------------------------------------------------------------------------
*/

$buy_now = isset($_GET['buy_now']) && $_GET['buy_now'] === '1';

if ($buy_now) {

    // Cek stok sebelum Buy Now
    if ($stock <= 0) {
        header("Location: book-detail.php?id=" . $book_id . "&stock=empty");
        exit();
    }

    // Kosongkan cart sebelumnya
    $_SESSION['cart'] = [];

    // Masukkan buku yang dipilih
    $_SESSION['cart'][$book_id] = [
        'id'       => $book['id'],
        'title'    => $book['title'],
        'author'   => $book['author'],
        'price'    => $book['price'],
        'image'    => $book['image'],
        'quantity' => 1
    ];

    // Langsung menuju checkout
    header("Location: checkout.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Tambahkan Buku ke Cart
|--------------------------------------------------------------------------
*/

// Ambil jumlah buku yang sudah ada di cart
$current_quantity = isset($_SESSION['cart'][$book_id])
    ? (int) $_SESSION['cart'][$book_id]['quantity']
    : 0;

// Cek stok habis
if ($stock <= 0) {
    header("Location: book-detail.php?id=" . $book_id . "&stock=empty");
    exit();
}

// Cek agar jumlah cart tidak melebihi stok
if (($current_quantity + 1) > $stock) {
    header("Location: book-detail.php?id=" . $book_id . "&stock=limit");
    exit();
}

// Tambahkan jumlah jika buku sudah ada di cart
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

?>