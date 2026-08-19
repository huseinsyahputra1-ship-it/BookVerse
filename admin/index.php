<?php

session_start();

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

// ================= DASHBOARD DATA =================

$booksQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM books"
);

$booksData = mysqli_fetch_assoc($booksQuery);

$totalBooks = $booksData['total'];


$usersQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users"
);

$usersData = mysqli_fetch_assoc($usersQuery);

$totalUsers = $usersData['total'];


$ordersQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders"
);

$ordersData = mysqli_fetch_assoc($ordersQuery);

$totalOrders = $ordersData['total'];


// ================= RECENT BOOKS =================

$recentBooksQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM books
     ORDER BY id DESC
     LIMIT 5"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - BookVerse</title>

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
                href="../logout.php"
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
                    class="admin-nav-link active">

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
                    class="admin-nav-link">

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


        <!-- ================= MAIN CONTENT ================= -->

        <main class="admin-main">

            <div class="admin-page-header">

                <div>

                    <span class="admin-page-label">
                        ADMIN DASHBOARD
                    </span>

                    <h1>
                        Welcome back,
                        <?php echo htmlspecialchars($_SESSION['fullname']); ?>!
                    </h1>

                    <p>
                        Manage your BookVerse store from here.
                    </p>

                </div>

            </div>


            <!-- ================= STATISTICS ================= -->

            <div class="admin-stats">


                <div class="admin-stat-card">

                    <div class="admin-stat-icon">

                        <i class="fa-solid fa-book"></i>

                    </div>

                    <div>

                        <span>Total Books</span>

                        <strong>
                            <?php echo $totalBooks; ?>
                        </strong>

                    </div>

                </div>


                <div class="admin-stat-card">

                    <div class="admin-stat-icon">

                        <i class="fa-solid fa-users"></i>

                    </div>

                    <div>

                        <span>Total Users</span>

                        <strong>
                            <?php echo $totalUsers; ?>
                        </strong>

                    </div>

                </div>


                <div class="admin-stat-card">

                    <div class="admin-stat-icon">

                        <i class="fa-solid fa-box"></i>

                    </div>

                    <div>

                        <span>Total Orders</span>

                        <strong>
                            <?php echo $totalOrders; ?>
                        </strong>

                    </div>

                </div>


            </div>


            <!-- ================= RECENT BOOKS ================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            BOOK COLLECTION
                        </span>

                        <h2>Recent Books</h2>

                    </div>

                    <a
                        href="books.php"
                        class="admin-view-all">

                        View All

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>


                <div class="admin-book-table">

                    <div class="admin-table-header">

                        <span>Book</span>

                        <span>Author</span>

                        <span>Price</span>

                    </div>


                    <?php while ($book = mysqli_fetch_assoc($recentBooksQuery)): ?>

                        <div class="admin-table-row">

                            <div class="admin-book-info">

                                <img
                                    src="../<?php echo htmlspecialchars($book['image']); ?>"
                                    alt="<?php echo htmlspecialchars($book['title']); ?>">

                                <div>

                                    <strong>
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </strong>

                                </div>

                            </div>


                            <span>
                                <?php echo htmlspecialchars($book['author']); ?>
                            </span>


                            <strong class="admin-book-price">

                                Rp<?php echo number_format(
                                    $book['price'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>

                            </strong>

                        </div>

                    <?php endwhile; ?>

                </div>

            </section>

        </main>

    </div>

</body>

</html>