<?php

session_start();

include "../config/database.php";

// =========================================================
// ADMIN ACCESS
// =========================================================

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");

    exit();

}

if ($_SESSION['role'] !== 'admin') {

    echo "<script>
        alert('Akses ditolak! Halaman ini hanya untuk admin.');
        window.location='../index.php';
    </script>";

    exit();

}

// =========================================================
// GET ORDER ID
// =========================================================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    echo "<script>
        alert('ID pesanan tidak valid!');
        window.location='orders.php';
    </script>";

    exit();

}

$orderId = (int) $_GET['id'];

// =========================================================
// GET ORDER DATA
// =========================================================

$orderStmt = mysqli_prepare(
    $conn,
    "SELECT
        orders.*,
        users.fullname,
        users.email
     FROM orders
     INNER JOIN users
        ON orders.user_id = users.id
     WHERE orders.id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $orderStmt,
    "i",
    $orderId
);

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

// =========================================================
// UPDATE ORDER STATUS
// =========================================================

if (isset($_POST['update_status'])) {

    $status = $_POST['status'];

    $allowedStatuses = [
        'Pending',
        'Processing',
        'Shipped',
        'Completed',
        'Cancelled'
    ];

    if (!in_array($status, $allowedStatuses, true)) {

        echo "<script>
            alert('Status pesanan tidak valid!');
        </script>";

    } else {

        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE orders
             SET status = ?
             WHERE id = ?"
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

// =========================================================
// GET ORDER ITEMS
// =========================================================

$itemsStmt = mysqli_prepare(
    $conn,
    "SELECT
        order_items.*,
        books.title,
        books.author,
        books.image
     FROM order_items
     INNER JOIN books
        ON order_items.book_id = books.id
     WHERE order_items.order_id = ?
     ORDER BY order_items.id ASC"
);

mysqli_stmt_bind_param(
    $itemsStmt,
    "i",
    $orderId
);

mysqli_stmt_execute($itemsStmt);

$itemsResult = mysqli_stmt_get_result($itemsStmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Order Detail - BookVerse
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
        href="../assets/css/style.css">

</head>


<body>


    <!-- =====================================================
         ADMIN HEADER
         ===================================================== -->

    <header class="admin-header">

        <div class="admin-header-left">

            <a
                href="index.php"
                class="admin-logo">

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


            <a
                href="../logout.php"
                class="admin-logout">

                <i class="fa-solid fa-right-from-bracket"></i>

                Logout

            </a>

        </div>

    </header>


    <!-- =====================================================
         ADMIN LAYOUT
         ===================================================== -->

    <div class="admin-layout">


        <!-- =================================================
             SIDEBAR
             ================================================= -->

        <aside class="admin-sidebar">

            <nav class="admin-nav">

                <a
                    href="index.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-chart-line"></i>

                    <span>Dashboard</span>

                </a>


                <a
                    href="books.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-book"></i>

                    <span>Manage Books</span>

                </a>


                <a
                    href="orders.php"
                    class="admin-nav-link active">

                    <i class="fa-solid fa-box"></i>

                    <span>Manage Orders</span>

                </a>


                <a
                    href="users.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-users"></i>

                    <span>Manage Users</span>

                </a>

            </nav>


            <div class="admin-sidebar-bottom">

                <a
                    href="../index.php"
                    class="admin-back-home">

                    <i class="fa-solid fa-house"></i>

                    <span>Back to Store</span>

                </a>

            </div>

        </aside>


        <!-- =================================================
             MAIN CONTENT
             ================================================= -->

        <main class="admin-main">


            <!-- =================================================
                 PAGE HEADER
                 ================================================= -->

            <div class="admin-page-header">

                <div>

                    <span class="admin-page-label">
                        ORDER MANAGEMENT
                    </span>

                    <h1>
                        Order Detail
                    </h1>

                    <p>
                        View customer order information and manage its status.
                    </p>

                </div>


                <a
                    href="orders.php"
                    class="admin-back-button">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Orders

                </a>

            </div>


            <!-- =================================================
                 ORDER INFORMATION
                 ================================================= -->

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


                    <!-- CUSTOMER -->

                    <div class="admin-order-info-card">

                        <span>
                            Customer
                        </span>

                        <strong>
                            <?php echo htmlspecialchars($order['fullname']); ?>
                        </strong>

                    </div>


                    <!-- EMAIL -->

                    <div class="admin-order-info-card">

                        <span>
                            Email
                        </span>

                        <strong>
                            <?php echo htmlspecialchars($order['email']); ?>
                        </strong>

                    </div>


                    <!-- DATE -->

                    <div class="admin-order-info-card">

                        <span>
                            Order Date
                        </span>

                        <strong>
                            <?php echo date(
                                'd M Y, H:i',
                                strtotime($order['created_at'])
                            ); ?>
                        </strong>

                    </div>


                    <!-- TOTAL -->

                    <div class="admin-order-info-card">

                        <span>
                            Total
                        </span>

                        <strong class="admin-order-total">

                            Rp<?php echo number_format(
                                $order['total_price'],
                                0,
                                ',',
                                '.'
                            ); ?>

                        </strong>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 ORDER ITEMS
                 ================================================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            ORDER ITEMS
                        </span>

                        <h2>
                            Books Ordered
                        </h2>

                    </div>

                </div>


                <div class="admin-order-items">

                    <?php if (mysqli_num_rows($itemsResult) > 0): ?>

                        <?php while ($item = mysqli_fetch_assoc($itemsResult)): ?>

                            <div class="admin-order-item">


                                <!-- BOOK COVER -->

                                <img
                                    src="../<?php echo htmlspecialchars($item['image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['title']); ?>"
                                    class="admin-order-item-cover">


                                <!-- BOOK INFORMATION -->

                                <div class="admin-order-item-info">

                                    <strong>
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </strong>

                                    <span>
                                        <?php echo htmlspecialchars($item['author']); ?>
                                    </span>

                                </div>


                                <!-- PRICE -->

                                <div class="admin-order-item-price">

                                    <span>
                                        <?php echo $item['quantity']; ?> ×
                                    </span>

                                    <strong>

                                        Rp<?php echo number_format(
                                            $item['price'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>

                                    </strong>

                                </div>


                            </div>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <div class="admin-empty-books">

                            <i class="fa-solid fa-book-open"></i>

                            <strong>
                                No items found
                            </strong>

                            <span>
                                This order does not contain any books.
                            </span>

                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <!-- =================================================
                 UPDATE STATUS
                 ================================================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            ORDER STATUS
                        </span>

                        <h2>
                            Update Status
                        </h2>

                    </div>

                </div>


                <form
                    method="POST"
                    class="admin-order-status-form">

                    <div class="admin-status-select">

                        <label for="status">
                            Current Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            required>

                            <option
                                value="Pending"
                                <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>

                                Pending

                            </option>

                            <option
                                value="Processing"
                                <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>

                                Processing

                            </option>

                            <option
                                value="Shipped"
                                <?php echo $order['status'] === 'Shipped' ? 'selected' : ''; ?>>

                                Shipped

                            </option>

                            <option
                                value="Completed"
                                <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>

                                Completed

                            </option>

                            <option
                                value="Cancelled"
                                <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>

                                Cancelled

                            </option>

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

            </section>


        </main>

    </div>


</body>

</html>

<?php

mysqli_stmt_close($itemsStmt);

?>