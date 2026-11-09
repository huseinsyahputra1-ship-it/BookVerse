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

    if ($quantity <= 0) {
        header("Location: cart.php");
        exit;
    }

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
        | Kunci dan Periksa Stok Buku
        |--------------------------------------------------------------------------
        */

        // Urutkan ID buku agar penguncian lebih konsisten
        $book_ids = array_map('intval', array_keys($cart));
        sort($book_ids);

        $stock_stmt = mysqli_prepare(
            $conn,
            "SELECT stock
             FROM books
             WHERE id = ?
             FOR UPDATE"
        );

        if (!$stock_stmt) {
            throw new Exception(
                "Gagal memeriksa stok buku."
            );
        }

        foreach ($book_ids as $book_id) {

            $quantity = (int) $cart[$book_id]['quantity'];

            mysqli_stmt_bind_param(
                $stock_stmt,
                "i",
                $book_id
            );

            if (!mysqli_stmt_execute($stock_stmt)) {
                throw new Exception(
                    "Gagal mengambil stok buku."
                );
            }

            $stock_result = mysqli_stmt_get_result($stock_stmt);
            $stock_data = mysqli_fetch_assoc($stock_result);

            if (!$stock_data) {
                throw new Exception(
                    "Salah satu buku sudah tidak tersedia."
                );
            }

            $available_stock = (int) $stock_data['stock'];

            if (
                $available_stock <= 0 ||
                $quantity > $available_stock
            ) {
                throw new Exception(
                    "Stok buku " .
                    $cart[$book_id]['title'] .
                    " tidak mencukupi. Silakan periksa keranjang."
                );
            }
        }

        mysqli_stmt_close($stock_stmt);

        /*
        |--------------------------------------------------------------------------
        | Kurangi Stok Buku
        |--------------------------------------------------------------------------
        */

        $update_stock_stmt = mysqli_prepare(
            $conn,
            "UPDATE books
             SET stock = stock - ?
             WHERE id = ?
             AND stock >= ?"
        );

        if (!$update_stock_stmt) {
            throw new Exception(
                "Gagal menyiapkan pengurangan stok."
            );
        }

        foreach ($cart as $item) {

            $book_id = (int) $item['id'];
            $quantity = (int) $item['quantity'];

            mysqli_stmt_bind_param(
                $update_stock_stmt,
                "iii",
                $quantity,
                $book_id,
                $quantity
            );

            if (!mysqli_stmt_execute($update_stock_stmt)) {
                throw new Exception(
                    "Gagal mengurangi stok buku."
                );
            }

            if (mysqli_stmt_affected_rows($update_stock_stmt) !== 1) {
                throw new Exception(
                    "Stok buku berubah. Silakan periksa keranjang dan coba kembali."
                );
            }
        }

        mysqli_stmt_close($update_stock_stmt);

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
                payment_method,
                order_number,
                total_price,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
        );

        if (!$order_stmt) {
            throw new Exception(
                "Gagal menyiapkan data pesanan."
            );
        }

        mysqli_stmt_bind_param(
            $order_stmt,
            "isssssd",
            $user_id,
            $recipient_name,
            $phone,
            $address,
            $payment_method,
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

        mysqli_rollback($conn);

        die(
            "Pesanan belum berhasil dibuat: " .
            htmlspecialchars($e->getMessage()) .
            "<br><br><a href='cart.php'>Kembali ke Keranjang</a>"
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

    <title>Payment Successful - BookVerse</title>

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

    <!-- QR Code Library -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- Existing Global CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="header">

        <div class="container">

            <div class="navbar">

                <div class="logo">

                    <img
                        src="assets/img/hugeicons_book-open-02.png"
                        alt="BookVerse">

                    <div class="logo-text">

                        <h2>BookVerse</h2>

                        <p>Every Book Has a Story</p>

                    </div>

                </div>

                <nav>

                    <ul class="menu">

                        <li><a href="index.php">Home</a></li>
                        <li><a href="books.php">Books</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>

                    </ul>

                </nav>

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

                    <a href="cart.php" class="icon-btn">

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

                                <span>Profile</span>

                            </a>

                            <a href="orders.php">

                                <i class="fa-solid fa-box"></i>

                                <span>Pesanan Saya</span>

                            </a>

                            <hr>

                            <a
                                href="logout.php"
                                class="logout-link">

                                <i class="fa-solid fa-right-from-bracket"></i>

                                <span>Logout</span>

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

                        <h1>Payment Successful!</h1>

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

                            <span>Order Number</span>

                            <strong>
                                <?php
                                echo htmlspecialchars($order['order_number']);
                                ?>
                            </strong>

                        </div>

                        <div class="order-info-box">

                            <span>Order Date</span>

                            <strong>
                                <?php echo htmlspecialchars($order_date); ?>
                            </strong>

                        </div>

                        <div class="order-info-box">

                            <span>Payment Method</span>

                            <strong>
                                <?php echo htmlspecialchars($payment_label); ?>
                            </strong>

                        </div>

                        <div class="order-info-box">

                            <span>Order Status</span>

                            <strong>
                                <?php echo htmlspecialchars($order['status']); ?>
                            </strong>

                        </div>

                    </div>

                    <!-- ================= DIGITAL ORDER RECEIPT ================= -->

                    <div class="receipt-section">

                        <!-- BookVerse Branding -->

                        <div class="receipt-brand">

                            <img
                                src="assets/img/hugeicons_book-open-02.png"
                                alt="BookVerse Logo">

                            <div>

                                <h2>BookVerse</h2>

                                <p>Every Book Has a Story</p>

                            </div>

                        </div>

                        <!-- Receipt Heading -->

                        <div class="receipt-heading">

                            <div>

                                <span>BOOKVERSE INVOICE</span>

                                <h3>Digital Order Receipt</h3>

                                <p>
                                    Thank you for shopping with BookVerse.
                                </p>

                            </div>

                            <div>

                                <i class="fa-solid fa-circle-check"></i>

                                Order Created

                            </div>

                        </div>

                        <!-- Order Number -->

                        <div class="receipt-order-number">

                            <span>ORDER NUMBER</span>

                            <strong>
                                <?php
                                echo htmlspecialchars($order['order_number']);
                                ?>
                            </strong>

                        </div>

                        <!-- QR Code -->

                        <div class="receipt-qr-section">

                            <div id="orderQRCode"></div>

                            <div>

                                <strong>Order Reference QR</strong>

                                <p>
                                    Scan this QR Code to identify your BookVerse order.
                                </p>

                            </div>

                        </div>

                        <!-- Order Details -->

                        <h3>Order Details</h3>

                        <div class="receipt-items">

                            <?php foreach ($order['items'] as $item): ?>

                                <div class="receipt-item">

                                    <div class="receipt-item-info">

                                        <strong>
                                            <?php
                                            echo htmlspecialchars($item['title']);
                                            ?>
                                        </strong>

                                        <span>
                                            <?php
                                            echo htmlspecialchars($item['author']);
                                            ?>
                                        </span>

                                        <span>
                                            Harga satuan: Rp<?php
                                            echo number_format(
                                                $item['price'],
                                                0,
                                                ',',
                                                '.'
                                            );
                                            ?>
                                        </span>

                                    </div>

                                    <div class="receipt-item-quantity">

                                        <?php
                                        echo (int) $item['quantity'];
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

                        <!-- Payment Summary -->

                        <div class="receipt-total">

                            <div class="receipt-total-row">

                                <span>Subtotal</span>

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

                                <span>Delivery</span>

                                <strong>Free</strong>

                            </div>

                            <div class="receipt-total-row receipt-grand-total">

                                <span>Total Payment</span>

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

                    <!-- ================= SHIPPING INFORMATION ================= -->

                    <div class="shipping-section">

                        <h3>Shipping Information</h3>

                        <div class="shipping-row">

                            <span class="shipping-label">
                                Recipient
                            </span>

                            <span class="shipping-value">
                                <?php
                                echo htmlspecialchars($order['recipient_name']);
                                ?>
                            </span>

                        </div>

                        <div class="shipping-row">

                            <span class="shipping-label">
                                Phone
                            </span>

                            <span class="shipping-value">
                                <?php
                                echo htmlspecialchars($order['phone']);
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
                                    htmlspecialchars($order['address'])
                                );
                                ?>
                            </span>

                        </div>

                    </div>

                    <!-- Actions -->

                    <div class="success-actions">

                        <button
                            type="button"
                            onclick="window.print()"
                            class="success-action-btn">

                            <i class="fa-solid fa-print"></i>

                            Print / Save PDF

                        </button>

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

                        This is a simulated payment receipt for BookVerse learning purposes. No real-money transactions have taken place.

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

                    <h2>BookVerse</h2>

                    <p>
                        Every Book Has a Story.
                        Discover books that inspire your journey.
                    </p>

                </div>

                <div class="footer-links">

                    <h3>Quick Links</h3>

                    <ul>

                        <li><a href="index.php">Home</a></li>
                        <li><a href="books.php">Books</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="about.php">About</a></li>

                    </ul>

                </div>

                <div class="footer-contact">

                    <h3>Contact</h3>

                    <p>Email : info@bookverse.com</p>

                    <p>Phone : +62 812 3456 7890</p>

                    <p>Jakarta, Indonesia</p>

                </div>

            </div>

            <div class="footer-bottom">

                <p>
                    © 2026 BookVerse. All Rights Reserved.
                </p>

            </div>

        </div>

    </footer>

    <!-- QR Code Generator -->

    <script>
    document.addEventListener("DOMContentLoaded", function () {

        const qrContainer = document.getElementById("orderQRCode");

        const orderNumber = <?php
            echo json_encode(
                $order['order_number'],
                JSON_HEX_TAG |
                JSON_HEX_APOS |
                JSON_HEX_QUOT |
                JSON_HEX_AMP
            );
        ?>;

        if (qrContainer && window.QRCode) {

            new QRCode(qrContainer, {
                text: orderNumber,
                width: 110,
                height: 110,
                colorDark: "#292333",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

        }

    });
    </script>

    <script src="assets/js/script.js"></script>

</body>

</html>