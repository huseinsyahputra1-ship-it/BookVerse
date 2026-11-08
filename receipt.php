
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
*/

$orderStmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        user_id,
        recipient_name,
        phone,
        address,
        payment_method,
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
| Ambil Item Pesanan
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
| Format Data
|--------------------------------------------------------------------------
*/

$paymentLabels = [
    'bank_transfer' => 'Bank Transfer',
    'e_wallet' => 'E-Wallet',
    'cod' => 'Cash on Delivery'
];

$paymentMethod = $paymentLabels[
    $order['payment_method'] ?? ''
] ?? 'Unknown';

$orderDate = date(
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
        Order Receipt | BookVerse
    </title>

    <!-- Google Font -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Receipt CSS -->

    <link
        rel="stylesheet"
        href="assets/css/receipt.css">

</head>

<body>

    <main class="receipt-page">

        <div class="receipt-container">

            <!-- ACTION BUTTONS -->

            <div class="receipt-actions">

                <a
                    href="order-detail.php?id=<?php echo (int) $order['id']; ?>"
                    class="receipt-back-btn">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Order

                </a>

                <button
                    type="button"
                    onclick="window.print()"
                    class="receipt-print-btn">

                    <i class="fa-solid fa-print"></i>

                    Print / Save PDF

                </button>

            </div>


            <!-- RECEIPT PAPER -->

            <section class="receipt-paper">

                <!-- HEADER -->

                <div class="receipt-header">

                    <div class="receipt-logo">

                        <img
                            src="assets/img/hugeicons_book-open-02.png"
                            alt="BookVerse">

                        <div>

                            <h1>
                                BookVerse
                            </h1>

                            <span>
                                Every Book Has a Story
                            </span>

                        </div>

                    </div>

                    <div class="receipt-title">

                        <h2>
                            ORDER RECEIPT
                        </h2>

                        <span>
                            Payment Summary
                        </span>

                    </div>

                </div>


                <div class="receipt-divider"></div>


                <!-- SUCCESS MESSAGE -->

                <div class="receipt-success">

                    <div class="receipt-success-icon">

                        <i class="fa-solid fa-check"></i>

                    </div>

                    <h2>
                        Order Successfully Created!
                    </h2>

                    <p>
                        Thank you for shopping with BookVerse.
                    </p>

                </div>


                <!-- ORDER INFORMATION -->

                <div class="receipt-section">

                    <h3>
                        Order Information
                    </h3>

                    <div class="receipt-info-grid">

                        <div class="receipt-info-item">

                            <span>
                                Order Number
                            </span>

                            <strong>
                                #<?php
                                echo htmlspecialchars(
                                    $order['order_number']
                                );
                                ?>
                            </strong>

                        </div>

                        <div class="receipt-info-item">

                            <span>
                                Order Date
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars($orderDate);
                                ?>
                            </strong>

                        </div>

                        <div class="receipt-info-item">

                            <span>
                                Payment Method
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars($paymentMethod);
                                ?>
                            </strong>

                        </div>

                        <div class="receipt-info-item">

                            <span>
                                Order Status
                            </span>

                            <strong class="receipt-status">
                                <?php
                                echo htmlspecialchars($order['status']);
                                ?>
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- CUSTOMER INFORMATION -->

                <div class="receipt-section">

                    <h3>
                        Shipping Information
                    </h3>

                    <div class="receipt-customer">

                        <div>

                            <span>
                                Recipient Name
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $order['recipient_name']
                                );
                                ?>
                            </strong>

                        </div>

                        <div>

                            <span>
                                Phone Number
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $order['phone']
                                );
                                ?>
                            </strong>

                        </div>

                        <div class="receipt-address">

                            <span>
                                Shipping Address
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

                </div>


                <!-- PURCHASED BOOKS -->

                <div class="receipt-section">

                    <h3>
                        Purchased Books
                    </h3>

                    <div class="receipt-table-wrapper">

                        <table class="receipt-table">

                            <thead>

                                <tr>

                                    <th>
                                        Book
                                    </th>

                                    <th>
                                        Price
                                    </th>

                                    <th>
                                        Qty
                                    </th>

                                    <th>
                                        Subtotal
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($orderItems as $item): ?>

                                    <tr>

                                        <td>

                                            <div class="receipt-book">

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

                                        </td>

                                        <td>

                                            Rp<?php
                                            echo number_format(
                                                $item['price'],
                                                0,
                                                ',',
                                                '.'
                                            );
                                            ?>

                                        </td>

                                        <td>
                                            <?php
                                            echo (int) $item['quantity'];
                                            ?>
                                        </td>

                                        <td>

                                            Rp<?php
                                            echo number_format(
                                                $item['subtotal'],
                                                0,
                                                ',',
                                                '.'
                                            );
                                            ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- TOTAL -->

                <div class="receipt-total-section">

                    <div class="receipt-total-row">

                        <span>
                            Delivery
                        </span>

                        <strong>
                            Free
                        </strong>

                    </div>

                    <div class="receipt-total-row grand-total">

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


                <!-- FOOTER -->

                <div class="receipt-footer">

                    <i class="fa-solid fa-book-open"></i>

                    <h3>
                        Thank You for Choosing BookVerse!
                    </h3>

                    <p>
                        Every Book Has a Story. Happy Reading!
                    </p>

                    <span>
                        © 2026 BookVerse. All Rights Reserved.
                    </span>

                </div>

            </section>

        </div>

    </main>

</body>

</html>
