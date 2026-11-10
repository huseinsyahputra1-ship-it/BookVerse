<?php

require_once __DIR__ . '/admin-session.php';
include "../config/database.php";

// ================= ADMIN ACCESS =================

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ================= VALIDATE ORDER ID =================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>
        alert('ID pesanan tidak valid!');
        window.location='orders.php';
    </script>";
    exit();
}

$orderId = (int) $_GET['id'];

// ================= GET ORDER =================

$orderStmt = mysqli_prepare(
    $conn,
    "SELECT orders.*, users.fullname, users.email
     FROM orders
     INNER JOIN users ON orders.user_id = users.id
     WHERE orders.id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($orderStmt, "i", $orderId);
mysqli_stmt_execute($orderStmt);

$orderResult = mysqli_stmt_get_result($orderStmt);

if (mysqli_num_rows($orderResult) === 0) {
    mysqli_stmt_close($orderStmt);

    echo "<script>
        alert('Pesanan tidak ditemukan!');
        window.location='orders.php';
    </script>";
    exit();
}

$order = mysqli_fetch_assoc($orderResult);
mysqli_stmt_close($orderStmt);

// ================= GET PAYMENT =================

$paymentStmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM payment_transactions
     WHERE order_id = ?
     ORDER BY id DESC
     LIMIT 1"
);

mysqli_stmt_bind_param($paymentStmt, "i", $orderId);
mysqli_stmt_execute($paymentStmt);

$paymentResult = mysqli_stmt_get_result($paymentStmt);
$payment = mysqli_fetch_assoc($paymentResult);

mysqli_stmt_close($paymentStmt);

// ================= GET SHIPPING =================

$shippingStmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM order_shipping
     WHERE order_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($shippingStmt, "i", $orderId);
mysqli_stmt_execute($shippingStmt);

$shippingResult = mysqli_stmt_get_result($shippingStmt);
$shipping = mysqli_fetch_assoc($shippingResult);

mysqli_stmt_close($shippingStmt);

// ================= PROCESS SHIPPING =================

