<?php

require_once __DIR__ . '/admin-session.php';

include "../config/database.php";

// ================= ADMIN ACCESS =================

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

// ================= STOCK HISTORY DATA =================

$historyQuery = mysqli_query(
    $conn,
    "SELECT
        sh.*,
        b.title AS book_title,
        u.fullname AS user_name
    FROM stock_history sh
    LEFT JOIN books b ON sh.book_id = b.id
    LEFT JOIN users u ON sh.user_id = u.id
    ORDER BY sh.created_at DESC"
);

if (!$historyQuery) {
    die("Gagal mengambil data stock history: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Stock History - BookVerse</title>

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

    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <style>

        .history-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
            font-size: 13px;
        }

        .history-table th,
        .history-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e8edf3;
        }

        .history-table th {
            background: #f5f7fa;
            color: #344054;
            font-weight: 600;
            white-space: nowrap;
        }

        .history-table td {
            color: #475467;
        }

        .history-table tbody tr:hover {
            background: #f9fafb;
        }

        .history-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-in {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-out {
            background: #fee2e2;
            color: #dc2626;
        }

        .badge-sale {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .history-quantity {
            font-weight: 700;
        }

        .history-empty {
            padding: 40px 20px;
            text-align: center;
            color: #98a2b3;
        }

        .history-empty i {
            font-size: 35px;
            margin-bottom: 12px;
        }

        .history-empty p {
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .history-table-wrapper {
                margin-top: 15px;
            }
        }

    </style>

</head>

<body>

    <!-- ================= ADMIN HEADER ================= -->

    <header class="admin-header">

        <div class="admin-header-left">

            <a
                href="../index.php"
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
                href="logout.php"
                class="admin-logout">

                <i class="fa-solid fa-right-from-bracket"></i>

                Logout

            </a>

        </div>

    </header>


    <!-- ================= ADMIN LAYOUT ================= -->

    <div class="admin-layout">


        <!-- ================= SIDEBAR ================= -->

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
                    href="categories.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-tags"></i>

                    <span>Manage Categories</span>

                </a>    


                <a
                    href="orders.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-box"></i>

                    <span>Manage Orders</span>

                </a>

                <a
                    href="payment-transactions.php"
                    class="admin-nav-link">

                    <i class="fa-solid  fa-money-bill-transfer"></i>

                    <span>Payment Transactions</span>

                </a>

                <a
                    href="return-management.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-rotate-left"></i>

                    <span>Return Management</span>

                </a>

                <a
                    href="users.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-users"></i>

                    <span>Manage Users</span>

                </a>


                <a
                    href="messages.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-envelope"></i>

                    <span>Messages</span>

                </a>


                <a
                    href="stock-history.php"
                    class="admin-nav-link active">

                    <i class="fa-solid fa-clock-rotate-left"></i>

                    <span>Stock History</span>

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


        <!-- ================= MAIN CONTENT ================= -->

        <main class="admin-main">

            <div class="admin-page-header">

                <div>

                    <span class="admin-page-label">
                        INVENTORY MANAGEMENT
                    </span>

                    <h1>Stock History</h1>

                    <p>
                        Track every stock movement in your BookVerse store.
                    </p>

                </div>

            </div>


            <!-- ================= HISTORY TABLE ================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            INVENTORY RECORDS
                        </span>

                        <h2>Stock Activity Records</h2>

                    </div>

                </div>


                <div class="history-table-wrapper">

                    <table class="history-table">

                        <thead>

                            <tr>

                                <th>No.</th>

                                <th>Date</th>

                                <th>Book</th>

                                <th>Activity</th>

                                <th>Quantity</th>

                                <th>Stock Before</th>

                                <th>Stock After</th>

                                <th>Performed By</th>

                                <th>Notes</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (mysqli_num_rows($historyQuery) > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($history = mysqli_fetch_assoc($historyQuery)): ?>

                                    <tr>

                                        <td>
                                            <?php echo $no++; ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo date(
                                                'd M Y, H:i',
                                                strtotime($history['created_at'])
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $history['book_title'] ?? 'Buku tidak tersedia'
                                                );
                                                ?>
                                            </strong>
                                        </td>

                                        <td>

                                            <?php if ($history['activity_type'] === 'Stock In'): ?>

                                                <span class="history-badge badge-in">
                                                    <i class="fa-solid fa-arrow-down"></i>
                                                    Stock In
                                                </span>

                                            <?php elseif ($history['activity_type'] === 'Stock Out'): ?>

                                                <span class="history-badge badge-out">
                                                    <i class="fa-solid fa-arrow-up"></i>
                                                    Stock Out
                                                </span>

                                            <?php else: ?>

                                                <span class="history-badge badge-sale">
                                                    <i class="fa-solid fa-cart-shopping"></i>
                                                    Sale
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td class="history-quantity">

                                            <?php if ($history['activity_type'] === 'Stock In'): ?>

                                                <span style="color: #16a34a;">
                                                    +<?php echo (int)$history['quantity']; ?>
                                                </span>

                                            <?php else: ?>

                                                <span style="color: #dc2626;">
                                                    -<?php echo (int)$history['quantity']; ?>
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?php echo (int)$history['stock_before']; ?>
                                        </td>

                                        <td>
                                            <?php echo (int)$history['stock_after']; ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $history['user_name'] ?? 'User tidak tersedia'
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $history['notes'] ?? '-'
                                            );
                                            ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="9">

                                        <div class="history-empty">

                                            <i class="fa-solid fa-clock-rotate-left"></i>

                                            <h3>No Stock History Yet</h3>

                                            <p>
                                                Stock activities will appear here.
                                            </p>

                                        </div>

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