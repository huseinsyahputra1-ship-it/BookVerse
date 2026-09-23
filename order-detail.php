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
    exit();
}

/*
|--------------------------------------------------------------------------
| Validasi Order ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$orderId = (int) $_GET['id'];
$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Ambil Data Order
|--------------------------------------------------------------------------
|
| Order hanya bisa dilihat oleh user yang membuat order tersebut.
|
*/

$orderStmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        user_id,
        recipient_name,
        phone,
        address,
        order_number,
        total_price,
        status,
        created_at
     FROM orders
     WHERE id = ?
     AND user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $orderStmt,
    "ii",
    $orderId,
    $userId
);

mysqli_stmt_execute($orderStmt);

$orderResult = mysqli_stmt_get_result($orderStmt);

$order = mysqli_fetch_assoc($orderResult);

mysqli_stmt_close($orderStmt);

/*
|--------------------------------------------------------------------------
| Jika Order Tidak Ditemukan
|--------------------------------------------------------------------------
*/

if (!$order) {
    header("Location: orders.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Ambil Detail Item Order
|--------------------------------------------------------------------------
*/

$itemStmt = mysqli_prepare(
    $conn,
    "SELECT
        oi.id,
        oi.book_id,
        oi.quantity,
        oi.price,
        b.title,
        b.author,
        b.image
     FROM order_items oi
     INNER JOIN books b
        ON oi.book_id = b.id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC"
);

mysqli_stmt_bind_param(
    $itemStmt,
    "i",
    $orderId
);

mysqli_stmt_execute($itemStmt);

$itemResult = mysqli_stmt_get_result($itemStmt);

$orderItems = [];

while ($item = mysqli_fetch_assoc($itemResult)) {

    $item['subtotal'] =
        (float) $item['price'] *
        (int) $item['quantity'];

    $orderItems[] = $item;
}

mysqli_stmt_close($itemStmt);

/*
|--------------------------------------------------------------------------
| Status Pesanan
|--------------------------------------------------------------------------
*/

$statusSteps = [
    'Pending',
    'Processing',
    'Shipped',
    'Completed'
];

$currentStatus = $order['status'];

$currentStatusIndex = array_search(
    $currentStatus,
    $statusSteps,
    true
);

if ($currentStatusIndex === false) {
    $currentStatusIndex = 0;
}

/*
|--------------------------------------------------------------------------
| Format Tanggal
|--------------------------------------------------------------------------
*/

$orderDate = date(
    'd M Y, H:i',
    strtotime($order['created_at'])
);

/*
|--------------------------------------------------------------------------
| Data User
|--------------------------------------------------------------------------
*/

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

    <title>
        Order Detail | BookVerse
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

    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <style>

        /* =========================================================
           ORDER DETAIL PAGE
           ========================================================= */

        .order-detail-page {
            padding: 55px 0 80px;
            background: #faf9ff;
            min-height: calc(100vh - 100px);
        }

        .order-detail-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ================= PAGE HEADER ================= */

        .order-detail-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .order-detail-heading {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .order-detail-heading span {
            color: #8a8495;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.2px;
        }

        .order-detail-heading h1 {
            margin: 0;
            color: #292333;
            font-size: 30px;
            font-weight: 700;
        }

        .order-detail-heading p {
            margin: 0;
            color: #777181;
            font-size: 13px;
        }

        .order-detail-back {
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 18px;
            border: 2px solid #6c3ef4;
            border-radius: 12px;
            background: #ffffff;
            color: #6c3ef4;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s ease;
            white-space: nowrap;
        }

        .order-detail-back:hover {
            background: #6c3ef4;
            color: #ffffff;
        }

        /* ================= STATUS CARD ================= */

        .order-status-card {
            margin-bottom: 25px;
            padding: 28px 30px;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(76, 61, 125, 0.07);
        }

        .order-status-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .order-number-box {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .order-number-box span {
            color: #8a8495;
            font-size: 11px;
        }

        .order-number-box strong {
            color: #292333;
            font-size: 17px;
        }

        .order-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: 999px;
            background: #f0ebff;
            color: #6c3ef4;
            font-size: 11px;
            font-weight: 600;
        }

        /* ================= STATUS TIMELINE ================= */

        .order-timeline {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            position: relative;
        }

        .order-timeline::before {
            content: "";
            position: absolute;
            top: 17px;
            left: 12.5%;
            right: 12.5%;
            height: 2px;
            background: #e8e3f2;
            z-index: 0;
        }

        .timeline-item {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 9px;
        }

        .timeline-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f1eef7;
            color: #a19baa;
            font-size: 13px;
            border: 3px solid #ffffff;
        }

        .timeline-item.active .timeline-icon {
            background: #6c3ef4;
            color: #ffffff;
        }

        .timeline-item.current .timeline-icon {
            box-shadow: 0 0 0 5px rgba(108, 62, 244, 0.10);
        }

        .timeline-item strong {
            color: #777181;
            font-size: 11px;
            font-weight: 600;
        }

        .timeline-item.active strong {
            color: #443c52;
        }

        /* ================= MAIN GRID ================= */

        .order-detail-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
            align-items: start;
        }

        .order-detail-main {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .order-detail-card {
            padding: 28px;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(76, 61, 125, 0.06);
        }

        .order-detail-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 22px;
        }

        .order-detail-card-header h2 {
            margin: 0;
            color: #292333;
            font-size: 18px;
            font-weight: 600;
        }

        .order-detail-card-header span {
            color: #8a8495;
            font-size: 11px;
        }

        /* ================= ORDER ITEMS ================= */

        .order-detail-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .order-detail-item {
            display: grid;
            grid-template-columns: 72px 1fr auto;
            gap: 15px;
            align-items: center;
            padding: 15px;
            border: 1px solid #ece9f3;
            border-radius: 15px;
        }

        .order-detail-item-image {
            width: 72px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 10px;
            background: #f5f2ff;
        }

        .order-detail-item-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 7px;
        }

        .order-detail-item-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .order-detail-item-info h3 {
            margin: 0;
            color: #292333;
            font-size: 14px;
            font-weight: 600;
        }

        .order-detail-item-info p {
            margin: 0;
            color: #777181;
            font-size: 11px;
        }

        .order-detail-item-info span {
            color: #6c3ef4;
            font-size: 12px;
            font-weight: 600;
        }

        .order-detail-item-total {
            color: #292333;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* ================= SHIPPING ================= */

        .customer-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .customer-info-box {
            padding: 17px;
            border-radius: 14px;
            background: #faf9ff;
        }

        .customer-info-box.full {
            grid-column: 1 / -1;
        }

        .customer-info-box span {
            display: block;
            margin-bottom: 6px;
            color: #8a8495;
            font-size: 10px;
            font-weight: 500;
        }

        .customer-info-box strong {
            display: block;
            color: #443c52;
            font-size: 12px;
            line-height: 1.6;
        }

        /* ================= SUMMARY ================= */

        .order-summary-card {
            position: sticky;
            top: 25px;
            padding: 28px;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(76, 61, 125, 0.07);
        }

        .order-summary-card h2 {
            margin: 0 0 22px;
            color: #292333;
            font-size: 19px;
            font-weight: 600;
        }

        .summary-detail-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 14px;
            color: #777181;
            font-size: 12px;
        }

        .summary-detail-row strong {
            color: #443c52;
        }

        .order-summary-card hr {
            border: none;
            height: 1px;
            margin: 20px 0;
            background: #ece9f3;
        }

        .summary-total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .summary-total-row span {
            color: #292333;
            font-size: 14px;
            font-weight: 600;
        }

        .summary-total-row strong {
            color: #6c3ef4;
            font-size: 20px;
            font-weight: 700;
        }

        .payment-simulation {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 22px;
            padding: 13px;
            border-radius: 12px;
            background: #f7f4ff;
        }

        .payment-simulation i {
            color: #6c3ef4;
            font-size: 15px;
        }

        .payment-simulation span {
            color: #777181;
            font-size: 10px;
            line-height: 1.5;
        }

        .order-summary-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 22px;
        }

        .order-summary-btn {
            width: 100%;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 11px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s ease;
            box-sizing: border-box;
        }

        .order-summary-btn-primary {
            background: #6c3ef4;
            color: #ffffff;
        }

        .order-summary-btn-primary:hover {
            background: #5530d9;
        }

        .order-summary-btn-secondary {
            border: 2px solid #6c3ef4;
            background: #ffffff;
            color: #6c3ef4;
        }

        .order-summary-btn-secondary:hover {
            background: #6c3ef4;
            color: #ffffff;
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 900px) {

            .order-detail-grid {
                grid-template-columns: 1fr;
            }

            .order-summary-card {
                position: static;
            }

        }

        @media (max-width: 700px) {

            .order-detail-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .order-status-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .order-timeline {
                gap: 5px;
            }

            .order-timeline::before {
                left: 8%;
                right: 8%;
            }

            .customer-info {
                grid-template-columns: 1fr;
            }

            .customer-info-box.full {
                grid-column: auto;
            }

        }

        @media (max-width: 520px) {

            .order-detail-page {
                padding-top: 35px;
            }

            .order-detail-card,
            .order-summary-card,
            .order-status-card {
                padding: 20px;
            }

            .order-detail-item {
                grid-template-columns: 58px 1fr;
            }

            .order-detail-item-image {
                width: 58px;
                height: 75px;
            }

            .order-detail-item-total {
                grid-column: 2;
            }

            .timeline-item strong {
                font-size: 9px;
            }

        }

    </style>

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

                        <h2>
                            BookVerse
                        </h2>

                        <span>
                            Every Book Has a Story
                        </span>

                    </div>

                </a>

                <!-- BACK TO ORDERS -->

                <a
                    href="orders.php"
                    class="profile-back">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Orders

                </a>

            </div>

        </div>

    </header>


    <!-- ================= ORDER DETAIL ================= -->

    <main class="order-detail-page">

        <div class="container">

            <div class="order-detail-container">

                <!-- ================= PAGE HEADER ================= -->

                <div class="order-detail-top">

                    <div class="order-detail-heading">

                        <span>
                            ORDER DETAILS
                        </span>

                        <h1>
                            Order Details
                        </h1>

                        <p>
                            Review your BookVerse order information.
                        </p>

                    </div>

                    <a
                        href="orders.php"
                        class="order-detail-back">

                        <i class="fa-solid fa-arrow-left"></i>

                        Kembali ke Pesanan

                    </a>

                </div>


                <!-- ================= STATUS ================= -->

                <section class="order-status-card">

                    <div class="order-status-header">

                        <div class="order-number-box">

                            <span>
                                ORDER NUMBER
                            </span>

                            <strong>
                                #<?php
                                echo htmlspecialchars(
                                    $order['order_number']
                                );
                                ?>
                            </strong>

                        </div>

                        <span class="order-status-badge">

                            <i class="fa-solid fa-circle"></i>

                            <?php
                            echo htmlspecialchars(
                                $order['status']
                            );
                            ?>

                        </span>

                    </div>


                    <!-- STATUS TIMELINE -->

                    <div class="order-timeline">

                        <?php foreach (
                            $statusSteps as $index => $status
                        ): ?>

                            <?php
                            $isActive =
                                $index <= $currentStatusIndex;

                            $isCurrent =
                                $index === $currentStatusIndex;
                            ?>

                            <div
                                class="timeline-item
                                <?php echo $isActive ? 'active' : ''; ?>
                                <?php echo $isCurrent ? 'current' : ''; ?>">

                                <div class="timeline-icon">

                                    <?php if ($isActive): ?>

                                        <i class="fa-solid fa-check"></i>

                                    <?php else: ?>

                                        <i class="fa-solid fa-circle"></i>

                                    <?php endif; ?>

                                </div>

                                <strong>
                                    <?php echo $status; ?>
                                </strong>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </section>


                <!-- ================= MAIN CONTENT ================= -->

                <div class="order-detail-grid">

                    <!-- ================= LEFT ================= -->

                    <div class="order-detail-main">

                        <!-- ORDER ITEMS -->

                        <section class="order-detail-card">

                            <div class="order-detail-card-header">

                                <h2>
                                    Order Items
                                </h2>

                                <span>
                                    <?php
                                    echo count($orderItems);
                                    ?>
                                    item(s)
                                </span>

                            </div>

                            <div class="order-detail-items">

                                <?php foreach ($orderItems as $item): ?>

                                    <div class="order-detail-item">

                                        <div class="order-detail-item-image">

                                            <img
                                                src="<?php
                                                echo htmlspecialchars(
                                                    $item['image']
                                                );
                                                ?>"
                                                alt="<?php
                                                echo htmlspecialchars(
                                                    $item['title']
                                                );
                                                ?>">

                                        </div>

                                        <div class="order-detail-item-info">

                                            <h3>
                                                <?php
                                                echo htmlspecialchars(
                                                    $item['title']
                                                );
                                                ?>
                                            </h3>

                                            <p>
                                                <?php
                                                echo htmlspecialchars(
                                                    $item['author']
                                                );
                                                ?>
                                            </p>

                                            <span>
                                                <?php
                                                echo $item['quantity'];
                                                ?>
                                                ×
                                                Rp<?php
                                                echo number_format(
                                                    $item['price'],
                                                    0,
                                                    ',',
                                                    '.'
                                                );
                                                ?>
                                            </span>

                                        </div>

                                        <div class="order-detail-item-total">

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

                        </section>


                        <!-- CUSTOMER INFORMATION -->

                        <section class="order-detail-card">

                            <div class="order-detail-card-header">

                                <h2>
                                    Shipping Information
                                </h2>

                                <span>
                                    Delivery Details
                                </span>

                            </div>

                            <div class="customer-info">

                                <div class="customer-info-box">

                                    <span>
                                        RECIPIENT NAME
                                    </span>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $order['recipient_name']
                                        );
                                        ?>
                                    </strong>

                                </div>

                                <div class="customer-info-box">

                                    <span>
                                        PHONE NUMBER
                                    </span>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $order['phone']
                                        );
                                        ?>
                                    </strong>

                                </div>

                                <div class="customer-info-box full">

                                    <span>
                                        SHIPPING ADDRESS
                                    </span>

                                    <strong>
                                        <?php
                                        echo nl2br(
                                            htmlspecialchars(
                                                $order['address']
                                            )
                                        );
                                        ?>
                                    </strong>

                                </div>

                            </div>

                        </section>

                    </div>


                    <!-- ================= RIGHT ================= -->

                    <aside>

                        <div class="order-summary-card">

                            <h2>
                                Order Summary
                            </h2>

                            <div class="summary-detail-row">

                                <span>
                                    Order Date
                                </span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $orderDate
                                    );
                                    ?>
                                </strong>

                            </div>

                            <div class="summary-detail-row">

                                <span>
                                    Payment
                                </span>

                                <strong>
                                    Payment Simulation
                                </strong>

                            </div>

                            <div class="summary-detail-row">

                                <span>
                                    Delivery
                                </span>

                                <strong>
                                    Free
                                </strong>

                            </div>

                            <hr>

                            <div class="summary-total-row">

                                <span>
                                    Total
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

                            <div class="payment-simulation">

                                <i class="fa-solid fa-shield-halved"></i>

                                <span>
                                    This order uses BookVerse's
                                    simulated payment system
                                    for educational purposes.
                                </span>

                            </div>

                            <div class="order-summary-actions">

                                <a
                                    href="orders.php"
                                    class="order-summary-btn order-summary-btn-primary">

                                    <i class="fa-solid fa-arrow-left"></i>

                                    Back to My Orders

                                </a>

                                <a
                                    href="index.php"
                                    class="order-summary-btn order-summary-btn-secondary">

                                    <i class="fa-solid fa-book"></i>

                                    Continue Shopping

                                </a>

                            </div>

                        </div>

                    </aside>

                </div>

            </div>

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