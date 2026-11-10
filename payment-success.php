<?php

require_once __DIR__ . '/config/user-session.php';
require_once __DIR__ . '/config/database.php';

// =========================================================
// USER ACCESS
// =========================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// =========================================================
// PROSES PEMILIHAN PROVIDER PEMBAYARAN
// =========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $order_id = (int) ($_POST['order_id'] ?? 0);
    $provider = trim($_POST['provider'] ?? '');

    if ($order_id <= 0) {
        die("ID pesanan tidak valid.");
    }

    mysqli_begin_transaction($conn);

    try {

        // AMBIL ORDER DAN PASTIKAN MILIK USER

        $order_stmt = mysqli_prepare(
            $conn,
            "SELECT *
            FROM orders
            WHERE id = ? AND user_id = ?
            FOR UPDATE"
        );

        if (!$order_stmt) {
            throw new Exception("Gagal mengambil data pesanan.");
        }

        mysqli_stmt_bind_param(
            $order_stmt,
            "ii",
            $order_id,
            $user_id
        );

        mysqli_stmt_execute($order_stmt);

        $order_result = mysqli_stmt_get_result($order_stmt);
        $order_data = mysqli_fetch_assoc($order_result);

        mysqli_stmt_close($order_stmt);

        if (!$order_data) {
            throw new Exception("Pesanan tidak ditemukan.");
        }

        // VALIDASI PROVIDER

        $payment_method = $order_data['payment_method'];

        $allowed_providers = [
            'bank_transfer' => [
                'BCA',
                'Mandiri',
                'BRI'
            ],
            'e_wallet' => [
                'GoPay',
                'DANA',
                'OVO'
            ]
        ];

        if (
            !isset($allowed_providers[$payment_method]) ||
            !in_array($provider, $allowed_providers[$payment_method], true)
        ) {
            throw new Exception("Provider pembayaran tidak valid.");
        }

        // AMBIL TRANSAKSI PEMBAYARAN YANG SUDAH DIBUAT DI CHECKOUT

        $payment_stmt = mysqli_prepare(
            $conn,
            "SELECT *
            FROM payment_transactions
            WHERE order_id = ?
            LIMIT 1
            FOR UPDATE"
        );

        if (!$payment_stmt) {
            throw new Exception("Gagal mengambil transaksi pembayaran.");
        }

        mysqli_stmt_bind_param(
            $payment_stmt,
            "i",
            $order_id
        );

        mysqli_stmt_execute($payment_stmt);

        $payment_result = mysqli_stmt_get_result($payment_stmt);
        $payment_data = mysqli_fetch_assoc($payment_result);

        mysqli_stmt_close($payment_stmt);

        if (!$payment_data) {
            throw new Exception("Transaksi pembayaran tidak ditemukan.");
        }

        // JANGAN UBAH TRANSAKSI YANG SUDAH DIPROSES

        if ($payment_data['payment_status'] !== 'Pending') {
            throw new Exception(
                "Transaksi ini sudah diproses. Silakan cek halaman Pesanan Saya."
            );
        }

        if (!empty($payment_data['payment_proof'])) {
            throw new Exception(
                "Bukti pembayaran sudah pernah diunggah."
            );
        }

        // UPDATE PROVIDER, BUKAN MEMBUAT TRANSAKSI BARU

        $update_stmt = mysqli_prepare(
            $conn,
            "UPDATE payment_transactions
            SET provider = ?, updated_at = NOW()
            WHERE order_id = ?"
        );

        if (!$update_stmt) {
            throw new Exception("Gagal menyiapkan pembaruan pembayaran.");
        }

        mysqli_stmt_bind_param(
            $update_stmt,
            "si",
            $provider,
            $order_id
        );

        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Gagal memperbarui provider pembayaran.");
        }

        mysqli_stmt_close($update_stmt);

        // AMBIL ITEM PESANAN DARI DATABASE

        $items_stmt = mysqli_prepare(
            $conn,
            "SELECT
                oi.book_id,
                oi.quantity,
                oi.price,
                b.title,
                b.author
            FROM order_items oi
            JOIN books b ON b.id = oi.book_id
            WHERE oi.order_id = ?"
        );

        if (!$items_stmt) {
            throw new Exception("Gagal mengambil detail buku.");
        }

        mysqli_stmt_bind_param(
            $items_stmt,
            "i",
            $order_id
        );

        mysqli_stmt_execute($items_stmt);

        $items_result = mysqli_stmt_get_result($items_stmt);

        $receipt_items = [];

        while ($item = mysqli_fetch_assoc($items_result)) {

            $quantity = (int) $item['quantity'];
            $price = (float) $item['price'];

            $receipt_items[] = [
                'title' => $item['title'],
                'author' => $item['author'] ?? '',
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $price * $quantity
            ];
        }

        mysqli_stmt_close($items_stmt);

        if (empty($receipt_items)) {
            throw new Exception("Detail buku dalam pesanan tidak ditemukan.");
        }

        mysqli_commit($conn);

        // SIMPAN DATA STRUK KE SESSION

        $_SESSION['last_order'] = [
            'order_id' => (int) $order_data['id'],
            'order_number' => $order_data['order_number'],
            'transaction_code' => $payment_data['transaction_code'],
            'recipient_name' => $order_data['recipient_name'],
            'phone' => $order_data['phone'],
            'address' => $order_data['address'],
            'payment_method' => $payment_method,
            'provider' => $provider,
            'total_price' => (float) $order_data['total_price'],
            'status' => $order_data['status'],
            'payment_status' => $payment_data['payment_status'],
            'created_at' => $order_data['created_at'],
            'items' => $receipt_items
        ];

        // KERANJANG DIHAPUS SETELAH PROVIDER DIPILIH

        unset($_SESSION['cart']);
        unset($_SESSION['checkout']);

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        die(
            "Gagal memproses pembayaran: " .
            htmlspecialchars($e->getMessage()) .
            "<br><br><a href='orders.php'>Kembali ke Pesanan Saya</a>"
        );
    }

} elseif (!isset($_SESSION['last_order'])) {

    header("Location: orders.php");
    exit;
}

