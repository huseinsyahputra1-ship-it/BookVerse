<?php

require_once __DIR__ . '/admin-session.php';

include "../config/database.php";

// ================= ADMIN ACCESS =================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('Akses ditolak! Halaman ini hanya untuk admin.');
        window.location='../index.php';
    </script>";
    exit();
}

// ================= MESSAGE STATISTICS =================

$totalQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM messages"
);

$totalData = mysqli_fetch_assoc($totalQuery);
$totalMessages = $totalData['total'] ?? 0;


$unreadQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM messages
     WHERE status = 'Unread'"
);

$unreadData = mysqli_fetch_assoc($unreadQuery);
$totalUnread = $unreadData['total'] ?? 0;


$readQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM messages
     WHERE status = 'Read'"
);

$readData = mysqli_fetch_assoc($readQuery);
$totalRead = $readData['total'] ?? 0;


// ================= MESSAGE FILTER =================

$filter = $_GET['status'] ?? 'all';

if (!in_array($filter, ['all', 'Unread', 'Read'], true)) {
    $filter = 'all';
}

if ($filter === 'Unread' || $filter === 'Read') {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM messages
         WHERE status = ?
         ORDER BY created_at DESC"
    );

    mysqli_stmt_bind_param($stmt, "s", $filter);
    mysqli_stmt_execute($stmt);

    $messagesQuery = mysqli_stmt_get_result($stmt);

} else {

    $messagesQuery = mysqli_query(
        $conn,
        "SELECT *
         FROM messages
         ORDER BY created_at DESC"
    );

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Messages - BookVerse Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">

    <link rel="stylesheet" href="../assets/css/admin-messages.css">

</head>

<body>

    <!-- ================= ADMIN HEADER ================= -->

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


    <!-- ================= ADMIN LAYOUT ================= -->

    <div class="admin-layout">

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

                <a
                    href="payment-transactions.php"
                    class="admin-nav-link">

                    <i class="fa-solid  fa-money-bill-transfer"></i>
                    <span>Payment Transactions</span>

                </a>

                <a href="return-management.php" class="admin-nav-link">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Return Management</span>
                </a>

                <a href="users.php" class="admin-nav-link">
                    <i class="fa-solid fa-users"></i>
                    <span>Manage Users</span>
                </a>

                <a href="messages.php" class="admin-nav-link active">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Messages</span>
                </a>

                <!-- STOCK HISTORY -->

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


        <!-- ================= MAIN CONTENT ================= -->

        <main class="admin-main">

            <div class="admin-page-header">

                <div>

                    <span class="admin-page-label">
                        CUSTOMER INBOX
                    </span>

                    <h1>Messages</h1>

                    <p>
                        Manage messages sent by BookVerse visitors.
                    </p>

                </div>

            </div>


            <!-- ================= MESSAGE STATISTICS ================= -->

            <div class="admin-stats">

                <div class="admin-stat-card">

                    <div class="admin-stat-icon">
                        <i class="fa-solid fa-envelope"></i>
                    </div>

                    <div>
                        <span>Total Messages</span>
                        <strong><?php echo $totalMessages; ?></strong>
                    </div>

                </div>


                <div class="admin-stat-card">

                    <div class="admin-stat-icon">
                        <i class="fa-solid fa-envelope-open"></i>
                    </div>

                    <div>
                        <span>Unread Messages</span>
                        <strong><?php echo $totalUnread; ?></strong>
                    </div>

                </div>


                <div class="admin-stat-card">

                    <div class="admin-stat-icon">
                        <i class="fa-solid fa-check-double"></i>
                    </div>

                    <div>
                        <span>Read Messages</span>
                        <strong><?php echo $totalRead; ?></strong>
                    </div>

                </div>

            </div>


            <!-- ================= MESSAGE LIST ================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            INBOX MANAGEMENT
                        </span>

                        <h2>Received Messages</h2>

                    </div>

                </div>


                <!-- FILTER -->

                <div class="message-filters">

                    <a
                        href="messages.php"
                        class="<?php echo $filter === 'all' ? 'active' : ''; ?>">

                        All Messages
                        <span><?php echo $totalMessages; ?></span>

                    </a>


                    <a
                        href="messages.php?status=Unread"
                        class="<?php echo $filter === 'Unread' ? 'active' : ''; ?>">

                        Unread
                        <span><?php echo $totalUnread; ?></span>

                    </a>


                    <a
                        href="messages.php?status=Read"
                        class="<?php echo $filter === 'Read' ? 'active' : ''; ?>">

                        Read
                        <span><?php echo $totalRead; ?></span>

                    </a>

                </div>


                <div class="admin-users-table-wrapper">

                    <table class="admin-users-table message-table">

                        <thead>

                            <tr>
                                <th>Sender</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($messagesQuery && mysqli_num_rows($messagesQuery) > 0): ?>

                                <?php while ($message = mysqli_fetch_assoc($messagesQuery)): ?>

                                    <tr>

                                        <td>

                                            <div class="admin-user-info">

                                                <div class="admin-user-avatar">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>

                                                <div class="message-sender">

                                                    <strong>
                                                        <?php echo htmlspecialchars($message['name']); ?>
                                                    </strong>

                                                    <span>
                                                        <?php echo htmlspecialchars($message['email']); ?>
                                                    </span>

                                                </div>

                                            </div>

                                        </td>


                                        <td>
                                            <span class="message-subject">
                                                <?php echo htmlspecialchars($message['subject']); ?>
                                            </span>
                                        </td>


                                        <td>
                                            <?php
                                            echo date(
                                                'd M Y, H:i',
                                                strtotime($message['created_at'])
                                            );
                                            ?>
                                        </td>


                                        <td>

                                            <span class="message-status <?php echo strtolower($message['status']); ?>">

                                                <?php echo htmlspecialchars($message['status']); ?>

                                            </span>

                                        </td>


                                        <td>

                                            <a
                                                href="message-detail.php?id=<?php echo (int) $message['id']; ?>"
                                                class="admin-action-button message-view-button">

                                                <i class="fa-solid fa-eye"></i>
                                                View

                                            </a>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="5">

                                        <div class="message-empty">

                                            <i class="fa-solid fa-inbox"></i>

                                            <strong>No messages found</strong>

                                            <span>
                                                Messages from visitors will appear here.
                                            </span>

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