<?php

session_start();

include "config/database.php";

/*
|--------------------------------------------------------------------------
| Validasi Cart
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/*
|--------------------------------------------------------------------------
| Validasi Parameter
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id']) ||
    !isset($_GET['action'])
) {
    header("Location: cart.php");
    exit();
}

$book_id = (int) $_GET['id'];
$action = $_GET['action'];

/*
|--------------------------------------------------------------------------
| Pastikan Buku Ada di Cart
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['cart'][$book_id])) {
    header("Location: cart.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Update Quantity
|--------------------------------------------------------------------------
*/

if ($action === 'increase') {

    /*
    |--------------------------------------------------------------------------
    | Ambil Stok Terbaru dari Database
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT stock FROM books WHERE id = ? LIMIT 1"
    );

    if (!$stmt) {
        header("Location: cart.php");
        exit();
    }

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
    | Cek Stok Sebelum Menambah Quantity
    |--------------------------------------------------------------------------
    */

    if (!$book) {
        header("Location: cart.php");
        exit();
    }

    $stock = (int) $book['stock'];

    $current_quantity = (int) $_SESSION['cart'][$book_id]['quantity'];

    if (
        $stock > 0 &&
        $current_quantity < $stock
    ) {
        $_SESSION['cart'][$book_id]['quantity']++;
    }

} elseif ($action === 'decrease') {

    $_SESSION['cart'][$book_id]['quantity']--;

    /*
    |--------------------------------------------------------------------------
    | Jika quantity menjadi 0,
    | hapus buku dari cart.
    |--------------------------------------------------------------------------
    */

    if ($_SESSION['cart'][$book_id]['quantity'] <= 0) {
        unset($_SESSION['cart'][$book_id]);
    }

}

/*
|--------------------------------------------------------------------------
| Kembali ke Cart
|--------------------------------------------------------------------------
*/

header("Location: cart.php");
exit();

?>