// =========================================================
// ORDER INFORMATION
// =========================================================

$order = $_SESSION['last_order'];

$payment_labels = [
    'bank_transfer' => 'Bank Virtual Account',
    'e_wallet' => 'E-Wallet'
];

$payment_label =
    $payment_labels[$order['payment_method']]
    ?? 'Payment Simulation';

$provider_label = $order['provider'] ?? '-';

$order_date = date(
    'd F Y, H:i',
    strtotime($order['created_at'])
);

// =========================================================
// CEK STATUS PEMBAYARAN TERBARU DARI DATABASE
// =========================================================

$status_stmt = mysqli_prepare(
    $conn,
    "SELECT payment_status, payment_proof, rejection_reason
    FROM payment_transactions
    WHERE order_id = ?
    AND EXISTS (
        SELECT 1 FROM orders
        WHERE orders.id = payment_transactions.order_id
        AND orders.user_id = ?
    )
    LIMIT 1"
);

mysqli_stmt_bind_param(
    $status_stmt,
    "ii",
    $order['order_id'],
    $user_id
);

mysqli_stmt_execute($status_stmt);

$status_result = mysqli_stmt_get_result($status_stmt);
$payment_data = mysqli_fetch_assoc($status_result);

mysqli_stmt_close($status_stmt);

if (!$payment_data) {
    die("Data pembayaran tidak ditemukan.");
}

$current_payment_status = $payment_data['payment_status'];
$payment_proof = $payment_data['payment_proof'];
$rejection_reason = $payment_data['rejection_reason'] ?? '';

$order['payment_status'] = $current_payment_status;

// =========================================================
// CSRF TOKEN
// =========================================================

