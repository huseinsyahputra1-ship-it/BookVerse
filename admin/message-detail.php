<?php

require_once __DIR__ . '/admin-session.php';

include "../config/database.php";

/* ================= ADMIN ACCESS ================= */

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


/* ================= GET MESSAGE ID ================= */

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: messages.php");
    exit();
}


/* ================= GET MESSAGE ================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM messages WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$message = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$message) {
    header("Location: messages.php");
    exit();
}


/* ================= MARK AS READ ================= */

if ($message['status'] === 'Unread') {

    $updateStmt = mysqli_prepare(
        $conn,
        "UPDATE messages
         SET status = 'Read'
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($updateStmt, "i", $id);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    $message['status'] = 'Read';
}


/* ================= EMAIL REPLY ================= */

$replySubject = "Re: " . $message['subject'];

$mailLink = "mailto:" . $message['email']
    . "?subject=" . rawurlencode($replySubject);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Message Detail - BookVerse Admin</title>

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

                <a href="payment-transactions.php" class="admin-nav-link">
                    <i class="fa-solid fa-credit-card"></i>
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

                    <h1>Message Detail</h1>

                    <p>
                        Read the message sent by your visitor.
                    </p>

                </div>

                <a href="messages.php" class="admin-back-button">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Messages
                </a>

            </div>


            <!-- ================= MESSAGE DETAIL ================= -->

            <section class="admin-section message-detail-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            MESSAGE INFORMATION
                        </span>

                        <h2>
                            <?php echo htmlspecialchars($message['subject']); ?>
                        </h2>

                    </div>

                    <span class="message-status read">
                        Read
                    </span>

                </div>


                <div class="message-detail-info">

                    <div class="message-detail-item">

                        <span>From</span>

                        <strong>
                            <?php echo htmlspecialchars($message['name']); ?>
                        </strong>

                    </div>


                    <div class="message-detail-item">

                        <span>Email Address</span>

                        <strong>
                            <?php echo htmlspecialchars($message['email']); ?>
                        </strong>

                    </div>


                    <div class="message-detail-item">

                        <span>Received At</span>

                        <strong>
                            <?php
                            echo date(
                                'd F Y, H:i',
                                strtotime($message['created_at'])
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="message-content-wrapper">

                    <span class="message-content-label">
                        MESSAGE CONTENT
                    </span>

                    <div class="message-content">

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $message['message'],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        );
                        ?>

                    </div>

                </div>


                <div class="message-detail-actions">

                    <a href="messages.php" class="admin-form-cancel">
                        Back to Inbox
                    </a>

                    <a
                        href="<?php echo htmlspecialchars($mailLink, ENT_QUOTES, 'UTF-8'); ?>"
                        class="admin-form-submit message-reply-button">

                        <i class="fa-solid fa-reply"></i>
                        Reply via Email

                    </a>

                </div>

            </section>

        </main>

    </div>

</body>

</html>