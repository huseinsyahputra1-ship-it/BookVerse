<?php

session_start();

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

    $_SESSION['cart'][$book_id]['quantity']++;

} elseif ($action === 'decrease') {

    $_SESSION['cart'][$book_id]['quantity']--;

    /*
    | Jika quantity menjadi 0,
    | hapus buku dari cart.
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