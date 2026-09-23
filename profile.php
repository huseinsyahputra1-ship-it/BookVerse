<?php

session_start();

include "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];

$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];
$role = $_SESSION['role'] ?? 'User';

/*
|--------------------------------------------------------------------------
| PROFILE STATISTICS
|--------------------------------------------------------------------------
*/

/* Total Orders */

$totalOrders = 0;

$orderStmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total_orders
     FROM orders
     WHERE user_id = ?"
);

mysqli_stmt_bind_param(
    $orderStmt,
    "i",
    $userId
);

mysqli_stmt_execute($orderStmt);

$orderResult = mysqli_stmt_get_result($orderStmt);

$orderData = mysqli_fetch_assoc($orderResult);

$totalOrders = (int) ($orderData['total_orders'] ?? 0);

mysqli_stmt_close($orderStmt);


/* Cart Items */

$cartItems = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $item) {

        $cartItems += (int) ($item['quantity'] ?? 0);

    }

}


/* Books Purchased */

$booksPurchased = 0;

$purchasedStmt = mysqli_prepare(
    $conn,
    "SELECT COALESCE(SUM(oi.quantity), 0) AS books_purchased
     FROM order_items oi
     INNER JOIN orders o
        ON oi.order_id = o.id
     WHERE o.user_id = ?"
);

mysqli_stmt_bind_param(
    $purchasedStmt,
    "i",
    $userId
);

mysqli_stmt_execute($purchasedStmt);

$purchasedResult = mysqli_stmt_get_result($purchasedStmt);

$purchasedData = mysqli_fetch_assoc($purchasedResult);

$booksPurchased = (int) ($purchasedData['books_purchased'] ?? 0);

mysqli_stmt_close($purchasedStmt);


/*
|--------------------------------------------------------------------------
| WISHLIST
|--------------------------------------------------------------------------
|
| Wishlist akan dihubungkan setelah logic wishlist selesai dibuat.
|
*/

$wishlistCount = 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>My Profile | BookVerse</title>

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
        href="assets/css/style.css">

</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="header">

        <div class="container">

            <div class="profile-navbar">

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

                <a
                    href="index.php"
                    class="profile-back">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Home

                </a>

            </div>

        </div>

    </header>


    <!-- ================= PROFILE PAGE ================= -->

    <main class="profile-page">

        <div class="container">

            <div class="profile-title">

                <span>MY ACCOUNT</span>

                <h1>My Profile</h1>

                <p>
                    Manage your BookVerse account and view your personal information.
                </p>

            </div>


            <!-- ================= PROFILE LAYOUT ================= -->

            <div class="profile-layout">

                <!-- ================= PROFILE CARD ================= -->

                <div class="profile-card">

                    <div class="profile-cover"></div>

                    <div class="profile-avatar">

                        <i class="fa-solid fa-user"></i>

                    </div>

                    <div class="profile-main-info">

                        <h2>
                            <?php echo htmlspecialchars($fullname); ?>
                        </h2>

                        <p>
                            <?php echo htmlspecialchars($email); ?>
                        </p>

                        <span class="profile-role">

                            <i class="fa-solid fa-shield-halved"></i>

                            <?php echo htmlspecialchars(ucfirst($role)); ?>

                        </span>

                    </div>

                    <div class="profile-card-actions">

                        <a
                            href="#account-info"
                            class="profile-action primary">

                            <i class="fa-solid fa-user-pen"></i>

                            Account Information

                        </a>

                        <a
                            href="orders.php"
                            class="profile-action secondary">

                            <i class="fa-solid fa-box"></i>

                            My Orders

                        </a>

                    </div>

                </div>


                <!-- ================= ACCOUNT INFORMATION ================= -->

                <div
                    class="account-info-card"
                    id="account-info">

                    <div class="account-heading">

                        <div>

                            <span>ACCOUNT</span>

                            <h2>Account Information</h2>

                        </div>

                        <i class="fa-solid fa-id-card"></i>

                    </div>


                    <div class="account-list">

                        <!-- FULL NAME -->

                        <div class="account-item">

                            <div class="account-icon">

                                <i class="fa-solid fa-user"></i>

                            </div>

                            <div class="account-text">

                                <span>Full Name</span>

                                <strong>
                                    <?php echo htmlspecialchars($fullname); ?>
                                </strong>

                            </div>

                        </div>


                        <!-- EMAIL -->

                        <div class="account-item">

                            <div class="account-icon">

                                <i class="fa-solid fa-envelope"></i>

                            </div>

                            <div class="account-text">

                                <span>Email Address</span>

                                <strong>
                                    <?php echo htmlspecialchars($email); ?>
                                </strong>

                            </div>

                        </div>


                        <!-- ROLE -->

                        <div class="account-item">

                            <div class="account-icon">

                                <i class="fa-solid fa-user-tag"></i>

                            </div>

                            <div class="account-text">

                                <span>Account Role</span>

                                <strong>
                                    <?php echo htmlspecialchars(ucfirst($role)); ?>
                                </strong>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="account-item">

                            <div class="account-icon">

                                <i class="fa-solid fa-circle-check"></i>

                            </div>

                            <div class="account-text">

                                <span>Account Status</span>

                                <strong class="status-active">
                                    Active
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ================= ACCOUNT STATS ================= -->

            <div class="profile-stats">

                <!-- TOTAL ORDERS -->

                <div class="profile-stat">

                    <div class="profile-stat-icon">

                        <i class="fa-solid fa-bag-shopping"></i>

                    </div>

                    <div>

                        <strong>
                            <?php echo $totalOrders; ?>
                        </strong>

                        <span>Total Orders</span>

                    </div>

                </div>


                <!-- WISHLIST -->

                <div class="profile-stat">

                    <div class="profile-stat-icon">

                        <i class="fa-regular fa-heart"></i>

                    </div>

                    <div>

                        <strong>
                            <?php echo $wishlistCount; ?>
                        </strong>

                        <span>Wishlist</span>

                    </div>

                </div>


                <!-- CART ITEMS -->

                <div class="profile-stat">

                    <div class="profile-stat-icon">

                        <i class="fa-solid fa-cart-shopping"></i>

                    </div>

                    <div>

                        <strong>
                            <?php echo $cartItems; ?>
                        </strong>

                        <span>Cart Items</span>

                    </div>

                </div>


                <!-- BOOKS PURCHASED -->

                <div class="profile-stat">

                    <div class="profile-stat-icon">

                        <i class="fa-solid fa-book-open"></i>

                    </div>

                    <div>

                        <strong>
                            <?php echo $booksPurchased; ?>
                        </strong>

                        <span>Books Purchased</span>

                    </div>

                </div>

            </div>


            <!-- ================= LOGOUT ================= -->

            <div class="profile-logout">

                <div>

                    <h3>Want to leave BookVerse?</h3>

                    <p>
                        You can safely log out from your account.
                    </p>

                </div>

                <a
                    href="logout.php"
                    class="logout-profile-btn">

                    <i class="fa-solid fa-right-from-bracket"></i>

                    Logout

                </a>

            </div>

        </div>

    </main>


    <!-- ================= FOOTER ================= -->

    <footer class="footer profile-footer">

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