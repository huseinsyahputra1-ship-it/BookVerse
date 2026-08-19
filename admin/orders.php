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
// GET ORDERS
// =========================================================

$ordersQuery = mysqli_query(
    $conn,
    "SELECT
        orders.*,
        users.fullname,
        users.email
     FROM orders
     INNER JOIN users
        ON orders.user_id = users.id
     ORDER BY orders.created_at DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Manage Orders - BookVerse</title>


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
                        Manage Orders
                    </h1>

                    <p>
                        Manage and monitor all customer orders.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 ORDER SUMMARY
                 ================================================= -->

            <div class="admin-book-summary">

                <div>

                    <i class="fa-solid fa-box"></i>

                    <div>

                        <span>Total Orders</span>

                        <strong>
                            <?php echo mysqli_num_rows($ordersQuery); ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 ORDER TABLE
                 ================================================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            ORDER COLLECTION
                        </span>

                        <h2>
                            All Orders
                        </h2>

                    </div>

                </div>


                <div class="admin-books-table-wrapper">

                    <table class="admin-books-table">

                        <thead>

                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (mysqli_num_rows($ordersQuery) > 0): ?>

                                <?php while ($order = mysqli_fetch_assoc($ordersQuery)): ?>

                                    <tr>

                                        <td>

                                            <strong class="admin-book-title">

                                                <?php echo htmlspecialchars(
                                                    $order['order_number']
                                                ); ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <div class="admin-order-customer">

                                                <strong>
                                                    <?php echo htmlspecialchars(
                                                        $order['fullname']
                                                    ); ?>
                                                </strong>

                                                <span>
                                                    <?php echo htmlspecialchars(
                                                        $order['email']
                                                    ); ?>
                                                </span>

                                            </div>

                                        </td>


                                        <td>

                                            <?php echo date(
                                                'd M Y, H:i',
                                                strtotime($order['created_at'])
                                            ); ?>

                                        </td>


                                        <td>

                                            <strong class="admin-table-price">

                                                Rp<?php echo number_format(
                                                    $order['total_price'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ); ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?php

                                            $statusClass = strtolower(
                                                $order['status']
                                            );

                                            ?>

                                            <span
                                                class="admin-order-status <?php echo $statusClass; ?>">

                                                <?php echo htmlspecialchars(
                                                    $order['status']
                                                ); ?>

                                            </span>

                                        </td>


                                        <td>

                                            <div class="admin-book-actions">

                                                <a
                                                    href="order-detail.php?id=<?php echo $order['id']; ?>"
                                                    class="admin-action-button edit">

                                                    <i class="fa-solid fa-eye"></i>

                                                    View

                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="6"
                                        class="admin-empty-books">

                                        <i class="fa-solid fa-box-open"></i>

                                        <strong>
                                            No orders found
                                        </strong>

                                        <span>
                                            There are currently no customer orders.
                                        </span>

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>

    </div>


</body>

</html>