if (isset($_POST['ship_order'])) {

    $courier = trim($_POST['courier'] ?? '');
    $trackingNumber = trim($_POST['tracking_number'] ?? '');

    if ($order['status'] !== 'Processing') {

        echo "<script>
            alert('Pesanan harus berstatus Processing sebelum dikirim!');
            window.location='order-detail.php?id=$orderId';
        </script>";

        exit();

    } elseif (!$payment || $payment['payment_status'] !== 'Paid') {

        echo "<script>
            alert('Pesanan hanya dapat dikirim setelah pembayaran disetujui!');
            window.location='order-detail.php?id=$orderId';
        </script>";

        exit();

    } elseif ($courier === '' || $trackingNumber === '') {

        echo "<script>
            alert('Kurir dan nomor resi wajib diisi!');
            window.location='order-detail.php?id=$orderId';
        </script>";

        exit();

    } elseif (strlen($courier) > 100 || strlen($trackingNumber) > 150) {

        echo "<script>
            alert('Nama kurir atau nomor resi terlalu panjang!');
            window.location='order-detail.php?id=$orderId';
        </script>";

        exit();

    } elseif ($shipping && $shipping['shipping_status'] !== 'Not Shipped') {

        echo "<script>
            alert('Pesanan ini sudah masuk proses pengiriman!');
            window.location='order-detail.php?id=$orderId';
        </script>";

        exit();

    } else {

        mysqli_begin_transaction($conn);

        try {

            // Simpan atau perbarui data pengiriman

            if ($shipping) {

                $shippingUpdate = mysqli_prepare(
                    $conn,
                    "UPDATE order_shipping
                     SET courier = ?,
                         tracking_number = ?,
                         shipping_status = 'Shipped',
                         shipped_at = NOW(),
                         updated_at = NOW()
                     WHERE order_id = ?
                     AND shipping_status = 'Not Shipped'"
                );

                mysqli_stmt_bind_param(
                    $shippingUpdate,
                    "ssi",
                    $courier,
                    $trackingNumber,
                    $orderId
                );

                mysqli_stmt_execute($shippingUpdate);

                if (mysqli_stmt_affected_rows($shippingUpdate) !== 1) {
                    throw new Exception("Gagal memperbarui data pengiriman.");
                }

                mysqli_stmt_close($shippingUpdate);

            } else {

                $shippingInsert = mysqli_prepare(
                    $conn,
                    "INSERT INTO order_shipping
                    (
                        order_id,
                        courier,
                        tracking_number,
                        shipping_status,
                        shipped_at
                    )
                    VALUES (?, ?, ?, 'Shipped', NOW())"
                );

                mysqli_stmt_bind_param(
                    $shippingInsert,
                    "iss",
                    $orderId,
                    $courier,
                    $trackingNumber
                );

                if (!mysqli_stmt_execute($shippingInsert)) {
                    throw new Exception("Gagal menyimpan data pengiriman.");
                }

                mysqli_stmt_close($shippingInsert);
            }

            // Ubah status pesanan menjadi Shipped

            $orderUpdate = mysqli_prepare(
                $conn,
                "UPDATE orders
                 SET status = 'Shipped'
                 WHERE id = ?
                 AND status = 'Processing'"
            );

            mysqli_stmt_bind_param($orderUpdate, "i", $orderId);
            mysqli_stmt_execute($orderUpdate);

            if (mysqli_stmt_affected_rows($orderUpdate) !== 1) {
                throw new Exception("Gagal mengubah status pesanan menjadi Shipped.");
            }

            mysqli_stmt_close($orderUpdate);

            mysqli_commit($conn);

            echo "<script>
                alert('Pesanan berhasil dikirim! Nomor resi berhasil disimpan.');
                window.location='order-detail.php?id=$orderId';
            </script>";

            exit();

        } catch (Throwable $e) {

            mysqli_rollback($conn);

            echo "<script>
                alert(" . json_encode($e->getMessage()) . ");
                window.location='order-detail.php?id=$orderId';
            </script>";

            exit();
        }
    }
}

// ================= UPDATE ORDER STATUS =================

if (isset($_POST['update_status'])) {

    $status = $_POST['status'] ?? '';

    if (!in_array($status, ['Pending', 'Processing', 'Cancelled'], true)) {

        echo "<script>
            alert('Status pesanan tidak valid!');
        </script>";

    } elseif ($status === 'Processing' && (!$payment || $payment['payment_status'] !== 'Paid')) {

        echo "<script>
            alert('Pesanan hanya dapat diproses setelah pembayaran disetujui!');
        </script>";

    } elseif (in_array($order['status'], ['Shipped', 'Completed'], true)) {

        echo "<script>
            alert('Pesanan yang sudah dikirim tidak dapat diubah melalui menu ini!');
            window.location='order-detail.php?id=$orderId';
        </script>";

        exit();

    } else {

        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE orders SET status = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            "si",
            $status,
            $orderId
        );

        if (mysqli_stmt_execute($updateStmt)) {

            mysqli_stmt_close($updateStmt);

            echo "<script>
                alert('Status pesanan berhasil diperbarui!');
                window.location='order-detail.php?id=$orderId';
            </script>";

            exit();
        }

        mysqli_stmt_close($updateStmt);

        echo "<script>
            alert('Gagal memperbarui status pesanan!');
        </script>";
    }
}

// ================= GET ORDER ITEMS =================

$itemsStmt = mysqli_prepare(
    $conn,
    "SELECT
        order_items.*,
        books.title,
        books.author,
        books.image
     FROM order_items
     INNER JOIN books ON order_items.book_id = books.id
     WHERE order_items.order_id = ?
     ORDER BY order_items.id ASC"
);

mysqli_stmt_bind_param($itemsStmt, "i", $orderId);
mysqli_stmt_execute($itemsStmt);

