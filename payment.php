
<?php

require_once __DIR__ . '/config/user-session.php';
require_once __DIR__ . '/config/database.php';

// =========================================================
// USER LOGIN
// =========================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// =========================================================
// AMBIL ORDER ID
// =========================================================

$order_id = (int) (
    $_GET['order_id']
    ?? $_SESSION['pending_order_id']
    ?? 0
);

if ($order_id <= 0) {
    header("Location: orders.php");
    exit;
}

// =========================================================
// AMBIL DATA ORDER MILIK USER
// =========================================================

$order_stmt = mysqli_prepare(
    $conn,
    "SELECT *
    FROM orders
    WHERE id = ? AND user_id = ?
    LIMIT 1"
);

if (!$order_stmt) {
    die("Gagal mengambil data pesanan.");
}

mysqli_stmt_bind_param(
    $order_stmt,
    "ii",
    $order_id,
    $user_id
);

mysqli_stmt_execute($order_stmt);

$order_result = mysqli_stmt_get_result($order_stmt);
$order = mysqli_fetch_assoc($order_result);

mysqli_stmt_close($order_stmt);

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

// =========================================================
// AMBIL TRANSAKSI PEMBAYARAN
// =========================================================

$payment_stmt = mysqli_prepare(
    $conn,
    "SELECT *
    FROM payment_transactions
    WHERE order_id = ?
    LIMIT 1"
);

if (!$payment_stmt) {
    die("Gagal mengambil transaksi pembayaran.");
}

mysqli_stmt_bind_param(
    $payment_stmt,
    "i",
    $order_id
);

mysqli_stmt_execute($payment_stmt);

$payment_result = mysqli_stmt_get_result($payment_stmt);
$payment_transaction = mysqli_fetch_assoc($payment_result);

mysqli_stmt_close($payment_stmt);

if (!$payment_transaction) {
    die("Transaksi pembayaran tidak ditemukan.");
}

// =========================================================
// VALIDASI STATUS PEMBAYARAN
// =========================================================

if ($payment_transaction['payment_status'] !== 'Pending') {
    header("Location: payment-success.php");
    exit;
}

// =========================================================
// DATA ORDER
// =========================================================

$recipient_name = $order['recipient_name'];
$phone = $order['phone'];
$address = $order['address'];
$payment_method = $order['payment_method'];

$total = (float) $order['total_price'];

// =========================================================
// VALIDASI PAYMENT METHOD
// =========================================================

$allowed_payment_methods = [
    'bank_transfer',
    'e_wallet'
];

if (!in_array($payment_method, $allowed_payment_methods, true)) {
    die("Metode pembayaran tidak valid.");
}

// =========================================================
// AMBIL ITEM ORDER DARI DATABASE
// =========================================================

$items_stmt = mysqli_prepare(
    $conn,
    "SELECT
        oi.book_id AS id,
        oi.quantity,
        oi.price,
        b.title,
        b.author,
        b.image
    FROM order_items oi
    JOIN books b ON b.id = oi.book_id
    WHERE oi.order_id = ?"
);

if (!$items_stmt) {
    die("Gagal mengambil detail buku.");
}

mysqli_stmt_bind_param(
    $items_stmt,
    "i",
    $order_id
);

mysqli_stmt_execute($items_stmt);

$items_result = mysqli_stmt_get_result($items_stmt);

$cart = [];

while ($item = mysqli_fetch_assoc($items_result)) {

    $book_id = (int) $item['id'];

    $cart[$book_id] = [
        'id' => $book_id,
        'title' => $item['title'],
        'author' => $item['author'] ?? '',
        'image' => $item['image'] ?? '',
        'price' => (float) $item['price'],
        'quantity' => (int) $item['quantity']
    ];
}

mysqli_stmt_close($items_stmt);

if (empty($cart)) {
    die("Detail buku dalam pesanan tidak ditemukan.");
}

// =========================================================
// SIMPAN SESSION ORDER
// =========================================================

$_SESSION['pending_order_id'] = $order_id;

$_SESSION['checkout'] = [
    'recipient_name' => $recipient_name,
    'phone' => $phone,
    'address' => $address,
    'payment_method' => $payment_method,
    'total' => $total
];

// =========================================================
// INFORMASI PAYMENT METHOD
// =========================================================

$payment_details = [
    'bank_transfer' => [
        'name' => 'Bank Virtual Account',
        'icon' => 'fa-building-columns',
        'description' => 'Choose your preferred bank'
    ],
    'e_wallet' => [
        'name' => 'E-Wallet',
        'icon' => 'fa-wallet',
        'description' => 'Choose your preferred digital wallet'
    ]
];

