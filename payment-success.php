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
| Pastikan Checkout dan Cart Tersedia
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['checkout']) ||
    empty($_SESSION['cart'])
) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil Data Checkout
|--------------------------------------------------------------------------
*/

$checkout = $_SESSION['checkout'];
$cart = $_SESSION['cart'];

$user_id = (int) $_SESSION['user_id'];

$recipient_name = trim($checkout['recipient_name'] ?? '');
$phone = trim($checkout['phone'] ?? '');
$address = trim($checkout['address'] ?? '');
$payment_method = $checkout['payment_method'] ?? '';

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
    header("Location: checkout.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Hitung Total dari Cart
|--------------------------------------------------------------------------
*/

$total_price = 0;

foreach ($cart as $item) {

    $quantity = (int) $item['quantity'];
    $price = (float) $item['price'];

    $total_price += $price * $quantity;
}

/*
|--------------------------------------------------------------------------
| Cegah Transaksi Kosong
|--------------------------------------------------------------------------
*/

if ($total_price <= 0) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Jika Belum Ada Order yang Diproses
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['last_order'])) {

    /*
    |--------------------------------------------------------------------------
    | Generate Nomor Order
    |--------------------------------------------------------------------------
    */

    $order_number =
        'BV' .
        date('YmdHis') .
        random_int(100, 999);

    /*
    |--------------------------------------------------------------------------
    | Mulai Database Transaction
    |--------------------------------------------------------------------------
    */

    mysqli_begin_transaction($conn);

    try {

        /*
        |--------------------------------------------------------------------------
        | Simpan Order
        |--------------------------------------------------------------------------
        */

        $order_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO orders
            (
                user_id,
                recipient_name,
                phone,
                address,
                order_number,
                total_price,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
        );

        if (!$order_stmt) {
            throw new Exception(
                "Gagal menyiapkan data pesanan."
            );
        }

        mysqli_stmt_bind_param(
            $order_stmt,
            "issssd",
            $user_id,
            $recipient_name,
            $phone,
            $address,
            $order_number,
            $total_price
        );

        if (!mysqli_stmt_execute($order_stmt)) {
            throw new Exception(
                "Gagal menyimpan pesanan."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil ID Order
        |--------------------------------------------------------------------------
        */

        $order_id = mysqli_insert_id($conn);

        mysqli_stmt_close($order_stmt);

        /*
        |--------------------------------------------------------------------------
        | Simpan Order Items
        |--------------------------------------------------------------------------
        */

        $item_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO order_items
            (
                order_id,
                book_id,
                quantity,
                price
            )
            VALUES (?, ?, ?, ?)"
        );

        if (!$item_stmt) {
            throw new Exception(
                "Gagal menyiapkan item pesanan."
            );
        }

        foreach ($cart as $item) {

            $book_id = (int) $item['id'];
            $quantity = (int) $item['quantity'];
            $price = (float) $item['price'];

            mysqli_stmt_bind_param(
                $item_stmt,
                "iiid",
                $order_id,
                $book_id,
                $quantity,
                $price
            );

            if (!mysqli_stmt_execute($item_stmt)) {
                throw new Exception(
                    "Gagal menyimpan item pesanan."
                );
            }
        }

        mysqli_stmt_close($item_stmt);

        /*
        |--------------------------------------------------------------------------
        | Commit Transaction
        |--------------------------------------------------------------------------
        */

        mysqli_commit($conn);

        /*
        |--------------------------------------------------------------------------
        | Simpan Data Struk ke Session
        |--------------------------------------------------------------------------
        */

        $receipt_items = [];

        foreach ($cart as $item) {

            $receipt_items[] = [
                'title' => $item['title'],
                'author' => $item['author'],
                'quantity' => (int) $item['quantity'],
                'price' => (float) $item['price'],
                'subtotal' =>
                    (float) $item['price'] *
                    (int) $item['quantity']
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan Order Terakhir
        |--------------------------------------------------------------------------
        */

        $_SESSION['last_order'] = [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'recipient_name' => $recipient_name,
            'phone' => $phone,
            'address' => $address,
            'payment_method' => $payment_method,
            'total_price' => $total_price,
            'status' => 'Pending',
            'created_at' => date('Y-m-d H:i:s'),
            'items' => $receipt_items
        ];

        /*
        |--------------------------------------------------------------------------
        | Kosongkan Cart
        |--------------------------------------------------------------------------
        */

        unset($_SESSION['cart']);

        /*
        |--------------------------------------------------------------------------
        | Bersihkan Checkout Session
        |--------------------------------------------------------------------------
        */

        unset($_SESSION['checkout']);

    } catch (Exception $e) {

        /*
        |--------------------------------------------------------------------------
        | Rollback Jika Gagal
        |--------------------------------------------------------------------------
        */

        mysqli_rollback($conn);

        die(
            "Terjadi kesalahan saat membuat pesanan. " .
            "Silakan coba kembali."
        );
    }
}

/*
|--------------------------------------------------------------------------
| Ambil Data Order Terakhir
|--------------------------------------------------------------------------
*/

$order = $_SESSION['last_order'];

/*
|--------------------------------------------------------------------------
| Format Metode Pembayaran
|--------------------------------------------------------------------------
*/

$payment_labels = [
    'bank_transfer' => 'Bank Transfer',
    'e_wallet' => 'E-Wallet',
    'cod' => 'Cash on Delivery'
];

$payment_label =
    $payment_labels[$order['payment_method']]
    ?? 'Payment Simulation';

/*
|--------------------------------------------------------------------------
| Format Tanggal
|--------------------------------------------------------------------------
*/

$order_date = date(
    'd F Y, H:i',
    strtotime($order['created_at'])
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Payment Successful - BookVerse
    </title>

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

    <style>

        .payment-success-page {
            padding: 70px 0 90px;
            background: #faf9ff;
            min-height: calc(100vh - 100px);
        }

        .payment-success-container {
            max-width: 850px;
            margin: 0 auto;
        }

        .payment-success-card {
            background: #ffffff;
            border-radius: 28px;
            padding: 45px;
            box-shadow: 0 12px 35px rgba(76, 61, 125, 0.08);
        }

        .success-header {
            text-align: center;
            padding-bottom: 30px;
            border-bottom: 1px solid #ece9f3;
        }

        .success-icon {
            width: 76px;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #f0ebff;
            color: #6c3ef4;
            font-size: 32px;
        }

        .success-header h1 {
            margin: 0 0 10px;
            color: #292333;
            font-size: 30px;
            font-weight: 700;
        }

        .success-header p {
            margin: 0;
            color: #777181;
            font-size: 14px;
        }

        .simulation-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            background: #f0ebff;
            color: #6c3ef4;
            font-size: 11px;
            font-weight: 600;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin: 30px 0;
        }

        .order-info-box {
            padding: 17px;
            border-radius: 14px;
            background: #faf9ff;
        }

        .order-info-box span {
            display: block;
            margin-bottom: 6px;
            color: #8a8495;
            font-size: 11px;
        }

        .order-info-box strong {
            color: #292333;
            font-size: 13px;
        }

        .receipt-section {
            margin-top: 30px;
        }

        .receipt-section h3 {
            margin: 0 0 18px;
            color: #292333;
            font-size: 18px;
        }

        .receipt-items {
            display: flex;
            flex-direction: column;
            border: 1px solid #ece9f3;
            border-radius: 16px;
            overflow: hidden;
        }

        .receipt-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 20px;
            align-items: center;
            padding: 17px 20px;
            border-bottom: 1px solid #ece9f3;
        }

        .receipt-item:last-child {
            border-bottom: none;
        }

        .receipt-item-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .receipt-item-info strong {
            color: #292333;
            font-size: 13px;
        }

        .receipt-item-info span {
            color: #8a8495;
            font-size: 11px;
        }

        .receipt-item-quantity {
            color: #777181;
            font-size: 12px;
        }

        .receipt-item-price {
            color: #443c52;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .receipt-total {
            margin-top: 22px;
            padding: 20px;
            border-radius: 16px;
            background: #f7f4ff;
        }

        .receipt-total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 10px;
            color: #777181;
            font-size: 13px;
        }

        .receipt-total-row:last-child {
            margin-bottom: 0;
        }

        .receipt-total-row strong {
            color: #443c52;
        }

        .receipt-grand-total {
            padding-top: 15px;
            margin-top: 15px;
            border-top: 1px solid #ddd5ff;
        }

        .receipt-grand-total span {
            color: #292333;
            font-size: 15px;
            font-weight: 600;
        }

        .receipt-grand-total strong {
            color: #6c3ef4;
            font-size: 21px;
            font-weight: 700;
        }

        .shipping-section {
            margin-top: 28px;
            padding: 22px;
            border: 1px solid #ece9f3;
            border-radius: 16px;
        }

        .shipping-section h3 {
            margin: 0 0 18px;
            color: #292333;
            font-size: 16px;
        }

        .shipping-row {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
        }

        .shipping-row:last-child {
            margin-bottom: 0;
        }

        .shipping-label {
            min-width: 130px;
            color: #8a8495;
            font-size: 12px;
        }

        .shipping-value {
            color: #443c52;
            font-size: 12px;
            font-weight: 500;
        }

        .success-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-top: 32px;
        }

        .success-action-btn {
            min-width: 180px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 20px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s ease;
        }

        .success-action-primary {
            background: #6c3ef4;
            color: #ffffff;
        }

        .success-action-primary:hover {
            background: #5530d9;
        }

        .success-action-secondary {
            border: 2px solid #6c3ef4;
            background: #ffffff;
            color: #6c3ef4;
        }

        .success-action-secondary:hover {
            background: #6c3ef4;
            color: #ffffff;
        }

        .success-note {
            margin: 25px 0 0;
            padding: 14px 18px;
            border-radius: 12px;
            background: #faf9ff;
            color: #8a8495;
            font-size: 11px;
            line-height: 1.7;
            text-align: center;
        }

        .success-note i {
            margin-right: 5px;
            color: #6c3ef4;
        }

        @media (max-width: 768px) {

            .payment-success-card {
                padding: 30px 20px;
            }

            .order-info {
                grid-template-columns: 1fr;
            }

            .receipt-item {
                grid-template-columns: 1fr auto;
            }

            .receipt-item-quantity {
                grid-column: 2;
                grid-row: 1;
            }

            .receipt-item-price {
                grid-column: 1 / -1;
            }

            .shipping-row {
                flex-direction: column;
                gap: 4px;
            }

            .success-actions {
                flex-direction: column;
            }

            .success-action-btn {
                width: 100%;
            }
        }

    </style>

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
                                <?php
                                echo htmlspecialchars(
                                    $_SESSION['fullname'] ?? ''
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
                                            $_SESSION['fullname'] ?? ''
                                        );
                                        ?>
                                    </strong>

                                    <small>
                                        <?php
                                        echo htmlspecialchars(
                                            $_SESSION['email'] ?? ''
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

                </div>

            </div>

        </div>

    </header>

    <!-- ================= PAYMENT SUCCESS ================= -->

    <main class="payment-success-page">

        <div class="container">

            <div class="payment-success-container">

                <div class="payment-success-card">

                    <!-- Success Header -->

                    <div class="success-header">

                        <div class="success-icon">

                            <i class="fa-solid fa-check"></i>

                        </div>

                        <h1>
                            Payment Successful!
                        </h1>

                        <p>
                            Your BookVerse order has been successfully created.
                        </p>

                        <span class="simulation-badge">

                            <i class="fa-solid fa-shield-halved"></i>

                            Payment Simulation

                        </span>

                    </div>

                    <!-- Order Information -->

                    <div class="order-info">

                        <div class="order-info-box">

                            <span>
                                Order Number
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $order['order_number']
                                );
                                ?>
                            </strong>

                        </div>

                        <div class="order-info-box">

                            <span>
                                Order Date
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $order_date
                                );
                                ?>
                            </strong>

                        </div>

                        <div class="order-info-box">

                            <span>
                                Payment Method
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $payment_label
                                );
                                ?>
                            </strong>

                        </div>

                        <div class="order-info-box">

                            <span>
                                Order Status
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $order['status']
                                );
                                ?>
                            </strong>

                        </div>

                    </div>

                    <!-- Receipt -->

                    <div class="receipt-section">

                        <h3>
                            Order Receipt
                        </h3>

                        <div class="receipt-items">

                            <?php foreach ($order['items'] as $item): ?>

                                <div class="receipt-item">

                                    <div class="receipt-item-info">

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $item['title']
                                            );
                                            ?>
                                        </strong>

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $item['author']
                                            );
                                            ?>
                                        </span>

                                    </div>

                                    <div class="receipt-item-quantity">

                                        <?php
                                        echo $item['quantity'];
                                        ?>
                                        ×

                                    </div>

                                    <div class="receipt-item-price">

                                        Rp<?php
                                        echo number_format(
                                            $item['subtotal'],
                                            0,
                                            ',',
                                            '.'
                                        );
                                        ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="receipt-total">

                            <div class="receipt-total-row">

                                <span>
                                    Subtotal
                                </span>

                                <strong>

                                    Rp<?php
                                    echo number_format(
                                        $order['total_price'],
                                        0,
                                        ',',
                                        '.'
                                    );
                                    ?>

                                </strong>

                            </div>

                            <div class="receipt-total-row">

                                <span>
                                    Delivery
                                </span>

                                <strong>
                                    Free
                                </strong>

                            </div>

                            <div class="receipt-total-row receipt-grand-total">

                                <span>
                                    Total Payment
                                </span>

                                <strong>

                                    Rp<?php
                                    echo number_format(
                                        $order['total_price'],
                                        0,
                                        ',',
                                        '.'
                                    );
                                    ?>

                                </strong>

                            </div>

                        </div>

                    </div>

                    <!-- Shipping Information -->

                    <div class="shipping-section">

                        <h3>
                            Shipping Information
                        </h3>

                        <div class="shipping-row">

                            <span class="shipping-label">
                                Recipient
                            </span>

                            <span class="shipping-value">
                                <?php
                                echo htmlspecialchars(
                                    $order['recipient_name']
                                );
                                ?>
                            </span>

                        </div>

                        <div class="shipping-row">

                            <span class="shipping-label">
                                Phone
                            </span>

                            <span class="shipping-value">
                                <?php
                                echo htmlspecialchars(
                                    $order['phone']
                                );
                                ?>
                            </span>

                        </div>

                        <div class="shipping-row">

                            <span class="shipping-label">
                                Address
                            </span>

                            <span class="shipping-value">
                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $order['address']
                                    )
                                );
                                ?>
                            </span>

                        </div>

                    </div>

                    <!-- Actions -->

                    <div class="success-actions">

                        <a
                            href="orders.php"
                            class="success-action-btn success-action-primary">

                            <i class="fa-solid fa-box"></i>

                            Lihat Pesanan

                        </a>

                        <a
                            href="index.php"
                            class="success-action-btn success-action-secondary">

                            <i class="fa-solid fa-book"></i>

                            Kembali Belanja

                        </a>

                    </div>

                    <!-- Simulation Notice -->

                    <p class="success-note">

                        <i class="fa-solid fa-circle-info"></i>

                        Ini adalah bukti pembayaran simulasi
                        untuk kebutuhan pembelajaran BookVerse.
                        Tidak ada transaksi uang sungguhan yang dilakukan.

                    </p>

                </div>

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