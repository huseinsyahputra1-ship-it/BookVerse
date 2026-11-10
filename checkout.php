<?php

require_once __DIR__ . "/config/user-session.php";
require_once __DIR__ . "/config/database.php";

// =========================================================
// USER LOGIN
// =========================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// =========================================================
// PROSES CHECKOUT
// =========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $recipient_name = trim($_POST['recipient_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';

    $allowed_payment_methods = [
        'bank_transfer',
        'e_wallet'
    ];

    if (
        $recipient_name === '' ||
        $phone === '' ||
        $address === '' ||
        !in_array($payment_method, $allowed_payment_methods, true)
    ) {
        echo "<script>
            alert('Data checkout tidak valid!');
            window.location='checkout.php';
        </script>";
        exit;
    }

    $cart = $_SESSION['cart'] ?? [];

    if (empty($cart)) {
        header("Location: cart.php");
        exit;
    }

    // =====================================================
    // HITUNG TOTAL PESANAN
    // =====================================================

    $total_price = 0;

    foreach ($cart as $item) {

        $quantity = (int) ($item['quantity'] ?? 0);
        $price = (float) ($item['price'] ?? 0);

        if ($quantity <= 0 || $price < 0) {
            die("Data keranjang tidak valid.");
        }

        $total_price += $quantity * $price;
    }

    if ($total_price <= 0) {
        die("Total pesanan tidak valid.");
    }

    // =====================================================
    // GENERATE NOMOR ORDER DAN TRANSAKSI
    // =====================================================

    $order_number = 'BV' . date('YmdHis') . random_int(100, 999);

    $transaction_code = 'PAY' . date('YmdHis') . random_int(1000, 9999);

    $payment_status = 'Pending';

    $provider = 'Belum Dipilih';

    $expires_at = date(
        'Y-m-d H:i:s',
        strtotime('+24 hours')
    );

    // =====================================================
    // DATABASE TRANSACTION
    // =====================================================

    mysqli_begin_transaction($conn);

    try {

        // -------------------------------------------------
        // VALIDASI STOK
        // -------------------------------------------------

        $stock_stmt = mysqli_prepare(
            $conn,
            "SELECT stock FROM books WHERE id = ? FOR UPDATE"
        );

        if (!$stock_stmt) {
            throw new Exception("Gagal memeriksa stok buku.");
        }

        $book_ids = array_map('intval', array_keys($cart));
        sort($book_ids);

        foreach ($book_ids as $book_id) {

            $quantity = (int) $cart[$book_id]['quantity'];

            mysqli_stmt_bind_param(
                $stock_stmt,
                "i",
                $book_id
            );

            if (!mysqli_stmt_execute($stock_stmt)) {
                throw new Exception("Gagal membaca stok buku.");
            }

            $result = mysqli_stmt_get_result($stock_stmt);
            $book = mysqli_fetch_assoc($result);

            if (!$book) {
                throw new Exception("Buku tidak ditemukan.");
            }

            if ((int) $book['stock'] < $quantity) {
                throw new Exception(
                    "Stok buku " .
                    ($cart[$book_id]['title'] ?? 'tersebut') .
                    " tidak mencukupi."
                );
            }
        }

        mysqli_stmt_close($stock_stmt);

        // -------------------------------------------------
        // SIMPAN ORDER
        // -------------------------------------------------

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
            throw new Exception("Gagal menyiapkan pesanan.");
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
            throw new Exception("Gagal menyimpan pesanan.");
        }

        $order_id = mysqli_insert_id($conn);

        mysqli_stmt_close($order_stmt);

        // -------------------------------------------------
        // SIMPAN ORDER ITEMS
        // -------------------------------------------------

        $item_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO order_items
            (order_id, book_id, quantity, price)
            VALUES (?, ?, ?, ?)"
        );

        if (!$item_stmt) {
            throw new Exception("Gagal menyiapkan item pesanan.");
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
                throw new Exception("Gagal menyimpan item pesanan.");
            }
        }

        mysqli_stmt_close($item_stmt);

        // -------------------------------------------------
        // SIMPAN PAYMENT TRANSACTION AWAL
        // -------------------------------------------------

        $payment_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO payment_transactions
            (
                order_id,
                transaction_code,
                payment_method,
                provider,
                payment_amount,
                payment_status,
                expires_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$payment_stmt) {
            throw new Exception(
                "Gagal menyiapkan transaksi pembayaran."
            );
        }

        mysqli_stmt_bind_param(
            $payment_stmt,
            "isssdss",
            $order_id,
            $transaction_code,
            $payment_method,
            $provider,
            $total_price,
            $payment_status,
            $expires_at
        );

        if (!mysqli_stmt_execute($payment_stmt)) {
            throw new Exception(
                "Gagal menyimpan transaksi pembayaran."
            );
        }

        mysqli_stmt_close($payment_stmt);

        // -------------------------------------------------
        // SIMPAN ORDER ID KE SESSION
        // -------------------------------------------------

        mysqli_commit($conn);

        $_SESSION['pending_order_id'] = $order_id;

        $_SESSION['checkout'] = [
            'recipient_name' => $recipient_name,
            'phone' => $phone,
            'address' => $address,
            'payment_method' => $payment_method,
            'total' => $total_price
        ];

        // Cart belum dihapus sampai proses pembayaran selesai.
        // Ini diperlukan agar payment-success.php tetap bisa
        // menggunakan data keranjang pada tahap berikutnya.

        header("Location: payment.php?order_id=" . $order_id);
        exit;

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        die(
            "Pesanan gagal dibuat: " .
            htmlspecialchars($e->getMessage()) .
            "<br><br><a href='cart.php'>Kembali ke Keranjang</a>"
        );
    }
}

