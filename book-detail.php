<?php

session_start();

include "config/database.php";

/* =========================================================
   GET BOOK ID
   ========================================================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$book_id = (int) $_GET['id'];

/* =========================================================
   GET BOOK DATA
   ========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM books
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================================================
   BOOK NOT FOUND
   ========================================================= */

if (!$book) {
    header("Location: index.php");
    exit();
}

/* =========================================================
   BOOK DATA
   ========================================================= */

$book_title = htmlspecialchars($book['title']);
$book_author = htmlspecialchars($book['author']);
$book_category = htmlspecialchars($book['category'] ?? '');
$book_description = htmlspecialchars($book['description'] ?? '');

$book_price = number_format(
    $book['price'],
    0,
    ',',
    '.'
);

$book_image = !empty($book['image'])
    ? htmlspecialchars($book['image'])
    : "assets/img/hugeicons_book-open-02.png";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $book_title; ?> | BookVerse
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

</head>

<body>

    <?php if (isset($_GET['added']) && $_GET['added'] === 'success'): ?>

       <div class="cart-toast">

           <i class="fa-solid fa-circle-check"></i>

           <span>
               Book added to your cart successfully!
           </span>

        </div>

    <?php endif; ?>                                                        
                                           
    <!-- ================= HEADER ================= -->
                                                                                     
    <header class="header">

        <div class="container">

            <div class="navbar">

                <!-- Logo -->

                <div class="logo">

                    <img
                        src="assets/img/hugeicons_book-open-02.png"
                        alt="BookVerse">

                    <div class="logo-text">

                        <h2>
                            BookVerse
                        </h2>

                        <p>
                            Every Book Has a Story
                        </p>

                    </div>

                </div>

                <!-- Menu -->

                <nav>

                    <ul class="menu">

                        <li>
                            <a href="index.php">
                                Home
                            </a>
                        </li>

                        <li>
                            <a href="index.php">
                                Books
                            </a>
                        </li>

                        <li>
                            <a href="index.php">
                                Categories
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                About
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                Contact
                            </a>
                        </li>

                    </ul>

                </nav>

                <!-- Right Menu -->

                <div class="right-menu">

                    <div class="search-box">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Search Books...">

                    </div>

                    <button
                        type="button"
                        class="icon-btn">

                        <i class="fa-regular fa-heart"></i>

                    </button>

                    <!-- Cart -->

                    <a
                        href="cart.php"
                        class="icon-btn">

                        <i class="fa-solid fa-cart-shopping"></i>

                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>

                        <div class="profile-menu">

                            <button
                                type="button"
                                class="profile-btn"
                                id="profileBtn">

                                <i class="fa-solid fa-user"></i>

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $_SESSION['fullname']
                                    );
                                    ?>
                                </span>

                                <i class="fa-solid fa-chevron-down"></i>

                            </button>

                            <div
                                class="profile-dropdown"
                                id="profileDropdown">

                                <div class="profile-header">

                                    <i class="fa-solid fa-circle-user"></i>

                                    <div>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $_SESSION['fullname']
                                            );
                                            ?>
                                        </strong>

                                        <small>
                                            <?php
                                            echo htmlspecialchars(
                                                $_SESSION['email']
                                            );
                                            ?>
                                        </small>

                                    </div>

                                </div>

                                <hr>

                                <a href="profile.php">

                                    <i class="fa-solid fa-user"></i>

                                    <span>
                                        Profile
                                    </span>

                                </a>

                                <a href="orders.php">

                                    <i class="fa-solid fa-box"></i>

                                    <span>
                                        Pesanan Saya
                                    </span>

                                </a>

                                <hr>

                                <a
                                    href="logout.php"
                                    class="logout-link">

                                    <i class="fa-solid fa-right-from-bracket"></i>

                                    <span>
                                        Logout
                                    </span>

                                </a>

                            </div>

                        </div>

                    <?php else: ?>

                        <a
                            href="login.php"
                            class="login-btn">

                            Login

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </header>

    <!-- ================= BOOK DETAIL ================= -->

    <main class="book-detail-page">

        <div class="container">

            <!-- Breadcrumb -->

            <div class="book-detail-breadcrumb">

                <a href="index.php">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Books

                </a>

            </div>

            <!-- Detail Card -->

            <section class="book-detail-card">

                <!-- Book Cover -->

                <div class="book-detail-image">

                    <img
                        src="<?php echo $book_image; ?>"
                        alt="<?php echo $book_title; ?>">

                </div>

                <!-- Book Information -->

                <div class="book-detail-content">

                    <?php if (!empty($book_category)): ?>

                        <span class="book-detail-category">

                            <?php echo $book_category; ?>

                        </span>

                    <?php endif; ?>

                    <h1>

                        <?php echo $book_title; ?>

                    </h1>

                    <p class="book-detail-author">

                        <i class="fa-solid fa-pen-nib"></i>

                        By

                        <strong>
                            <?php echo $book_author; ?>
                        </strong>

                    </p>

                    <div class="book-detail-price">

                        Rp<?php echo $book_price; ?>

                    </div>

                    <div class="book-detail-divider"></div>

                    <div class="book-detail-description">

                        <h3>
                            About This Book
                        </h3>

                        <?php if (!empty($book_description)): ?>

                            <p>
                                <?php echo nl2br($book_description); ?>
                            </p>

                        <?php else: ?>

                            <p>
                                No description is available
                                for this book yet.
                            </p>

                        <?php endif; ?>

                    </div>

                    <!-- Action -->

                    <div class="book-detail-action">

                        <!-- Add to Cart -->

                        <a
                            href="add-to-cart.php?id=<?php echo $book['id']; ?>&redirect=detail"
                            class="book-detail-cart">

                            <i class="fa-solid fa-cart-shopping"></i>

                            Tambah ke Keranjang

                        </a>

                        <!-- Buy Now -->

                        <button
                            type="button"
                            class="book-detail-buy">

                            <i class="fa-solid fa-bag-shopping"></i>

                            Beli Sekarang

                        </button>

                    </div>

                </div>

            </section>

        </div>

    </main>

    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        <div class="container">

            <div class="footer-content">

                <div class="footer-logo">

                    <img
                        src="assets/img/hugeicons_book-open-02.png"
                        alt="BookVerse">

                    <h2>
                        BookVerse
                    </h2>

                    <p>
                        Every Book Has a Story.
                        Discover books that inspire your journey.
                    </p>

                </div>

                <div class="footer-links">

                    <h3>
                        Quick Links
                    </h3>

                    <ul>

                        <li>
                            <a href="index.php">
                                Home
                            </a>
                        </li>

                        <li>
                            <a href="index.php">
                                Books
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                Categories
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                About
                            </a>
                        </li>

                    </ul>

                </div>

                <div class="footer-contact">

                    <h3>
                        Contact
                    </h3>

                    <p>
                        Email : info@bookverse.com
                    </p>

                    <p>
                        Phone : +62 812 3456 7890
                    </p>

                    <p>
                        Jakarta, Indonesia
                    </p>

                </div>

            </div>

            <div class="footer-bottom">

                <p>
                    © 2026 BookVerse. All Rights Reserved.
                </p>

            </div>

        </div>

    </footer>

    <!-- JavaScript -->

    <script src="assets/js/script.js"></script>

</body>

</html>