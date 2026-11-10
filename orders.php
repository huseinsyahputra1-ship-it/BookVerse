
<?php

require_once __DIR__ . '/config/user-session.php';
require_once __DIR__ . '/config/database.php';

// =========================================================
// USER ACCESS
// =========================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];

// =========================================================
// AMBIL DATA ORDERS DAN PAYMENT USER
// =========================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        o.*,
        pt.payment_status,
        pt.provider,
        pt.payment_proof
    FROM orders o
    LEFT JOIN payment_transactions pt
        ON pt.order_id = o.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC"
);

if (!$stmt) {
    die("Gagal mengambil data pesanan.");
}

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

$query = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>My Orders | BookVerse</title>

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

    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <link
        rel="stylesheet"
        href="assets/css/orders.css">

</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="header">

        <div class="container">

            <div class="profile-navbar">

                <!-- LOGO -->

                <a
                    href="index.php"
                    class="profile-logo">

                    <img
                        src="assets/img/hugeicons_book-open-02.png"
                        alt="BookVerse">

                    <div>

                        <h2>BookVerse</h2>

                        <span>Every Book Has a Story</span>

                    </div>

                </a>

                <!-- BACK HOME -->

                <a
                    href="index.php"
                    class="profile-back">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Home

                </a>

            </div>

        </div>

    </header>


    <!-- ================= ORDERS PAGE ================= -->

    <main class="orders-page">

        <div class="container">

            <!-- PAGE TITLE -->

            <div class="orders-title">

                <span>MY ACCOUNT</span>

                <h1>My Orders</h1>

                <p>
                    Track and manage all your BookVerse orders.
                </p>

            </div>


            <?php if (mysqli_num_rows($query) > 0): ?>

                <!-- ================= ORDER LIST ================= -->

                <div class="orders-list">

                    <?php while ($order = mysqli_fetch_assoc($query)): ?>

                        <?php
                        $paymentStatus = $order['payment_status'] ?? 'Pending';
                        ?>

                        <article class="order-card">

                            <!-- ORDER HEADER -->

                            <div class="order-header">

                                <div>

                                    <span class="order-label">
                                        ORDER NUMBER
                                    </span>

                                    <h2>
                                        #<?php echo htmlspecialchars(
                                            $order['order_number']
                                        ); ?>
                                    </h2>

                                </div>

                                <span
                                    class="order-status status-<?php echo strtolower(
                                        $order['status']
                                    ); ?>">

                                    <?php echo htmlspecialchars(
                                        $order['status']
                                    ); ?>

                                </span>

                            </div>


                            <!-- ORDER CONTENT -->

                            <div class="order-content">

                                <div class="order-icon">

                                    <i class="fa-solid fa-book-open"></i>

                                </div>

                                <div class="order-description">

                                    <strong>
                                        BookVerse Order
                                    </strong>

                                    <span>
                                        Order placed on
                                        <?php echo date(
                                            'd M Y, H:i',
                                            strtotime($order['created_at'])
                                        ); ?>
                                    </span>

                                </div>

                            </div>


                            <!-- PAYMENT STATUS -->

                            <div class="order-payment-info">

                                <span>
                                    Payment Status
                                </span>

                                <strong class="payment-status payment-<?php echo strtolower(
                                    str_replace(' ', '-', $paymentStatus)
                                ); ?>">

                                    <?php echo htmlspecialchars($paymentStatus); ?>

                                </strong>

                            </div>


                            <!-- ORDER FOOTER -->

                            <div class="order-footer">

                                <div class="order-total">

                                    <span>
                                        Total Payment
                                    </span>

                                    <strong>
                                        Rp<?php echo number_format(
                                            $order['total_price'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>
                                    </strong>

                                </div>


                                <div class="order-actions">

                                    <?php if (
                                        $paymentStatus === 'Pending' &&
                                        $order['status'] === 'Pending'
                                    ): ?>

                                        <a
                                            href="payment.php?order_id=<?php echo (int) $order['id']; ?>"
                                            class="continue-payment-btn">

                                            <i class="fa-solid fa-credit-card"></i>

                                            Lanjutkan Pembayaran

                                        </a>

                                    <?php endif; ?>


                                    <a
                                        href="order-detail.php?id=<?php echo (int) $order['id']; ?>"
                                        class="order-detail-btn">

                                        View Details

                                        <i class="fa-solid fa-arrow-right"></i>

                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <!-- ================= EMPTY ORDERS ================= -->

                <div class="orders-empty">

                    <div class="orders-empty-icon">

                        <i class="fa-solid fa-box-open"></i>

                    </div>

                    <h2>No Orders Yet</h2>

                    <p>
                        You haven't placed any orders yet.
                        Start exploring our books and find
                        your next favorite story.
                    </p>

                    <a
                        href="index.php"
                        class="orders-shop-btn">

                        <i class="fa-solid fa-book"></i>

                        Explore Books

                    </a>

                </div>

            <?php endif; ?>

        </div>

    </main>


    <!-- ================= FOOTER ================= -->

    <footer class="footer orders-footer">

        <div class="container">

            <div class="footer-bottom">

                <p>
                    © 2026 BookVerse. All Rights Reserved.
                </p>

            </div>

        </div>

    </footer>

</body>

</html>

<?php

mysqli_stmt_close($stmt);

?>
