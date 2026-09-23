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
| Ambil Cart
|--------------------------------------------------------------------------
*/

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Hitung Total Dari Session Cart
|--------------------------------------------------------------------------
*/

$total = 0;

foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

/*
|--------------------------------------------------------------------------
| Ambil Data Checkout
|--------------------------------------------------------------------------
*/

$recipient_name = $_POST['recipient_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'bank_transfer';

/*
|--------------------------------------------------------------------------
| Validasi Metode Pembayaran
|--------------------------------------------------------------------------
*/

$allowed_payment_methods = [
    'bank_transfer',
    'e_wallet',
    'cod'
];

if (!in_array($payment_method, $allowed_payment_methods, true)) {
    $payment_method = 'bank_transfer';
}

/*
|--------------------------------------------------------------------------
| Simpan Data Checkout ke Session
|--------------------------------------------------------------------------
|
| Data ini akan digunakan pada tahap pembuatan order.
|
*/

$_SESSION['checkout'] = [
    'recipient_name' => trim($recipient_name),
    'phone'          => trim($phone),
    'address'        => trim($address),
    'payment_method' => $payment_method,
    'total'          => $total
];

/*
|--------------------------------------------------------------------------
| Informasi Payment Method
|--------------------------------------------------------------------------
*/

$payment_details = [
    'bank_transfer' => [
        'name' => 'Bank Transfer',
        'icon' => 'fa-building-columns',
        'description' => 'Simulated bank transfer payment'
    ],
    'e_wallet' => [
        'name' => 'E-Wallet',
        'icon' => 'fa-wallet',
        'description' => 'Simulated digital wallet payment'
    ],
    'cod' => [
        'name' => 'Cash on Delivery',
        'icon' => 'fa-truck',
        'description' => 'Pay when your order arrives'
    ]
];

$current_payment = $payment_details[$payment_method];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Payment - BookVerse</title>

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

                    <?php if (isset($_SESSION['user_id'])): ?>

                        <div class="profile-menu">

                            <button
                                type="button"
                                class="profile-btn"
                                id="profileBtn">

                                <i class="fa-solid fa-user"></i>

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $_SESSION['fullname']
                                    );
                                    ?>
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
                                            <?php
                                            echo htmlspecialchars(
                                                $_SESSION['fullname']
                                            );
                                            ?>
                                        </strong>

                                        <small>
                                            <?php
                                            echo htmlspecialchars(
                                                $_SESSION['email']
                                            );
                                            ?>
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

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </header>

    <!-- ================= PAYMENT ================= -->

    <main class="payment-page">

        <div class="container">

            <!-- Payment Header -->

            <div class="payment-page-header">

                <div>

                    <span class="payment-badge">

                        <i class="fa-solid fa-shield-halved"></i>

                        Secure Checkout

                    </span>

                    <h1>
                        Complete Your Payment
                    </h1>

                    <p>
                        Review your payment details and complete
                        your simulated transaction.
                    </p>

                </div>

                <a
                    href="checkout.php"
                    class="payment-back-btn">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Checkout

                </a>

            </div>

            <!-- Simulation Notice -->

            <div class="payment-simulation-notice">

                <div class="payment-notice-icon">

                    <i class="fa-solid fa-graduation-cap"></i>

                </div>

                <div>

                    <strong>
                        Payment Simulation
                    </strong>

                    <p>
                        This is a simulated payment system created
                        for educational purposes. No real money will
                        be transferred.
                    </p>

                </div>

            </div>

            <!-- Payment Layout -->

            <div class="payment-layout">

                <!-- ================= PAYMENT FORM ================= -->

                <section class="payment-main">

                    <div class="payment-card">

                        <div class="payment-card-header">

                            <div>

                                <span class="payment-step">
                                    01
                                </span>

                                <div>

                                    <h2>
                                        Payment Method
                                    </h2>

                                    <p>
                                        Your selected payment method.
                                    </p>

                                </div>

                            </div>

                        </div>

                        <div class="selected-payment">

                            <div class="selected-payment-icon">

                                <i class="fa-solid <?php echo $current_payment['icon']; ?>"></i>

                            </div>

                            <div class="selected-payment-info">

                                <strong>
                                    <?php echo $current_payment['name']; ?>
                                </strong>

                                <span>
                                    <?php echo $current_payment['description']; ?>
                                </span>

                            </div>

                            <i class="fa-solid fa-circle-check"></i>

                        </div>

                    </div>

                    <!-- Payment Details -->

                    <div class="payment-card">

                        <div class="payment-card-header">

                            <div>

                                <span class="payment-step">
                                    02
                                </span>

                                <div>

                                    <h2>
                                        Payment Details
                                    </h2>

                                    <p>
                                        Enter simulated payment information.
                                    </p>

                                </div>

                            </div>

                        </div>

                        <?php if ($payment_method === 'bank_transfer'): ?>

                            <div class="fake-payment-box">

                                <div class="fake-bank-header">

                                    <div>

                                        <span>
                                            BookVerse Virtual Account
                                        </span>

                                        <strong>
                                            BOOKVERSE
                                        </strong>

                                    </div>

                                    <i class="fa-solid fa-building-columns"></i>

                                </div>

                                <div class="fake-account-number">

                                    <span>
                                        Virtual Account Number
                                    </span>

                                    <strong>
                                        8800 2026 0018 4592
                                    </strong>

                                </div>

                                <div class="fake-payment-row">

                                    <span>
                                        Account Name
                                    </span>

                                    <strong>
                                        BOOKVERSE STORE
                                    </strong>

                                </div>

                            </div>

                            <div class="payment-input-group">

                                <label for="transfer_name">
                                    Account Holder Name
                                </label>

                                <input
                                    type="text"
                                    id="transfer_name"
                                    value="<?php echo htmlspecialchars($recipient_name); ?>"
                                    placeholder="Enter account holder name">

                            </div>

                        <?php elseif ($payment_method === 'e_wallet'): ?>

                            <div class="fake-payment-box ewallet-box">

                                <div class="ewallet-icon">

                                    <i class="fa-solid fa-wallet"></i>

                                </div>

                                <strong>
                                    BookVerse E-Wallet
                                </strong>

                                <span>
                                    Simulated digital wallet payment
                                </span>

                                <div class="fake-ewallet-number">
                                    0812 3456 7890
                                </div>

                            </div>

                            <div class="payment-input-group">

                                <label for="wallet_phone">
                                    E-Wallet Number
                                </label>

                                <input
                                    type="text"
                                    id="wallet_phone"
                                    value="<?php echo htmlspecialchars($phone); ?>"
                                    placeholder="Enter e-wallet number">

                            </div>

                        <?php else: ?>

                            <div class="fake-payment-box cod-box">

                                <div class="cod-icon">

                                    <i class="fa-solid fa-truck"></i>

                                </div>

                                <strong>
                                    Cash on Delivery
                                </strong>

                                <span>
                                    Your payment will be collected
                                    when the order arrives.
                                </span>

                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- Payment Action -->

                    <div class="payment-action-card">

                        <div class="payment-secure-info">

                            <i class="fa-solid fa-lock"></i>

                            <div>

                                <strong>
                                    Secure Simulation
                                </strong>

                                <span>
                                    Your information is processed
                                    locally for this demo.
                                </span>

                            </div>

                        </div>

                        <form
                            action="payment-success.php"
                            method="POST">

                            <input
                                type="hidden"
                                name="payment_method"
                                value="<?php echo htmlspecialchars($payment_method); ?>">

                            <button
                                type="submit"
                                class="pay-now-btn">

                                <i class="fa-solid fa-lock"></i>

                                Pay Rp<?php echo number_format(
                                    $total,
                                    0,
                                    ',',
                                    '.'
                                ); ?>

                            </button>

                        </form>

                    </div>

                </section>

                <!-- ================= PAYMENT SUMMARY ================= -->

                <aside class="payment-summary">

                    <div class="payment-summary-card">

                        <div class="summary-brand">

                            <img
                                src="assets/img/hugeicons_book-open-02.png"
                                alt="BookVerse">

                            <div>

                                <strong>
                                    BookVerse
                                </strong>

                                <span>
                                    Order Summary
                                </span>

                            </div>

                        </div>

                        <hr>

                        <div class="payment-items">

                            <?php foreach ($cart as $item): ?>

                                <?php
                                $subtotal =
                                    $item['price'] * $item['quantity'];
                                ?>

                                <div class="payment-item">

                                    <div class="payment-item-image">

                                        <img
                                            src="<?php echo htmlspecialchars($item['image']); ?>"
                                            alt="<?php echo htmlspecialchars($item['title']); ?>">

                                    </div>

                                    <div class="payment-item-info">

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $item['title']
                                            );
                                            ?>
                                        </strong>

                                        <span>
                                            <?php echo $item['quantity']; ?>
                                            ×
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

                        <div class="payment-summary-row">

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

                        <div class="payment-summary-row">

                            <span>
                                Delivery
                            </span>

                            <strong>
                                Free
                            </strong>

                        </div>

                        <div class="payment-total">

                            <span>
                                Total Payment
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

                    </div>

                </aside>

            </div>

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