$itemsResult = mysqli_stmt_get_result($itemsStmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Detail - BookVerse</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

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

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <!-- ADMIN HEADER -->

    <header class="admin-header">

        <div class="admin-header-left">

            <a href="index.php" class="admin-logo">

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

                <a href="orders.php" class="admin-nav-link active">
                    <i class="fa-solid fa-box"></i>
                    <span>Manage Orders</span>
                </a>

                <a href="payment-transactions.php" class="admin-nav-link">
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
                    <i class="fa-solid fa-boxes-stacked"></i>
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
                        ORDER MANAGEMENT
                    </span>

                    <h1>Order Detail</h1>

                    <p>
                        View customer order, payment and shipping information.
                    </p>

                </div>

                <a href="orders.php" class="admin-back-button">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Orders
                </a>

            </div>

            <!-- ORDER INFORMATION -->

            <section class="admin-section">

                <div class="admin-section-header">
                    <div>
                        <span class="admin-section-label">
                            ORDER INFORMATION
                        </span>

                        <h2>
                            <?php echo htmlspecialchars($order['order_number']); ?>
                        </h2>
                    </div>
                </div>

                <div class="admin-order-info-grid">

                    <div class="admin-order-info-card">
                        <span>Customer</span>
                        <strong>
                            <?php echo htmlspecialchars($order['fullname']); ?>
                        </strong>
                    </div>

                    <div class="admin-order-info-card">
                        <span>Email</span>
                        <strong>
                            <?php echo htmlspecialchars($order['email']); ?>
                        </strong>
                    </div>

                    <div class="admin-order-info-card">
                        <span>Recipient</span>
                        <strong>
                            <?php echo htmlspecialchars($order['recipient_name']); ?>
                        </strong>
                    </div>

                    <div class="admin-order-info-card">
                        <span>Phone</span>
                        <strong>
                            <?php echo htmlspecialchars($order['phone']); ?>
                        </strong>
                    </div>

                    <div class="admin-order-info-card">
                        <span>Order Number</span>
                        <strong>
                            #<?php echo htmlspecialchars($order['order_number']); ?>
                        </strong>
                    </div>

                    <div class="admin-order-info-card">
                        <span>Order Date</span>
                        <strong>
                            <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?>
                        </strong>
                    </div>

                    <div class="admin-order-info-card">
                        <span>Order Status</span>
                        <strong>
                            <?php echo htmlspecialchars($order['status']); ?>
                        </strong>
                    </div>

                    <div class="admin-order-info-card">
                        <span>Total Payment</span>
                        <strong class="admin-order-total">
                            Rp<?php echo number_format($order['total_price'], 0, ',', '.'); ?>
                        </strong>
                    </div>

                    <div class="admin-order-info-card admin-order-address-card">
                        <span>Delivery Address</span>
                        <strong>
                            <?php echo nl2br(htmlspecialchars($order['address'])); ?>
                        </strong>
                    </div>

                </div>

            </section>

            <!-- PAYMENT INFORMATION -->

            <section class="admin-section">

                <div class="admin-section-header">
                    <div>
                        <span class="admin-section-label">
                            PAYMENT INFORMATION
                        </span>
                        <h2>Payment Details</h2>
                    </div>
                </div>

                <?php if ($payment): ?>

                    <div class="admin-order-info-grid">

                        <div class="admin-order-info-card">
                            <span>Transaction Code</span>
                            <strong>
                                <?php echo htmlspecialchars($payment['transaction_code']); ?>
                            </strong>
                        </div>

                        <div class="admin-order-info-card">
                            <span>Payment Method</span>
                            <strong>
                                <?php echo htmlspecialchars($payment['payment_method']); ?>
                            </strong>
                        </div>

                        <div class="admin-order-info-card">
                            <span>Provider</span>
                            <strong>
                                <?php echo htmlspecialchars($payment['provider']); ?>
                            </strong>
                        </div>

                        <div class="admin-order-info-card">
                            <span>Payment Status</span>
                            <strong>
                                <?php echo htmlspecialchars($payment['payment_status']); ?>
                            </strong>
                        </div>

                        <div class="admin-order-info-card">
                            <span>Payment Amount</span>
                            <strong class="admin-order-total">
                                Rp<?php echo number_format($payment['payment_amount'], 0, ',', '.'); ?>
                            </strong>
                        </div>

                        <div class="admin-order-info-card">
                            <span>Expiry Date</span>
                            <strong>
                                <?php echo htmlspecialchars($payment['expires_at']); ?>
                            </strong>
                        </div>

                    </div>

                    <?php if (!empty($payment['payment_proof'])): ?>

                        <div style="margin-top:20px;">

                            <a
                                href="../<?php echo htmlspecialchars($payment['payment_proof']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="admin-action-button edit">

                                <i class="fa-solid fa-image"></i>
                                View Payment Proof

                            </a>

                        </div>

                    <?php else: ?>

                        <p>No payment proof uploaded.</p>

                    <?php endif; ?>

                    <?php if (!empty($payment['rejection_reason'])): ?>

                        <div style="margin-top:15px;">
                            <strong>Rejection Reason:</strong>
                            <p>
                                <?php echo htmlspecialchars($payment['rejection_reason']); ?>
                            </p>
                        </div>

                    <?php endif; ?>

                <?php else: ?>

                    <p>No payment transaction found for this order.</p>

                <?php endif; ?>

            </section>

            <!-- SHIPPING INFORMATION -->

            <section class="admin-section">

                <div class="admin-section-header">
                    <div>
                        <span class="admin-section-label">
                            SHIPPING INFORMATION
                        </span>
                        <h2>Delivery Details</h2>
                    </div>
                </div>

                <?php if ($shipping): ?>

                    <div class="admin-order-info-grid">

                        <div class="admin-order-info-card">
                            <span>Courier</span>
                            <strong>
                                <?php echo htmlspecialchars($shipping['courier'] ?: '-'); ?>
                            </strong>
                        </div>

                        <div class="admin-order-info-card">
                            <span>Tracking Number</span>
                            <strong>
                                <?php echo htmlspecialchars($shipping['tracking_number'] ?: '-'); ?>
                            </strong>
                        </div>

                        <div class="admin-order-info-card">
                            <span>Shipping Status</span>
                            <strong>
                                <?php echo htmlspecialchars($shipping['shipping_status']); ?>
                            </strong>
                        </div>

                        <div class="admin-order-info-card">
                            <span>Shipped At</span>
                            <strong>
                                <?php echo htmlspecialchars($shipping['shipped_at'] ?: '-'); ?>
                            </strong>
                        </div>

                        <div class="admin-order-info-card">
                            <span>Received At</span>
                            <strong>
                                <?php echo htmlspecialchars($shipping['received_at'] ?: '-'); ?>
                            </strong>
                        </div>

                    </div>

                <?php else: ?>

                    <p>Shipping information has not been added yet.</p>

                <?php endif; ?>

                <!-- SHIPPING FORM -->

                <?php if (
                    $order['status'] === 'Processing' &&
                    $payment &&
                    $payment['payment_status'] === 'Paid' &&
                    (!$shipping || $shipping['shipping_status'] === 'Not Shipped')
                ): ?>

                    <form method="POST" class="admin-order-status-form">

                        <div class="admin-status-select">

                            <label for="courier">Select Courier</label>

                            <select id="courier" name="courier" required>

                                <option value="">-- Select Courier --</option>
                                <option value="JNE">JNE</option>
                                <option value="J&T Express">J&T Express</option>
                                <option value="SiCepat">SiCepat</option>
                                <option value="AnterAja">AnterAja</option>
                                <option value="Pos Indonesia">Pos Indonesia</option>
                                <option value="ID Express">ID Express</option>
                                <option value="Other">Other</option>

                            </select>

                        </div>

                        <div class="admin-status-select">

                            <label for="tracking_number">Tracking Number / Resi</label>

                            <input
                                type="text"
                                id="tracking_number"
                                name="tracking_number"
                                placeholder="Enter tracking number..."
                                maxlength="150"
                                required>

                        </div>

                        <button
                            type="submit"
                            name="ship_order"
                            class="admin-form-submit"
                            onclick="return confirm('Kirim pesanan ini sekarang?')">

                            <i class="fa-solid fa-truck-fast"></i>
                            Kirim Pesanan

                        </button>

                    </form>

                <?php elseif ($order['status'] === 'Processing' && (!$payment || $payment['payment_status'] !== 'Paid')): ?>

                    <p>Pesanan belum dapat dikirim karena pembayaran belum disetujui.</p>

                <?php elseif ($order['status'] === 'Shipped'): ?>

                    <p>Pesanan sudah dikirim. Menunggu konfirmasi penerimaan dari User.</p>

                <?php elseif ($order['status'] === 'Completed'): ?>

                    <p>Pesanan sudah selesai dan diterima oleh User.</p>

                <?php endif; ?>

            </section>

            <!-- ORDER ITEMS -->

            <section class="admin-section">

                <div class="admin-section-header">
                    <div>
                        <span class="admin-section-label">
                            ORDER ITEMS
                        </span>
                        <h2>Books Ordered</h2>
                    </div>
                </div>

                <div class="admin-order-items">

                    <?php if (mysqli_num_rows($itemsResult) > 0): ?>

                        <?php while ($item = mysqli_fetch_assoc($itemsResult)): ?>

                            <div class="admin-order-item">

                                <img
                                    src="../<?php echo htmlspecialchars($item['image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['title']); ?>"
                                    class="admin-order-item-cover">

                                <div class="admin-order-item-info">

                                    <strong>
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </strong>

                                    <span>
                                        <?php echo htmlspecialchars($item['author']); ?>
                                    </span>

                                </div>

                                <div class="admin-order-item-price">

                                    <span>
                                        <?php echo (int) $item['quantity']; ?> ×
                                    </span>

                                    <strong>
                                        Rp<?php echo number_format($item['price'], 0, ',', '.'); ?>
                                    </strong>

                                </div>

                            </div>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <div class="admin-empty-books">
                            <i class="fa-solid fa-book-open"></i>
                            <strong>No items found</strong>
                            <span>This order does not contain any books.</span>
                        </div>

                    <?php endif; ?>

                </div>

            </section>

            <!-- UPDATE ORDER STATUS -->

            <section class="admin-section">

                <div class="admin-section-header">
                    <div>
                        <span class="admin-section-label">
                            ORDER STATUS
                        </span>
                        <h2>Update Status</h2>
                    </div>
                </div>

                <?php if (in_array($order['status'], ['Shipped', 'Completed'], true)): ?>

                    <p>
                        Status pengiriman dan penyelesaian pesanan dikelola melalui fitur Shipping dan konfirmasi penerimaan User.
                    </p>

                <?php else: ?>

                    <form method="POST" class="admin-order-status-form">

                        <div class="admin-status-select">

                            <label for="status">Current Status</label>

                            <select id="status" name="status" required>

                                <option
                                    value="Pending"
                                    <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>
                                    Pending
                                </option>

                                <?php if ($payment && $payment['payment_status'] === 'Paid'): ?>

                                    <option
                                        value="Processing"
                                        <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>
                                        Processing
                                    </option>

                                <?php endif; ?>

                                <?php if (!$payment || $payment['payment_status'] !== 'Paid'): ?>

                                    <option
                                        value="Cancelled"
                                        <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>
                                        Cancelled
                                    </option>

                                <?php endif; ?>

                            </select>

                        </div>

                        <button
                            type="submit"
                            name="update_status"
                            class="admin-form-submit">

                            <i class="fa-solid fa-floppy-disk"></i>
                            Update Status

                        </button>

                    </form>

                <?php endif; ?>

            </section>

        </main>

    </div>

</body>

</html>

<?php
mysqli_stmt_close($itemsStmt);
?>