if (
    empty($_SESSION['payment_proof_token']) ||
    in_array($current_payment_status, ['Waiting Verification', 'Paid'], true)
) {
    $_SESSION['payment_proof_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Order Created - BookVerse</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <link rel="stylesheet" href="assets/css/style.css">

    <!-- CSS KHUSUS UPLOAD BUKTI PEMBAYARAN -->

    <link rel="stylesheet" href="assets/css/payment-proof.css">

</head>

<body>

    <!-- HEADER -->

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

                    <button type="button" class="icon-btn">
                        <i class="fa-regular fa-heart"></i>
                    </button>

                    <a href="cart.php" class="icon-btn">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </a>

                    <div class="profile-menu">

                        <button
                            type="button"
                            class="profile-btn"
                            id="profileBtn">

                            <i class="fa-solid fa-user"></i>

                            <span>
                                <?php echo htmlspecialchars($_SESSION['fullname'] ?? ''); ?>
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
                                        <?php echo htmlspecialchars($_SESSION['fullname'] ?? ''); ?>
                                    </strong>

                                    <small>
                                        <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>
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

                </div>

            </div>

        </div>

    </header>

    <!-- ORDER CREATED -->

    <main class="payment-success-page">

        <div class="container">

            <div class="payment-success-container">

                <div class="payment-success-card">

                    <div class="success-header">

                        <div class="success-icon">
                            <i class="fa-solid fa-check"></i>
                        </div>

                        <h1>Order Successfully Created!</h1>

                        <p>
                            Your order has been created and is waiting
                            for payment verification.
                        </p>

                        <span class="simulation-badge">
                            <i class="fa-solid fa-shield-halved"></i>
                            Payment Simulation
                        </span>

                    </div>

                    <!-- ORDER INFORMATION -->

                    <div class="order-info">

                        <div class="order-info-box">
                            <span>Order Number</span>
                            <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                        </div>

                        <div class="order-info-box">
                            <span>Order Date</span>
                            <strong><?php echo htmlspecialchars($order_date); ?></strong>
                        </div>

                        <div class="order-info-box">
                            <span>Payment Method</span>
                            <strong><?php echo htmlspecialchars($payment_label); ?></strong>
                        </div>

                        <div class="order-info-box">
                            <span>Payment Provider</span>
                            <strong><?php echo htmlspecialchars($provider_label); ?></strong>
                        </div>

                        <div class="order-info-box">
                            <span>Order Status</span>
                            <strong>Pending</strong>
                        </div>

                        <div class="order-info-box">
                            <span>Payment Status</span>
                            <strong><?php echo htmlspecialchars($current_payment_status); ?></strong>
                        </div>

                    </div>

                    <!-- DIGITAL RECEIPT -->

                    <div class="receipt-section">

                        <div class="receipt-brand">

                            <img
                                src="assets/img/hugeicons_book-open-02.png"
                                alt="BookVerse Logo">

                            <div>
                                <h2>BookVerse</h2>
                                <p>Every Book Has a Story</p>
                            </div>

                        </div>

                        <div class="receipt-heading">

                            <div>
                                <span>BOOKVERSE INVOICE</span>
                                <h3>Digital Order Receipt</h3>
                                <p>Thank you for shopping with BookVerse.</p>
                            </div>

                            <div>
                                <i class="fa-solid fa-clock"></i>
                                Payment Pending
                            </div>

                        </div>

                        <div class="receipt-order-number">

                            <span>ORDER NUMBER</span>

                            <strong>
                                <?php echo htmlspecialchars($order['order_number']); ?>
                            </strong>

                        </div>

                        <div class="receipt-qr-section">

                            <div id="orderQRCode"></div>

                            <div>
                                <strong>Order Reference QR</strong>
                                <p>
                                    Scan this QR Code to identify your
                                    BookVerse order.
                                </p>
                            </div>

                        </div>

                        <h3>Order Details</h3>

                        <div class="receipt-items">

                            <?php foreach ($order['items'] as $item): ?>

                                <div class="receipt-item">

                                    <div class="receipt-item-info">

                                        <strong>
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </strong>

                                        <span>
                                            <?php echo htmlspecialchars($item['author']); ?>
                                        </span>

                                        <span>
                                            Harga satuan: Rp<?php
                                            echo number_format($item['price'], 0, ',', '.');
                                            ?>
                                        </span>

                                    </div>

                                    <div class="receipt-item-quantity">
                                        <?php echo (int) $item['quantity']; ?> ×
                                    </div>

                                    <div class="receipt-item-price">
                                        Rp<?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="receipt-total">

                            <div class="receipt-total-row">
                                <span>Subtotal</span>
                                <strong>
                                    Rp<?php echo number_format($order['total_price'], 0, ',', '.'); ?>
                                </strong>
                            </div>

                            <div class="receipt-total-row">
                                <span>Delivery</span>
                                <strong>Free</strong>
                            </div>

                            <div class="receipt-total-row receipt-grand-total">
                                <span>Total Payment</span>
                                <strong>
                                    Rp<?php echo number_format($order['total_price'], 0, ',', '.'); ?>
                                </strong>
                            </div>

                        </div>

                    </div>

                    <!-- SHIPPING INFORMATION -->

                    <div class="shipping-section">

                        <h3>Shipping Information</h3>

                        <div class="shipping-row">
                            <span class="shipping-label">Recipient</span>
                            <span class="shipping-value">
                                <?php echo htmlspecialchars($order['recipient_name']); ?>
                            </span>
                        </div>

                        <div class="shipping-row">
                            <span class="shipping-label">Phone</span>
                            <span class="shipping-value">
                                <?php echo htmlspecialchars($order['phone']); ?>
                            </span>
                        </div>

                        <div class="shipping-row">
                            <span class="shipping-label">Address</span>
                            <span class="shipping-value">
                                <?php echo nl2br(htmlspecialchars($order['address'])); ?>
                            </span>
                        </div>

                    </div>

                    <!-- UPLOAD PAYMENT PROOF -->

                    <div class="payment-proof-section">

                        <div class="payment-proof-heading">

                            <i class="fa-solid fa-cloud-arrow-up"></i>

                            <h3>Bukti Pembayaran</h3>

                            <p>
                                Silakan unggah bukti pembayaran untuk
                                melanjutkan proses verifikasi pesanan.
                            </p>

                        </div>

                        <?php if ($current_payment_status === 'Paid'): ?>

                            <div class="proof-status proof-success">
                                <i class="fa-solid fa-circle-check"></i>
                                Pembayaran sudah berhasil diverifikasi Admin.
                            </div>

                        <?php elseif ($current_payment_status === 'Waiting Verification'): ?>

                            <div class="proof-status proof-pending">
                                <i class="fa-solid fa-hourglass-half"></i>
                                Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi Admin.
                            </div>

                            <?php if (!empty($payment_proof)): ?>

                                <a
                                    href="<?php echo htmlspecialchars($payment_proof); ?>"
                                    target="_blank"
                                    rel="noopener"
                                    class="proof-preview-link">

                                    <i class="fa-solid fa-image"></i>
                                    Lihat Bukti Pembayaran

                                </a>

                            <?php endif; ?>

                        <?php elseif ($current_payment_status === 'Expired'): ?>

                            <div class="proof-status proof-error">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Batas waktu pembayaran sudah berakhir.
                            </div>

                        <?php elseif ($current_payment_status === 'Rejected'): ?>

                            <div class="proof-status proof-error">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Bukti pembayaran ditolak Admin.
                                <?php if (!empty($rejection_reason)): ?>
                                    <p>
                                        Alasan:
                                        <?php echo htmlspecialchars($rejection_reason); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <form
                                action="payment-proof.php"
                                method="POST"
                                enctype="multipart/form-data"
                                class="payment-proof-form">

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?php echo (int) $order['order_id']; ?>">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['payment_proof_token']); ?>">

                                <label for="payment_proof">
                                    Unggah ulang bukti pembayaran
                                </label>

                                <input
                                    type="file"
                                    name="payment_proof"
                                    id="payment_proof"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                    required>

                                <small>
                                    Format JPG, JPEG, PNG. Maksimal 5 MB.
                                </small>

                                <button type="submit" class="proof-submit-btn">
                                    <i class="fa-solid fa-upload"></i>
                                    Kirim Ulang Bukti Pembayaran
                                </button>

                            </form>

                        <?php elseif ($current_payment_status === 'Pending'): ?>

                            <form
                                action="payment-proof.php"
                                method="POST"
                                enctype="multipart/form-data"
                                class="payment-proof-form">

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?php echo (int) $order['order_id']; ?>">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['payment_proof_token']); ?>">

                                <label for="payment_proof">
                                    Pilih Bukti Pembayaran
                                </label>

                                <input
                                    type="file"
                                    name="payment_proof"
                                    id="payment_proof"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                    required>

                                <small>
                                    Format JPG, JPEG, PNG. Maksimal 5 MB.
                                </small>

                                <button type="submit" class="proof-submit-btn">
                                    <i class="fa-solid fa-upload"></i>
                                    Kirim Bukti Pembayaran
                                </button>

                            </form>

                        <?php else: ?>

                            <div class="proof-status proof-error">
                                Status pembayaran tidak memungkinkan upload bukti.
                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- ACTIONS -->

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

                    <p class="success-note">

                        <i class="fa-solid fa-circle-info"></i>

                        This is a simulated payment receipt for BookVerse
                        learning purposes. No real-money transactions
                        have taken place.

                    </p>

                </div>

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
                <p>© 2026 BookVerse. All Rights Reserved.</p>
            </div>

        </div>

    </footer>

    <!-- QR CODE GENERATOR -->

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