$current_payment = $payment_details[$payment_method];

// =========================================================
// PROVIDER
// =========================================================

$providers = [
    'bank_transfer' => [
        'BCA' => 'BCA Virtual Account',
        'Mandiri' => 'Mandiri Virtual Account',
        'BRI' => 'BRI Virtual Account'
    ],
    'e_wallet' => [
        'GoPay' => 'GoPay',
        'DANA' => 'DANA',
        'OVO' => 'OVO'
    ]
];

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment - BookVerse</title>

    <!-- Google Font -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Main CSS -->

    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Payment CSS -->

    <link rel="stylesheet" href="assets/css/payment.css">

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

                    <a href="wishlist.php" class="icon-btn">

                        <i class="fa-regular fa-heart"></i>

                    </a>

                    <a href="cart.php" class="icon-btn">

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
                                    <?php echo htmlspecialchars($_SESSION['fullname']); ?>
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
                                            <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                                        </strong>

                                        <small>
                                            <?php echo htmlspecialchars($_SESSION['email']); ?>
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

                                <a href="logout.php" class="logout-link">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    <span>Logout</span>
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

            <div class="payment-page-header">

                <div>

                    <span class="payment-badge">

                        <i class="fa-solid fa-shield-halved"></i>

                        Secure Checkout

                    </span>

                    <h1>Complete Your Payment</h1>

                    <p>
                        Review your payment details and complete
                        your simulated transaction.
                    </p>

                </div>

                <a href="checkout.php" class="payment-back-btn">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Checkout

                </a>

            </div>

            <!-- SIMULATION NOTICE -->

            <div class="payment-simulation-notice">

                <div class="payment-notice-icon">

                    <i class="fa-solid fa-graduation-cap"></i>

                </div>

                <div>

                    <strong>Payment Simulation</strong>

                    <p>
                        This is a simulated payment system created
                        for educational purposes. No real money will
                        be transferred.
                    </p>

                </div>

            </div>

            <!-- PAYMENT LAYOUT -->

            <div class="payment-layout">

                <section class="payment-main">

                    <!-- PAYMENT METHOD -->

                    <div class="payment-card">

                        <div class="payment-card-header">

                            <div>

                                <span class="payment-step">01</span>

                                <div>

                                    <h2>Payment Method</h2>

                                    <p>
                                        Your selected payment method.
                                    </p>

                                </div>

                            </div>

                        </div>

                        <div class="selected-payment">

                            <div class="selected-payment-icon">

                                <i class="fa-solid <?php echo htmlspecialchars($current_payment['icon']); ?>"></i>

                            </div>

                            <div class="selected-payment-info">

                                <strong>
                                    <?php echo htmlspecialchars($current_payment['name']); ?>
                                </strong>

                                <span>
                                    <?php echo htmlspecialchars($current_payment['description']); ?>
                                </span>

                            </div>

                            <i class="fa-solid fa-circle-check"></i>

                        </div>

                    </div>

                    <!-- PAYMENT DETAILS -->

                    <div class="payment-card">

                        <div class="payment-card-header">

                            <div>

                                <span class="payment-step">02</span>

                                <div>

                                    <h2>Payment Details</h2>

                                    <p>
                                        Select your payment provider.
                                    </p>

                                </div>

                            </div>

                        </div>

                        <form
                            id="paymentForm"
                            action="payment-success.php"
                            method="POST">

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?php echo (int) $order_id; ?>">

                            <!-- PAYMENT PROVIDER -->

                            <div class="payment-input-group">

                                <label class="provider-section-label">
                                    Select Payment Provider
                                </label>

                                <div class="provider-grid">

                                    <?php foreach ($providers[$payment_method] as $value => $label): ?>

                                        <label class="provider-option">

                                            <input
                                                type="radio"
                                                name="provider"
                                                value="<?php echo htmlspecialchars($value); ?>"
                                                required>

                                            <span class="provider-card">

                                                <span class="provider-logo">

                                                    <?php if ($value === 'BCA'): ?>

                                                        <span class="provider-bca">
                                                            BCA
                                                        </span>

                                                    <?php elseif ($value === 'Mandiri'): ?>

                                                        <i class="fa-solid fa-building-columns provider-mandiri"></i>

                                                    <?php elseif ($value === 'BRI'): ?>

                                                        <span class="provider-bri">
                                                            BRI
                                                        </span>

                                                    <?php elseif ($value === 'GoPay'): ?>

                                                        <span class="provider-gopay">
                                                            GoPay
                                                        </span>

                                                    <?php elseif ($value === 'DANA'): ?>

                                                        <span class="provider-dana">
                                                            DANA
                                                        </span>

                                                    <?php elseif ($value === 'OVO'): ?>

                                                        <span class="provider-ovo">
                                                            OVO
                                                        </span>

                                                    <?php endif; ?>

                                                </span>

                                                <span class="provider-name">
                                                    <?php echo htmlspecialchars($label); ?>
                                                </span>

                                                <span class="provider-check">

                                                    <i class="fa-solid fa-circle-check"></i>

                                                </span>

                                            </span>

                                        </label>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                            <!-- BANK TRANSFER DETAILS -->

                            <?php if ($payment_method === 'bank_transfer'): ?>

                                <div class="fake-payment-box">

                                    <div class="fake-bank-header">

                                        <div>

                                            <span>BookVerse Virtual Account</span>

                                            <strong>BOOKVERSE</strong>

                                        </div>

                                        <i class="fa-solid fa-building-columns"></i>

                                    </div>

                                    <div class="fake-account-number">

                                        <span>Virtual Account Number</span>

                                        <strong>
                                            Akan dibuat setelah provider dipilih
                                        </strong>

                                    </div>

                                    <div class="fake-payment-row">

                                        <span>Account Name</span>

                                        <strong>BOOKVERSE STORE</strong>

                                    </div>

                                </div>

                                <div class="payment-input-group">

                                    <label for="transfer_name">
                                        Account Holder Name
                                    </label>

                                    <input
                                        type="text"
                                        id="transfer_name"
                                        placeholder="Enter account holder name"
                                        value="<?php echo htmlspecialchars($recipient_name); ?>">

                                </div>

                            <!-- E-WALLET DETAILS -->

                            <?php elseif ($payment_method === 'e_wallet'): ?>

                                <div class="fake-payment-box ewallet-box">

                                    <div class="ewallet-icon">

                                        <i class="fa-solid fa-wallet"></i>

                                    </div>

                                    <strong>BookVerse E-Wallet</strong>

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
                                        placeholder="Enter e-wallet number"
                                        value="<?php echo htmlspecialchars($phone); ?>">

                                </div>

                            <?php endif; ?>

                        </form>

                    </div>

                    <!-- PAYMENT ACTION -->

                    <div class="payment-action-card">

                        <div class="payment-secure-info">

                            <i class="fa-solid fa-lock"></i>

                            <div>

                                <strong>Secure Simulation</strong>

                                <span>
                                    Your information is processed
                                    locally for this demo.
                                </span>

                            </div>

                        </div>

                        <button
                            type="submit"
                            form="paymentForm"
                            class="pay-now-btn">

                            <i class="fa-solid fa-lock"></i>

                            Continue Rp<?php echo number_format($total, 0, ',', '.'); ?>

                        </button>

                    </div>

                </section>

                <!-- PAYMENT SUMMARY -->

                <aside class="payment-summary">

                    <div class="payment-summary-card">

                        <div class="summary-brand">

                            <img
                                src="assets/img/hugeicons_book-open-02.png"
                                alt="BookVerse">

                            <div>

                                <strong>BookVerse</strong>

                                <span>Order Summary</span>

                            </div>

                        </div>

                        <hr>

                        <div class="payment-items">

                            <?php foreach ($cart as $item): ?>

                                <?php
                                $subtotal = $item['price'] * $item['quantity'];
                                ?>

                                <div class="payment-item">

                                    <div class="payment-item-image">

                                        <img
                                            src="<?php echo htmlspecialchars($item['image']); ?>"
                                            alt="<?php echo htmlspecialchars($item['title']); ?>">

                                    </div>

                                    <div class="payment-item-info">

                                        <strong>
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </strong>

                                        <span>
                                            <?php echo (int) $item['quantity']; ?>
                                            × Rp<?php echo number_format($item['price'], 0, ',', '.'); ?>
                                        </span>

                                    </div>

                                    <strong>
                                        Rp<?php echo number_format($subtotal, 0, ',', '.'); ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <hr>

                        <div class="payment-summary-row">

                            <span>Subtotal</span>

                            <strong>
                                Rp<?php echo number_format($total, 0, ',', '.'); ?>
                            </strong>

                        </div>

                        <div class="payment-summary-row">

                            <span>Delivery</span>

                            <strong>Free</strong>

                        </div>

                        <div class="payment-total">

                            <span>Total Payment</span>

                            <strong>
                                Rp<?php echo number_format($total, 0, ',', '.'); ?>
                            </strong>

                        </div>

                    </div>

                </aside>

            </div>

        </div>

    </main>

    <!-- FOOTER -->

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