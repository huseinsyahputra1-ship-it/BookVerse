<?php

include "../config/database.php";
require_once __DIR__ . '/admin-session.php';

// ================= ADMIN ACCESS =================

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ================= CSRF TOKEN =================

if (empty($_SESSION['payment_admin_token'])) {
    $_SESSION['payment_admin_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

// ================= PROCESS VERIFICATION =================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';
    $transactionId = (int) ($_POST['transaction_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');

    if (!hash_equals($_SESSION['payment_admin_token'], $csrfToken)) {
        $message = "Permintaan tidak valid. Silakan muat ulang halaman.";
        $messageType = "error";

    } elseif ($transactionId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        $message = "Data transaksi tidak valid.";
        $messageType = "error";

    } elseif ($action === 'reject' && $rejectionReason === '') {
        $message = "Alasan penolakan wajib diisi.";
        $messageType = "error";

    } else {

        mysqli_begin_transaction($conn);

        try {

            // Ambil transaksi dan kunci data pesanan
            $stmt = mysqli_prepare(
                $conn,
                "SELECT
                    pt.payment_status,
                    pt.expires_at,
                    pt.order_id,
                    o.status AS order_status
                 FROM payment_transactions pt
                 INNER JOIN orders o ON pt.order_id = o.id
                 WHERE pt.id = ?
                 FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt, "i", $transactionId);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $transaction = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (!$transaction) {
                throw new Exception("Transaksi tidak ditemukan.");
            }

            if ($transaction['payment_status'] !== 'Waiting Verification') {
                throw new Exception("Transaksi ini sudah diproses atau tidak dapat diverifikasi.");
            }

            // ================= CHECK EXPIRY =================

            if (
                !empty($transaction['expires_at']) &&
                strtotime($transaction['expires_at']) <= time()
            ) {

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE payment_transactions
                     SET payment_status = 'Expired',
                         updated_at = NOW()
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param($stmt, "i", $transactionId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conn);

                $message = "Transaksi sudah kedaluwarsa.";
                $messageType = "error";

            // ================= APPROVE PAYMENT =================

            } elseif ($action === 'approve') {

                $orderId = (int) $transaction['order_id'];
                $adminId = (int) $_SESSION['user_id'];

                // Pesanan yang sudah dibatalkan atau selesai tidak boleh disetujui
                if (in_array($transaction['order_status'], ['Cancelled', 'Completed'], true)) {
                    throw new Exception(
                        "Pesanan yang sudah dibatalkan atau selesai tidak dapat disetujui."
                    );
                }

                // Update status pembayaran menjadi Paid
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE payment_transactions
                     SET payment_status = 'Paid',
                         verified_by = ?,
                         verified_at = NOW(),
                         rejection_reason = NULL,
                         updated_at = NOW()
                     WHERE id = ?
                     AND payment_status = 'Waiting Verification'"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ii",
                    $adminId,
                    $transactionId
                );

                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) !== 1) {
                    throw new Exception("Gagal menyetujui pembayaran.");
                }

                mysqli_stmt_close($stmt);

                // Otomatis ubah pesanan Pending menjadi Processing
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE orders
                     SET status = 'Processing'
                     WHERE id = ?
                     AND status = 'Pending'"
                );

                mysqli_stmt_bind_param($stmt, "i", $orderId);
                mysqli_stmt_execute($stmt);

                $orderUpdated = mysqli_stmt_affected_rows($stmt);

                mysqli_stmt_close($stmt);

                // Pastikan pesanan berhasil masuk proses
                if ($orderUpdated !== 1) {
                    throw new Exception(
                        "Pembayaran gagal diproses karena status pesanan tidak sesuai."
                    );
                }

                mysqli_commit($conn);

                $message = "Pembayaran berhasil disetujui. Status pesanan otomatis berubah menjadi Processing.";
                $messageType = "success";

            // ================= REJECT PAYMENT =================

            } else {

                $adminId = (int) $_SESSION['user_id'];

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE payment_transactions
                     SET payment_status = 'Rejected',
                         rejection_reason = ?,
                         verified_by = ?,
                         verified_at = NOW(),
                         updated_at = NOW()
                     WHERE id = ?
                     AND payment_status = 'Waiting Verification'"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sii",
                    $rejectionReason,
                    $adminId,
                    $transactionId
                );

                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) !== 1) {
                    throw new Exception("Gagal menolak pembayaran.");
                }

                mysqli_stmt_close($stmt);

                mysqli_commit($conn);

                $message = "Pembayaran berhasil ditolak.";
                $messageType = "success";
            }

        } catch (Throwable $e) {

            mysqli_rollback($conn);

            $message = $e->getMessage();
            $messageType = "error";
        }
    }

    // Token baru setelah setiap proses
    $_SESSION['payment_admin_token'] = bin2hex(random_bytes(32));
}

