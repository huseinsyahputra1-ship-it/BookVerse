<?php

session_start();

include "config/database.php";

/*
|--------------------------------------------------------------------------
| Pastikan User Sudah Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil Data Cart
|--------------------------------------------------------------------------
*/

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Hitung Total
|--------------------------------------------------------------------------
*/

$total = 0;

foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

/*
|--------------------------------------------------------------------------
| Data User
|--------------------------------------------------------------------------
*/

$fullname = $_SESSION['fullname'] ?? '';
$email = $_SESSION['email'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Checkout - BookVerse</title>

    <!-- Google Font -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="header">

        <div class="container">

            <div class="navbar">

                <!-- Logo -->

                <div class="logo">

                    <img
                        src="assets/img/hugeicons_book-open-02.png"
                        alt="BookVerse">

                    <div class="logo-text">

                        <h2>
                            BookVerse
                        </h2>

                        <p>
                            Every Book Has a Story
                        </p>

                    </div>

                </div>

                <!-- Menu -->

                <nav>

                    <ul class="menu">

                        <li>
                            <a href="index.php">
                                Home
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                Books
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                Categories
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                About
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                Contact
                            </a>
                        </li>

                    </ul>

                </nav>

                <!-- Right Menu -->

                <div class="right-menu">

                    <div class="search-box">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Search Books...">

                    </div>

                    <button
                        type="button"
                        class="icon-btn">

                        <i class="fa-regular fa-heart"></i>

                    </button>

                    <a
                        href="cart.php"
                        class="icon-btn">

                        <i class="fa-solid fa-cart-shopping"></i>

                    </a>

                    <!-- Profile -->

                    <div class="profile-menu">

                        <button
                            type="button"
                            class="profile-btn"
                            id="profileBtn">

                            <i class="fa-solid fa-user"></i>

                            <span>
                                <?php echo htmlspecialchars($fullname); ?>
                            </span>

                            <i class="fa-solid fa-chevron-down"></i>

                        </button>

                        <div
                            class="profile-dropdown"
                            id="profileDropdown">

                            <div class="profile-header">

                                <i class="fa-solid fa-circle-user"></i>

                                <div>

                                    <strong>
                                        <?php echo htmlspecialchars($fullname); ?>
                                    </strong>

                                    <small>
                                        <?php echo htmlspecialchars($email); ?>
                                    </small>

                                </div>

                            </div>

                            <hr>

                            <a href="profile.php">

                                <i class="fa-solid fa-user"></i>

                                <span>
                                    Profile
                                </span>

                            </a>

                            <a href="orders.php">

                                <i class="fa-solid fa-box"></i>

                                <span>
                                    Pesanan Saya
                                </span>

                            </a>

                            <hr>

                            <a
                                href="logout.php"
                                class="logout-link">

                                <i class="fa-solid fa-right-from-bracket"></i>

                                <span>
                                    Logout
                                </span>

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </header>

    <!-- ================= CHECKOUT ================= -->

    <main class="checkout-page">

        <div class="container">

            <!-- Checkout Header -->

            <div class="checkout-page-header">

                <div class="section-title">

                    <h2>
                        Checkout
                    </h2>

                    <p>
                        Complete your order and payment details.
                    </p>

                </div>

                <a
                    href="cart.php"
                    class="cart-back-btn">

                    <i class="fa-solid fa-arrow-left"></i>

                    Kembali ke Cart

                </a>

            </div>

            <!-- ================= CHECKOUT FORM ================= -->

            <form
                action="payment.php"
                method="POST">

                <!-- Checkout Layout -->

                <div class="checkout-layout">

                    <!-- ================= CUSTOMER INFORMATION ================= -->

                    <div class="checkout-main">

                        <div class="checkout-card">

                            <div class="checkout-card-header">

                                <div>

                                    <span class="checkout-step">
                                        01
                                    </span>

                                    <div>

                                        <h3>
                                            Informasi Pengiriman
                                        </h3>

                                        <p>
                                            Masukkan data penerima pesanan.
                                        </p>

                                    </div>

                                </div>

                            </div>

                            <div class="checkout-form">

                                <div class="form-group">

                                    <label for="recipient_name">
                                        Nama Penerima
                                    </label>

                                    <input
                                        type="text"
                                        id="recipient_name"
                                        name="recipient_name"
                                        value="<?php echo htmlspecialchars($fullname); ?>"
                                        placeholder="Masukkan nama penerima"
                                        required>

                                </div>

                                <div class="form-group">

                                    <label for="phone">
                                        Nomor Telepon
                                    </label>

                                    <input
                                        type="text"
                                        id="phone"
                                        name="phone"
                                        placeholder="Contoh: 081234567890"
                                        required>

                                </div>

                                <div class="form-group">

                                    <label for="address">
                                        Alamat Lengkap
                                    </label>

                                    <textarea
                                        id="address"
                                        name="address"
                                        rows="5"
                                        placeholder="Masukkan alamat lengkap pengiriman"
                                        required></textarea>

                                </div>

                            </div>

                        </div>

                        <!-- ================= PAYMENT METHOD ================= -->

                        <div class="checkout-card">

                            <div class="checkout-card-header">

                                <div>

                                    <span class="checkout-step">
                                        02
                                    </span>

                                    <div>

                                        <h3>
                                            Metode Pembayaran
                                        </h3>

                                        <p>
                                            Pilih metode pembayaran untuk pesanan.
                                        </p>

                                    </div>

                                </div>

                            </div>

                            <div class="payment-methods">

                                <label class="payment-option">

                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="bank_transfer"
                                        checked>

                                    <div class="payment-icon">

                                        <i class="fa-solid fa-building-columns"></i>

                                    </div>

                                    <div class="payment-info">

                                        <strong>
                                            Bank Transfer
                                        </strong>

                                        <span>
                                            Pembayaran simulasi
                                        </span>

                                    </div>

                                    <i class="fa-solid fa-circle-check payment-check"></i>

                                </label>

                                <label class="payment-option">

                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="e_wallet">

                                    <div class="payment-icon">

                                        <i class="fa-solid fa-wallet"></i>

                                    </div>

                                    <div class="payment-info">

                                        <strong>
                                            E-Wallet
                                        </strong>

                                        <span>
                                            Pembayaran simulasi
                                        </span>

                                    </div>

                                    <i class="fa-solid fa-circle-check payment-check"></i>

                                </label>

                                <label class="payment-option">

                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="cod">

                                    <div class="payment-icon">

                                        <i class="fa-solid fa-truck"></i>

                                    </div>

                                    <div class="payment-info">

                                        <strong>
                                            Cash on Delivery
                                        </strong>

                                        <span>
                                            Bayar saat pesanan diterima
                                        </span>

                                    </div>

                                    <i class="fa-solid fa-circle-check payment-check"></i>

                                </label>

                            </div>

                        </div>

                    </div>

                    <!-- ================= ORDER SUMMARY ================= -->

                    <aside class="checkout-summary">

                        <div class="checkout-summary-card">

                            <h3>
                                Order Summary
                            </h3>

                            <div class="checkout-items">

                                <?php foreach ($cart as $item): ?>

                                    <?php
                                    $subtotal =
                                        $item['price'] * $item['quantity'];
                                    ?>

                                    <div class="checkout-item">

                                        <div class="checkout-item-image">

                                            <img
                                                src="<?php echo htmlspecialchars($item['image']); ?>"
                                                alt="<?php echo htmlspecialchars($item['title']); ?>">

                                        </div>

                                        <div class="checkout-item-info">

                                            <h4>
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </h4>

                                            <span>
                                                <?php echo $item['quantity']; ?> ×
                                                Rp<?php echo number_format(
                                                    $item['price'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ); ?>
                                            </span>

                                        </div>

                                        <strong>

                                            Rp<?php echo number_format(
                                                $subtotal,
                                                0,
                                                ',',
                                                '.'
                                            ); ?>

                                        </strong>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                            <hr>

                            <div class="summary-row">

                                <span>
                                    Subtotal
                                </span>

                                <strong>

                                    Rp<?php echo number_format(
                                        $total,
                                        0,
                                        ',',
                                        '.'
                                    ); ?>

                                </strong>

                            </div>

                            <div class="summary-row">

                                <span>
                                    Delivery
                                </span>

                                <strong>
                                    Free
                                </strong>

                            </div>

                            <hr>

                            <div class="summary-total">

                                <span>
                                    Total
                                </span>

                                <strong>

                                    Rp<?php echo number_format(
                                        $total,
                                        0,
                                        ',',
                                        '.'
                                    ); ?>

                                </strong>

                            </div>

                            <button
                                type="submit"
                                class="checkout-submit-btn">

                                <i class="fa-solid fa-lock"></i>

                                Lanjut ke Pembayaran

                            </button>

                            <p class="checkout-note">

                                <i class="fa-solid fa-shield-halved"></i>

                                Ini adalah sistem pembayaran simulasi
                                untuk kebutuhan pembelajaran.

                            </p>

                        </div>

                    </aside>

                </div>

            </form>

        </div>

    </main>

    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        <div class="container">

            <div class="footer-content">

                <div class="footer-logo">

                    <img
                        src="assets/img/hugeicons_book-open-02.png"
                        alt="BookVerse">

                    <h2>
                        BookVerse
                    </h2>

                    <p>
                        Every Book Has a Story.
                        Discover books that inspire your journey.
                    </p>

                </div>

                <div class="footer-links">

                    <h3>
                        Quick Links
                    </h3>

                    <ul>

                        <li>
                            <a href="index.php">
                                Home
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                Books
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                Categories
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                About
                            </a>
                        </li>

                    </ul>

                </div>

                <div class="footer-contact">

                    <h3>
                        Contact
                    </h3>

                    <p>
                        Email : info@bookverse.com
                    </p>

                    <p>
                        Phone : +62 812 3456 7890
                    </p>

                    <p>
                        Jakarta, Indonesia
                    </p>

                </div>

            </div>

            <div class="footer-bottom">

                <p>
                    © 2026 BookVerse. All Rights Reserved.
                </p>

            </div>

        </div>

    </footer>

    <script src="assets/js/script.js"></script>

</body>

</html>