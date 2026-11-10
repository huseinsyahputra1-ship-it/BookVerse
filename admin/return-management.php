<?php

require_once __DIR__ . '/admin-session.php';
include "../config/database.php";

// =========================================================
// ADMIN ACCESS
// =========================================================

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// =========================================================
// CSRF TOKEN
// =========================================================

if (empty($_SESSION['return_admin_token'])) {
    $_SESSION['return_admin_token'] = bin2hex(random_bytes(32));
}

$successMessage = "";
$errorMessage = "";

// =========================================================
// HANDLE ADMIN ACTION
// =========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['return_admin_token'], $csrfToken)) {
        $errorMessage = "Permintaan tidak valid. Silakan refresh halaman.";
    } else {
        $returnId = (int) ($_POST['return_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        $inspectionNotes = trim($_POST['inspection_notes'] ?? '');
        $restoreStock = isset($_POST['restore_stock']) && $_POST['restore_stock'] === '1';

        if ($returnId <= 0) {
            $errorMessage = "Data pengajuan tidak valid.";
        } else {
            mysqli_begin_transaction($conn);

            try {
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT rr.id, rr.return_status, rr.stock_restored,
                            rr.order_id, o.payment_method
                     FROM return_requests rr
                     INNER JOIN orders o ON o.id = rr.order_id
                     WHERE rr.id = ?
                     FOR UPDATE"
                );

                mysqli_stmt_bind_param($stmt, "i", $returnId);
                mysqli_stmt_execute($stmt);

                $returnData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);

                if (!$returnData) {
                    throw new Exception("Pengajuan Return tidak ditemukan.");
                }

                $status = $returnData['return_status'];
                $orderId = (int) $returnData['order_id'];

                // =========================================
                // REVIEW / APPROVE / REJECT
                // =========================================

                if ($action === 'review' || $action === 'approve' || $action === 'reject') {

                    if (!in_array($status, ['Requested', 'Under Review'], true)) {
                        throw new Exception("Pengajuan ini sudah melewati tahap pemeriksaan awal.");
                    }

                    if ($action === 'review') {
                        $stmt = mysqli_prepare(
                            $conn,
                            "UPDATE return_requests
                             SET return_status = 'Under Review', updated_at = NOW()
                             WHERE id = ?"
                        );

                        mysqli_stmt_bind_param($stmt, "i", $returnId);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        $successMessage = "Pengajuan berhasil masuk tahap pemeriksaan.";

                    } elseif ($action === 'approve') {
                        $stmt = mysqli_prepare(
                            $conn,
                            "UPDATE return_requests
                             SET return_status = 'Approved',
                                 rejection_reason = NULL,
                                 updated_at = NOW()
                             WHERE id = ?"
                        );

                        mysqli_stmt_bind_param($stmt, "i", $returnId);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        $successMessage = "Pengajuan Return berhasil disetujui.";

                    } else {
                        if ($rejectionReason === '' || strlen($rejectionReason) > 2000) {
                            throw new Exception("Alasan penolakan wajib diisi (maksimal 2000 karakter).");
                        }

                        $stmt = mysqli_prepare(
                            $conn,
                            "UPDATE return_requests
                             SET return_status = 'Rejected',
                                 rejection_reason = ?,
                                 updated_at = NOW()
                             WHERE id = ?"
                        );

                        mysqli_stmt_bind_param(
                            $stmt,
                            "si",
                            $rejectionReason,
                            $returnId
                        );

                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        $successMessage = "Pengajuan Return berhasil ditolak.";
                    }

                // =========================================
                // CONFIRM ITEM RECEIVED
                // =========================================

                } elseif ($action === 'receive') {

                    if ($status !== 'Return Shipped') {
                        throw new Exception("Barang hanya dapat dikonfirmasi setelah pelanggan mengirim retur.");
                    }

                    if ($inspectionNotes === '') {
                        throw new Exception("Catatan pemeriksaan barang wajib diisi.");
                    }

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE return_items
                         SET condition_notes = ?
                         WHERE return_id = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "si", $inspectionNotes, $returnId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // RESTORE STOCK
                    if ($restoreStock && (int) $returnData['stock_restored'] === 0) {

                        $itemsStmt = mysqli_prepare(
                            $conn,
                            "SELECT ri.book_id, ri.quantity
                             FROM return_items ri
                             WHERE ri.return_id = ?"
                        );

                        mysqli_stmt_bind_param($itemsStmt, "i", $returnId);
                        mysqli_stmt_execute($itemsStmt);

                        $itemsResult = mysqli_stmt_get_result($itemsStmt);

                        while ($item = mysqli_fetch_assoc($itemsResult)) {

                            $bookId = (int) $item['book_id'];
                            $quantity = (int) $item['quantity'];

                            $stockStmt = mysqli_prepare(
                                $conn,
                                "UPDATE books
                                 SET stock = stock + ?
                                 WHERE id = ?"
                            );

                            mysqli_stmt_bind_param($stockStmt, "ii", $quantity, $bookId);
                            mysqli_stmt_execute($stockStmt);
                            mysqli_stmt_close($stockStmt);

                            $activityType = 'Return';
                            $notes = "Stok dikembalikan dari Return #{$returnId}";
                            $adminId = (int) $_SESSION['user_id'];

                            $historyStmt = mysqli_prepare(
                                $conn,
                                "INSERT INTO stock_history
                                 (book_id, user_id, activity_type, quantity,
                                  stock_before, stock_after, notes, created_at)
                                 SELECT id, ?, ?, ?, stock - ?, stock, ?, NOW()
                                 FROM books
                                 WHERE id = ?"
                            );

                            mysqli_stmt_bind_param(
                                $historyStmt,
                                "isiisi",
                                $adminId,
                                $activityType,
                                $quantity,
                                $quantity,
                                $notes,
                                $bookId
                            );

                            mysqli_stmt_execute($historyStmt);
                            mysqli_stmt_close($historyStmt);
                        }

                        mysqli_stmt_close($itemsStmt);
                    }

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE return_requests
                         SET return_status = 'Item Received',
                             received_at = NOW(),
                             inspected_by = ?,
                             inspected_at = NOW(),
                             stock_restored = ?,
                             updated_at = NOW()
                         WHERE id = ?"
                    );

                    $adminId = (int) $_SESSION['user_id'];
                    $stockFlag = $restoreStock ? 1 : 0;

                    mysqli_stmt_bind_param(
                        $stmt,
                        "iii",
                        $adminId,
                        $stockFlag,
                        $returnId
                    );

                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    $successMessage = $restoreStock
                        ? "Barang diterima, pemeriksaan tersimpan, dan stok buku berhasil dikembalikan."
                        : "Barang diterima dan pemeriksaan tersimpan. Stok tidak ditambahkan karena barang tidak layak dijual.";

                // =========================================
                // PROCESS REFUND
                // =========================================

                } elseif ($action === 'process_refund') {

                    if ($status !== 'Item Received') {
                        throw new Exception("Refund hanya dapat diproses setelah barang diterima dan diperiksa.");
                    }

                    $amountStmt = mysqli_prepare(
                        $conn,
                        "SELECT COALESCE(SUM(ri.quantity * oi.price), 0) AS refund_amount
                         FROM return_items ri
                         INNER JOIN order_items oi
                           ON oi.order_id = ? AND oi.book_id = ri.book_id
                         WHERE ri.return_id = ?"
                    );

                    mysqli_stmt_bind_param($amountStmt, "ii", $orderId, $returnId);
                    mysqli_stmt_execute($amountStmt);

                    $amountRow = mysqli_fetch_assoc(mysqli_stmt_get_result($amountStmt));
                    mysqli_stmt_close($amountStmt);

                    $refundAmount = (float) ($amountRow['refund_amount'] ?? 0);

                    if ($refundAmount <= 0) {
                        throw new Exception("Nominal refund tidak valid. Periksa data return_items dan order_items.");
                    }

                    $refundCode = 'REF-' . date('YmdHis') . '-' . $returnId;
                    $refundMethod = $returnData['payment_method'];

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO refund_transactions
                         (return_id, order_id, refund_code, refund_amount,
                          refund_method, refund_status, processed_by,
                          processed_at, created_at)
                         VALUES (?, ?, ?, ?, ?, 'Processing', ?, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE
                            refund_amount = VALUES(refund_amount),
                            refund_method = VALUES(refund_method),
                            refund_status = 'Processing',
                            processed_by = VALUES(processed_by),
                            processed_at = NOW()"
                    );

                    $adminId = (int) $_SESSION['user_id'];

                    mysqli_stmt_bind_param(
                        $stmt,
                        "iisdsi",
                        $returnId,
                        $orderId,
                        $refundCode,
                        $refundAmount,
                        $refundMethod,
                        $adminId
                    );

                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE return_requests
                         SET return_status = 'Refund Processing',
                             updated_at = NOW()
                         WHERE id = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $returnId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    $successMessage = "Refund masuk tahap Processing. Silakan selesaikan transaksi refund.";

                // =========================================
                // COMPLETE REFUND
                // =========================================

                } elseif ($action === 'complete_refund') {

                    if ($status !== 'Refund Processing') {
                        throw new Exception("Refund belum berada dalam tahap Processing.");
                    }

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE refund_transactions
                         SET refund_status = 'Completed',
                             processed_by = ?,
                             processed_at = NOW()
                         WHERE return_id = ?
                         AND refund_status IN ('Pending', 'Processing')"
                    );

                    $adminId = (int) $_SESSION['user_id'];

                    mysqli_stmt_bind_param($stmt, "ii", $adminId, $returnId);
                    mysqli_stmt_execute($stmt);

                    $updated = mysqli_stmt_affected_rows($stmt);
                    mysqli_stmt_close($stmt);

                    if ($updated !== 1) {
                        throw new Exception("Transaksi refund tidak ditemukan atau sudah diselesaikan.");
                    }

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE return_requests
                         SET return_status = 'Refunded',
                             updated_at = NOW()
                         WHERE id = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $returnId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    $successMessage = "Refund berhasil diselesaikan dan status Return menjadi Refunded.";

                } else {
                    throw new Exception("Aksi tidak dikenal.");
                }

                mysqli_commit($conn);

            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $errorMessage = $e->getMessage();
            }
        }
    }
}