// ================= GET TRANSACTIONS =================

$transactionsQuery = mysqli_query(
    $conn,
    "SELECT
        pt.id,
        pt.order_id,
        pt.transaction_code,
        pt.payment_method,
        pt.provider,
        pt.payment_amount,
        pt.payment_status,
        pt.payment_proof,
        pt.rejection_reason,
        pt.expires_at,
        pt.created_at,
        o.order_number,
        o.user_id,
        u.fullname
    FROM payment_transactions pt
    INNER JOIN orders o ON pt.order_id = o.id
    INNER JOIN users u ON o.user_id = u.id
    ORDER BY pt.created_at DESC"
);

if (!$transactionsQuery) {
    die("Gagal mengambil data transaksi: " . htmlspecialchars(mysqli_error($conn)));
}

// ================= STATISTICS =================

$statsQuery = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS total,
        SUM(payment_status = 'Waiting Verification') AS waiting,
        SUM(payment_status = 'Paid') AS paid,
        SUM(payment_status = 'Rejected') AS rejected
    FROM payment_transactions"
);

if (!$statsQuery) {
    die("Gagal mengambil statistik transaksi: " . htmlspecialchars(mysqli_error($conn)));
}

$stats = mysqli_fetch_assoc($statsQuery);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment Transactions - BookVerse</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/payment-transactions.css">
</head>