// =========================================================
// AMBIL DATA CART
// =========================================================

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

// =========================================================
// HITUNG TOTAL
// =========================================================

$total = 0;

foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

// =========================================================
// DATA USER
// =========================================================

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

                        <h2>BookVerse</h2>

                        <p>Every Book Has a Story</p>

                    </div>

                </div>

                <!-- Menu -->

                <nav>

                    <ul class="menu">

                        <li><a href="index.php">Home</a></li>
                        <li><a href="books.php">Books</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>

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

                    <a href="wishlist.php" class="icon-btn">

                        <i class="fa-regular fa-heart"></i>

                    </a>

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

    <!-- ================= CHECKOUT ================= -->

    <main class="checkout-page">

        <div class="container">

            <div class="checkout-page-header">

                <div class="section-title">

                    <h2>Checkout</h2>

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
                action="checkout.php"
                method="POST">

                <div class="checkout-layout">

                    <!-- CUSTOMER INFORMATION -->

                    <div class="checkout-main">

                        <div class="checkout-card">

                            <div class="checkout-card-header">

                                <div>

                                    <span class="checkout-step">01</span>

                                    <div>

                                        <h3>Informasi Pengiriman</h3>

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

                        <!-- PAYMENT METHOD -->

                        <div class="checkout-card">

                            <div class="checkout-card-header">

                                <div>

                                    <span class="checkout-step">02</span>

                                    <div>

                                        <h3>Metode Pembayaran</h3>

                                        <p>
                                            Pilih metode pembayaran untuk pesanan.
                                        </p>

                                    </div>

                                </div>

                            </div>

                            <div class="payment-methods">

                                <!-- BANK TRANSFER -->

                                <label class="payment-option">

                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="bank_transfer"
                                        checked
                                        required>

                                    <div class="payment-icon">
                                        <i class="fa-solid fa-building-columns"></i>
                                    </div>

                                    <div class="payment-info">

                                        <strong>Bank Transfer</strong>

                                        <span>Pembayaran simulasi</span>

                                    </div>

                                    <i class="fa-solid fa-circle-check payment-check"></i>

                                </label>

                                <!-- E-WALLET -->

                                <label class="payment-option">

                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="e_wallet">

                                    <div class="payment-icon">
                                        <i class="fa-solid fa-wallet"></i>
                                    </div>

                                    <div class="payment-info">

                                        <strong>E-Wallet</strong>

                                        <span>Pembayaran simulasi</span>

                                    </div>

                                    <i class="fa-solid fa-circle-check payment-check"></i>

                                </label>

                            </div>

                        </div>

                    </div>

                    <!-- ORDER SUMMARY -->

                    <aside class="checkout-summary">

                        <div class="checkout-summary-card">

                            <h3>Order Summary</h3>

                            <div class="checkout-items">

                                <?php foreach ($cart as $item): ?>

                                    <?php
                                    $subtotal = $item['price'] * $item['quantity'];
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

                                <span>Subtotal</span>

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

                                <span>Delivery</span>

                                <strong>Free</strong>

                            </div>

                            <hr>

                            <div class="summary-total">

                                <span>Total</span>

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

    <script src="assets/js/script.js"></script>

</body>

</html>