// =========================================================
// RETURN STATISTICS
// =========================================================

$statsQuery = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS total_returns,

        SUM(
            CASE
                WHEN return_status IN ('Requested', 'Under Review')
                THEN 1 ELSE 0
            END
        ) AS pending_returns,

        SUM(
            CASE
                WHEN return_status = 'Approved'
                THEN 1 ELSE 0
            END
        ) AS approved_returns,

        SUM(
            CASE
                WHEN return_status = 'Rejected'
                THEN 1 ELSE 0
            END
        ) AS rejected_returns

     FROM return_requests"
);

$stats = mysqli_fetch_assoc($statsQuery);


// =========================================================
// FILTER
// =========================================================

$filter = $_GET['status'] ?? 'all';

$allowedFilters = [
    'all',
    'Requested',
    'Under Review',
    'Approved',
    'Rejected',
    'Return Shipped',
    'Item Received',
    'Refund Processing',
    'Refunded'
];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}


// =========================================================
// FETCH RETURN REQUESTS
// =========================================================

if ($filter === 'all') {

    $returnsQuery = mysqli_query(
        $conn,
        "SELECT
            rr.*,
            o.order_number,
            o.total_price,
            o.user_id,
            u.fullname,
            u.email,
            rr.return_courier,
            rr.return_tracking_number

         FROM return_requests rr

         INNER JOIN orders o
            ON rr.order_id = o.id

         INNER JOIN users u
            ON rr.user_id = u.id

         ORDER BY rr.created_at DESC"
    );

} else {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            rr.*,
            o.order_number,
            o.total_price,
            o.user_id,
            u.fullname,
            u.email,
            rr.return_courier,
            rr.return_tracking_number

         FROM return_requests rr

         INNER JOIN orders o
            ON rr.order_id = o.id

         INNER JOIN users u
            ON rr.user_id = u.id

         WHERE rr.return_status = ?

         ORDER BY rr.created_at DESC"
    );

    mysqli_stmt_bind_param($stmt, "s", $filter);
    mysqli_stmt_execute($stmt);

    $returnsQuery = mysqli_stmt_get_result($stmt);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Return Management - BookVerse</title>

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

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <link
        rel="stylesheet"
        href="../assets/css/return-management.css">

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

                <a href="payment-transactions.php" class="admin-nav-link">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    <span>Payment Transactions</span>
                </a>

                <a href="return-management.php" class="admin-nav-link active">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Return Management</span>
                </a>

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
                        RETURN MANAGEMENT
                    </span>

                    <h1>Return &amp; Refund Requests</h1>

                    <p>
                        Review and manage customer return requests.
                    </p>

                </div>

            </div>


            <!-- ALERT -->

            <?php if ($successMessage): ?>

                <div class="return-admin-alert success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>

            <?php endif; ?>


            <?php if ($errorMessage): ?>

                <div class="return-admin-alert error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>

            <?php endif; ?>


            <!-- STATISTICS -->

            <div class="return-admin-stats">

                <div class="return-admin-card">
                    <span>Total Return Requests</span>
                    <strong>
                        <?php echo (int) ($stats['total_returns'] ?? 0); ?>
                    </strong>
                </div>

                <div class="return-admin-card">
                    <span>Waiting Review</span>
                    <strong>
                        <?php echo (int) ($stats['pending_returns'] ?? 0); ?>
                    </strong>
                </div>

                <div class="return-admin-card">
                    <span>Approved</span>
                    <strong>
                        <?php echo (int) ($stats['approved_returns'] ?? 0); ?>
                    </strong>
                </div>

                <div class="return-admin-card">
                    <span>Rejected</span>
                    <strong>
                        <?php echo (int) ($stats['rejected_returns'] ?? 0); ?>
                    </strong>
                </div>

            </div>


            <!-- FILTER -->

            <div class="return-filter">

                <?php

                $filterOptions = [
                    'all' => 'All Requests',
                    'Requested' => 'Requested',
                    'Under Review' => 'Under Review',
                    'Approved' => 'Approved',
                    'Rejected' => 'Rejected',
                    'Return Shipped' => 'Return Shipped',
                    'Item Received' => 'Item Received',
                    'Refund Processing' => 'Refund Processing',
                    'Refunded' => 'Refunded'
                ];

                foreach ($filterOptions as $value => $label):

                ?>

                    <a
                        href="return-management.php?status=<?php echo urlencode($value); ?>"
                        class="<?php echo $filter === $value ? 'active' : ''; ?>">

                        <?php echo htmlspecialchars($label); ?>

                    </a>

                <?php endforeach; ?>

            </div>


            <!-- RETURN REQUEST LIST -->

            <div class="return-admin-list">

                <?php if (mysqli_num_rows($returnsQuery) > 0): ?>

                    <?php while ($return = mysqli_fetch_assoc($returnsQuery)): ?>

                        <?php

                        $returnId = (int) $return['id'];

                        $imagesQuery = mysqli_prepare(
                            $conn,
                            "SELECT image_path
                             FROM return_images
                             WHERE return_id = ?
                             ORDER BY id ASC"
                        );

                        mysqli_stmt_bind_param(
                            $imagesQuery,
                            "i",
                            $returnId
                        );

                        mysqli_stmt_execute($imagesQuery);

                        $imagesResult = mysqli_stmt_get_result($imagesQuery);

                        ?>

                        <section class="return-admin-item">

                            <!-- HEADER -->

                            <div class="return-admin-header">

                                <div>

                                    <h3>
                                        Return #<?php echo $returnId; ?>
                                    </h3>

                                    <p>
                                        Order:
                                        <?php echo htmlspecialchars($return['order_number']); ?>
                                    </p>

                                    <p>
                                        Submitted:
                                        <?php echo date(
                                            'd M Y H:i',
                                            strtotime($return['created_at'])
                                        ); ?>
                                    </p>

                                </div>

                                <span class="return-admin-status">
                                    <?php echo htmlspecialchars($return['return_status']); ?>
                                </span>

                            </div>


                            <!-- CUSTOMER DETAILS -->

                            <div class="return-admin-details">

                                <div class="return-admin-detail">
                                    <span>Customer</span>
                                    <strong>
                                        <?php echo htmlspecialchars($return['fullname']); ?>
                                    </strong>
                                </div>

                                <div class="return-admin-detail">
                                    <span>Email</span>
                                    <strong>
                                        <?php echo htmlspecialchars($return['email']); ?>
                                    </strong>
                                </div>

                                <div class="return-admin-detail">
                                    <span>Return Reason</span>
                                    <strong>
                                        <?php echo htmlspecialchars($return['return_reason']); ?>
                                    </strong>
                                </div>

                                <div class="return-admin-detail">
                                    <span>Order Total</span>
                                    <strong>
                                        Rp<?php echo number_format(
                                            $return['total_price'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>
                                    </strong>
                                </div>

                            </div>


                            <!-- RETURN SHIPPING -->

                            <?php if (
                                !empty($return['return_courier']) ||
                                !empty($return['return_tracking_number'])
                            ): ?>

                                <div class="return-admin-description">

                                    <strong>Return Shipment:</strong>

                                    <p>
                                        Courier:
                                        <?php echo htmlspecialchars($return['return_courier'] ?: '-'); ?>
                                    </p>

                                    <p>
                                        Tracking Number:
                                        <?php echo htmlspecialchars($return['return_tracking_number'] ?: '-'); ?>
                                    </p>

                                </div>

                            <?php endif; ?>


                            <!-- REFUND INFORMATION -->

                            <?php

                            $refundInfoStmt = mysqli_prepare(
                                $conn,
                                "SELECT refund_code, refund_amount, refund_method, refund_status
                                 FROM refund_transactions
                                 WHERE return_id = ?
                                 LIMIT 1"
                            );

                            mysqli_stmt_bind_param($refundInfoStmt, "i", $returnId);
                            mysqli_stmt_execute($refundInfoStmt);

                            $refundInfo = mysqli_fetch_assoc(
                                mysqli_stmt_get_result($refundInfoStmt)
                            );

                            mysqli_stmt_close($refundInfoStmt);

                            ?>

                            <?php if ($refundInfo): ?>

                                <div class="return-admin-description">

                                    <strong>Refund Information</strong>

                                    <p>
                                        Refund Code:
                                        <?php echo htmlspecialchars($refundInfo['refund_code']); ?>
                                    </p>

                                    <p>
                                        Amount:
                                        Rp<?php echo number_format(
                                            $refundInfo['refund_amount'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>
                                    </p>

                                    <p>
                                        Method:
                                        <?php echo htmlspecialchars($refundInfo['refund_method']); ?>
                                    </p>

                                    <p>
                                        Status:
                                        <?php echo htmlspecialchars($refundInfo['refund_status']); ?>
                                    </p>

                                </div>

                            <?php endif; ?>


                            <!-- CUSTOMER DESCRIPTION -->

                            <div class="return-admin-description">

                                <strong>Customer Description:</strong>

                                <p>
                                    <?php echo nl2br(htmlspecialchars($return['description'])); ?>
                                </p>

                            </div>


                            <!-- EVIDENCE PHOTOS -->

                            <strong style="font-size:12px;">
                                Return Evidence Photos
                            </strong>

                            <div class="return-admin-images">

                                <?php if (mysqli_num_rows($imagesResult) > 0): ?>

                                    <?php while ($image = mysqli_fetch_assoc($imagesResult)): ?>

                                        <a
                                            href="../<?php echo htmlspecialchars($image['image_path']); ?>"
                                            target="_blank"
                                            rel="noopener">

                                            <img
                                                src="../<?php echo htmlspecialchars($image['image_path']); ?>"
                                                alt="Return Evidence">

                                        </a>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <p class="return-help-text">
                                        No evidence photos found.
                                    </p>

                                <?php endif; ?>

                            </div>

                            <?php mysqli_stmt_close($imagesQuery); ?>


                            <!-- ADMIN ACTIONS -->

                            <?php if (in_array(
                                $return['return_status'],
                                ['Requested', 'Under Review'],
                                true
                            )): ?>

                                <div class="return-admin-actions">

                                    <?php if ($return['return_status'] === 'Requested'): ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['return_admin_token']); ?>">

                                            <input
                                                type="hidden"
                                                name="return_id"
                                                value="<?php echo $returnId; ?>">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="review">

                                            <button
                                                type="submit"
                                                class="return-btn-review">

                                                <i class="fa-solid fa-eye"></i>
                                                Start Review

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['return_admin_token']); ?>">

                                        <input
                                            type="hidden"
                                            name="return_id"
                                            value="<?php echo $returnId; ?>">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve">

                                        <button
                                            type="submit"
                                            class="return-btn-approve"
                                            onclick="return confirm('Setujui pengajuan Return ini?')">

                                            <i class="fa-solid fa-check"></i>
                                            Approve Return

                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                        class="return-rejection-form">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['return_admin_token']); ?>">

                                        <input
                                            type="hidden"
                                            name="return_id"
                                            value="<?php echo $returnId; ?>">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject">

                                        <textarea
                                            name="rejection_reason"
                                            maxlength="2000"
                                            required
                                            placeholder="Masukkan alasan penolakan..."></textarea>

                                        <button
                                            type="submit"
                                            class="return-btn-reject"
                                            onclick="return confirm('Tolak pengajuan Return ini?')">

                                            <i class="fa-solid fa-xmark"></i>
                                            Reject Return

                                        </button>

                                    </form>

                                </div>


                            <?php elseif ($return['return_status'] === 'Return Shipped'): ?>

                                <!-- CONFIRM ITEM RECEIVED -->

                                <form method="POST" class="return-inspection-form">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['return_admin_token']); ?>">

                                    <input
                                        type="hidden"
                                        name="return_id"
                                        value="<?php echo $returnId; ?>">

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="receive">

                                    <label>
                                        Inspection Notes
                                    </label>

                                    <textarea
                                        name="inspection_notes"
                                        required
                                        placeholder="Tuliskan kondisi barang setelah diterima dan diperiksa..."></textarea>

                                    <label class="return-stock-option">

                                        <input
                                            type="checkbox"
                                            name="restore_stock"
                                            value="1">

                                        Buku masih layak dijual dan stok boleh dikembalikan.

                                    </label>

                                    <button
                                        type="submit"
                                        class="return-btn-approve"
                                        onclick="return confirm('Konfirmasi barang retur sudah diterima dan diperiksa?')">

                                        <i class="fa-solid fa-box-check"></i>
                                        Confirm Item Received

                                    </button>

                                </form>


                            <?php elseif ($return['return_status'] === 'Item Received'): ?>

                                <div class="return-admin-description">
                                    Barang sudah diterima dan diperiksa. Admin dapat melanjutkan proses refund.
                                </div>

                                <div class="return-admin-actions">

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['return_admin_token']); ?>">

                                        <input
                                            type="hidden"
                                            name="return_id"
                                            value="<?php echo $returnId; ?>">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="process_refund">

                                        <button
                                            type="submit"
                                            class="return-btn-review"
                                            onclick="return confirm('Mulai proses refund ke metode pembayaran awal?')">

                                            <i class="fa-solid fa-money-bill-transfer"></i>
                                            Process Refund

                                        </button>

                                    </form>

                                </div>


                            <?php elseif ($return['return_status'] === 'Refund Processing'): ?>

                                <div class="return-admin-actions">

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['return_admin_token']); ?>">

                                        <input
                                            type="hidden"
                                            name="return_id"
                                            value="<?php echo $returnId; ?>">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="complete_refund">

                                        <button
                                            type="submit"
                                            class="return-btn-approve"
                                            onclick="return confirm('Tandai refund ini sudah benar-benar diselesaikan?')">

                                            <i class="fa-solid fa-check-double"></i>
                                            Complete Refund

                                        </button>

                                    </form>

                                </div>


                            <?php else: ?>

                                <div class="return-admin-description">

                                    Pengajuan ini sudah diproses pada tahap:

                                    <strong>
                                        <?php echo htmlspecialchars($return['return_status']); ?>
                                    </strong>

                                    <?php if (!empty($return['rejection_reason'])): ?>

                                        <p>
                                            <strong>Rejection Reason:</strong>
                                            <br>
                                            <?php echo nl2br(htmlspecialchars($return['rejection_reason'])); ?>
                                        </p>

                                    <?php endif; ?>

                                </div>

                            <?php endif; ?>

                        </section>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="return-admin-empty">

                        <i class="fa-solid fa-box-open"></i>

                        <strong>No Return Requests</strong>

                        <p>
                            Belum ada pengajuan Return &amp; Refund untuk filter ini.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>

</body>

</html>