<body>

    <!-- ADMIN HEADER -->

    <header class="admin-header">

        <div class="admin-header-left">

            <a href="../index.php" class="admin-logo">

                <img
                    src="../assets/img/hugeicons_book-open-02.png"
                    alt="BookVerse">

                <div>
                    <h2>BookVerse</h2>
                    <span>Admin Panel</span>
                </div>

            </a>

        </div>

        <div class="admin-header-right">

            <span class="admin-welcome">
                <i class="fa-solid fa-user-shield"></i>
                <?php echo htmlspecialchars($_SESSION['fullname']); ?>
            </span>

            <a href="logout.php" class="admin-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>

        </div>

    </header>

    <!-- ADMIN LAYOUT -->

    <div class="admin-layout">

        <!-- SIDEBAR -->

        <aside class="admin-sidebar">

            <nav class="admin-nav">

                <a href="index.php" class="admin-nav-link">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>

                <a href="books.php" class="admin-nav-link">
                    <i class="fa-solid fa-book"></i>
                    <span>Manage Books</span>
                </a>

                <a href="categories.php" class="admin-nav-link">
                    <i class="fa-solid fa-tags"></i>
                    <span>Manage Categories</span>
                </a>

                <a href="orders.php" class="admin-nav-link">
                    <i class="fa-solid fa-box"></i>
                    <span>Manage Orders</span>
                </a>

                <a href="payment-transactions.php" class="admin-nav-link active">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    <span>Payment Transactions</span>
                </a>

                <a href="return-management.php" class="admin-nav-link">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Return Management</span>

                <a href="users.php" class="admin-nav-link">
                    <i class="fa-solid fa-users"></i>
                    <span>Manage Users</span>
                </a>

                <a href="messages.php" class="admin-nav-link">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Messages</span>
                </a>

                <a href="stock-history.php" class="admin-nav-link">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Stock History</span>
                </a>

            </nav>

            <div class="admin-sidebar-bottom">

                <a href="../index.php" class="admin-back-home">
                    <i class="fa-solid fa-house"></i>
                    <span>Back to Store</span>
                </a>

            </div>

        </aside>

        <!-- MAIN CONTENT -->

        <main class="admin-main">

            <div class="admin-page-header">

                <div>
                    <span class="admin-page-label">
                        PAYMENT MANAGEMENT
                    </span>

                    <h1>Payment Transactions</h1>

                    <p>
                        Review payment proofs and verify customer transactions.
                    </p>
                </div>

            </div>

            <!-- NOTIFICATION -->

            <?php if ($message !== ''): ?>

                <div class="payment-alert <?php echo $messageType; ?>">
                    <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>

                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>

            <!-- STATISTICS -->

            <div class="payment-admin-stats">

                <div class="payment-admin-stat">
                    <span>Total Transactions</span>
                    <strong><?php echo (int) $stats['total']; ?></strong>
                </div>

                <div class="payment-admin-stat">
                    <span>Waiting Verification</span>
                    <strong><?php echo (int) $stats['waiting']; ?></strong>
                </div>

                <div class="payment-admin-stat">
                    <span>Paid</span>
                    <strong><?php echo (int) $stats['paid']; ?></strong>
                </div>

                <div class="payment-admin-stat">
                    <span>Rejected</span>
                    <strong><?php echo (int) $stats['rejected']; ?></strong>
                </div>

            </div>

            <!-- TRANSACTION LIST -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>
                        <span class="admin-section-label">
                            TRANSACTION RECORDS
                        </span>

                        <h2>All Payments</h2>
                    </div>

                </div>

                <div class="payment-transaction-list">

                    <?php if (mysqli_num_rows($transactionsQuery) > 0): ?>

                        <?php while ($transaction = mysqli_fetch_assoc($transactionsQuery)): ?>

                            <article class="payment-transaction-card">

                                <div class="payment-transaction-top">

                                    <div>
                                        <strong>
                                            <?php echo htmlspecialchars($transaction['order_number']); ?>
                                        </strong>

                                        <span>
                                            <?php echo htmlspecialchars($transaction['transaction_code']); ?>
                                        </span>
                                    </div>

                                    <span class="payment-status status-<?php echo strtolower(str_replace(' ', '-', $transaction['payment_status'])); ?>">
                                        <?php echo htmlspecialchars($transaction['payment_status']); ?>
                                    </span>

                                </div>

                                <div class="payment-transaction-info">

                                    <div>
                                        <span>Customer</span>
                                        <strong>
                                            <?php echo htmlspecialchars($transaction['fullname']); ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Payment Method</span>
                                        <strong>
                                            <?php echo htmlspecialchars($transaction['payment_method']); ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Provider</span>
                                        <strong>
                                            <?php echo htmlspecialchars($transaction['provider']); ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Payment Amount</span>
                                        <strong>
                                            Rp<?php echo number_format((float) $transaction['payment_amount'], 0, ',', '.'); ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Transaction Date</span>
                                        <strong>
                                            <?php echo htmlspecialchars($transaction['created_at']); ?>
                                        </strong>
                                    </div>

                                </div>

                                <?php if (!empty($transaction['payment_proof'])): ?>

                                    <div class="payment-proof-admin">

                                        <span>Payment Proof</span>

                                        <a
                                            href="../<?php echo htmlspecialchars($transaction['payment_proof']); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer">

                                            <i class="fa-solid fa-image"></i>
                                            View Payment Proof

                                        </a>

                                    </div>

                                <?php else: ?>

                                    <div class="payment-proof-admin">
                                        <span>Payment Proof</span>
                                        <strong>No proof uploaded</strong>
                                    </div>

                                <?php endif; ?>

                                <?php if ($transaction['payment_status'] === 'Rejected' && !empty($transaction['rejection_reason'])): ?>

                                    <div class="payment-rejection-info">
                                        <strong>Rejection Reason:</strong>
                                        <p>
                                            <?php echo htmlspecialchars($transaction['rejection_reason']); ?>
                                        </p>
                                    </div>

                                <?php endif; ?>

                                <?php if ($transaction['payment_status'] === 'Waiting Verification'): ?>

                                    <div class="payment-admin-actions">

                                        <form method="POST" class="payment-approve-form">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['payment_admin_token']); ?>">

                                            <input
                                                type="hidden"
                                                name="transaction_id"
                                                value="<?php echo (int) $transaction['id']; ?>">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="approve">

                                            <button
                                                type="submit"
                                                onclick="return confirm('Approve pembayaran ini?')">

                                                <i class="fa-solid fa-check"></i>
                                                Approve Payment

                                            </button>

                                        </form>

                                        <form method="POST" class="payment-reject-form">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['payment_admin_token']); ?>">

                                            <input
                                                type="hidden"
                                                name="transaction_id"
                                                value="<?php echo (int) $transaction['id']; ?>">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reject">

                                            <textarea
                                                name="rejection_reason"
                                                placeholder="Enter rejection reason..."
                                                required></textarea>

                                            <button
                                                type="submit"
                                                onclick="return confirm('Reject pembayaran ini?')">

                                                <i class="fa-solid fa-xmark"></i>
                                                Reject Payment

                                            </button>

                                        </form>

                                    </div>

                                <?php endif; ?>

                            </article>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <div class="payment-empty-state">
                            <i class="fa-solid fa-receipt"></i>
                            <h3>No Transactions Yet</h3>
                            <p>Payment transactions will appear here.</p>
                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </main>

    </div>

</body>

</html>