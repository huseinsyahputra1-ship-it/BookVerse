<?php 
session_start(); 

include "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookVerse</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="header">

        <div class="container">

            <div class="navbar">

                <!-- Logo -->

                <div class="logo">

                    <img src="assets/img/hugeicons_book-open-02.png" alt="BookVerse">

                    <div class="logo-text">

                        <h2>BookVerse</h2>

                        <p>Every Book Has a Story</p>

                    </div>

                </div>

                <!-- Menu -->

                <nav>

                    <ul class="menu">

                        <li><a href="#" class="active">Home</a></li>

                        <li><a href="#">Books</a></li>

                        <li><a href="#">Categories</a></li>

                        <li><a href="#">About</a></li>

                        <li><a href="#">Contact</a></li>

                    </ul>

                </nav>

                <!-- Right -->

                <div class="right-menu">

                    <div class="search-box">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Search Books...">

                    </div>

                    <button class="icon-btn">

                        <i class="fa-regular fa-heart"></i>

                    </button>

                    <button class="icon-btn">

                        <i class="fa-solid fa-cart-shopping"></i>

                    </button>

<?php if (isset($_SESSION['user_id'])): ?>

    <div class="profile-menu">

        <button
            type="button"
            class="profile-btn"
            id="profileBtn">

            <i class="fa-solid fa-user"></i>

            <span>
                <?php echo htmlspecialchars($_SESSION['fullname']); ?>
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
                        <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                    </strong>

                    <small>
                        <?php echo htmlspecialchars($_SESSION['email']); ?>
                    </small>

                </div>

            </div>

            <hr>

            <a href="profile.php">

                <i class="fa-solid fa-user"></i>

                <span>Profile</span>

            </a>

            <a href="orders.php">

                <i class="fa-solid fa-box"></i>

                <span>Pesanan Saya</span>

            </a>

            <hr>

            <a
                href="logout.php"
                class="logout-link">

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Logout</span>

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

    <!-- ================= HERO ================= -->

    <section class="hero">

        <div class="container">

            <div class="hero-wrapper">

                <!-- LEFT -->

                <div class="hero-left">

                    <span class="hero-tag">

                        📚 Your Favorite Online Book Store

                    </span>

                    <h1>

                        Discover Your <br>
                        Next

                        <span>

                            Favorite Book

                        </span>

                    </h1>

                    <p>

                        Explore thousands of books from every genre,
                        discover inspiring stories,
                        and enjoy a reading experience that opens
                        the door to endless imagination.

                    </p>

                    <div class="hero-button">

                        <a href="#" class="btn-purple">

                            Explore Books

                        </a>

                        <a href="#" class="btn-yellow">

                            Browse Categories

                        </a>

                    </div>

                </div>

                <!-- RIGHT -->

                <div class="hero-right">

                    <img src="assets/img/hero-library.jpeg" alt="Library">

                </div>

            </div>

        </div>

    </section>

    <!-- ================= STATISTICS ================= -->

    <section class="statistics">

        <div class="container">

            <div class="stats-wrapper">

                <div class="stat-card">

                    <div class="stat-icon">
                        <img src="assets/img/mdi_account-multiple.png" alt="">
                    </div>

                    <div class="stat-content">
                        <h2>12K+</h2>
                        <p>Happy Readers</p>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <img src="assets/img/qlementine-icons_book-16.png" alt="">
                    </div>

                    <div class="stat-content">
                        <h2>8K+</h2>
                        <p>Book Collection</p>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <img src="assets/img/si_bar-chart-line.png" alt="">
                    </div>

                    <div class="stat-content">
                        <h2>4.9</h2>
                        <p>Customer Rating</p>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <img src="assets/img/mdi_truck-fast-outline.png" alt="">
                    </div>

                    <div class="stat-content">
                        <h2>Fast</h2>
                        <p>Delivery</p>
                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- ================= CATEGORY ================= -->

    <section class="category">

        <div class="container">

            <div class="section-title">

                <h2>Shop by Category</h2>

                <p>
                    Find your favorite books from various interesting categories.
                </p>

            </div>

            <div class="category-wrapper">

                <div class="category-card">

                    <img src="assets/img/mdi_brain.png" alt="">

                    <h3>Self Development</h3>

                    <span>120 Books</span>

                </div>

                <div class="category-card">

                    <img src="assets/img/tabler_book.png" alt="">

                    <h3>Novel</h3>

                    <span>340 Books</span>

                </div>

                <div class="category-card">

                    <img src="assets/img/streamline_office-building-1.png" alt="">

                    <h3>Business</h3>

                    <span>96 Books</span>

                </div>

                <div class="category-card">

                    <img src="assets/img/qlementine-icons_book-16.png" alt="">

                    <h3>Education</h3>

                    <span>210 Books</span>

                </div>

                <div class="category-card">

                    <img src="assets/img/hugeicons_book-open-02.png" alt="">

                    <h3>Comics</h3>

                    <span>180 Books</span>

                </div>

            </div>

        </div>

    </section>
    
<!-- ================= FEATURED BOOKS ================= -->

<section class="featured-books">

    <div class="container">

        <div class="section-title">

            <h2>Featured Books</h2>

            <p>
                Discover our handpicked collection of the most popular books.
            </p>

        </div>

        <div class="book-grid">

            <?php

            $query = mysqli_query(
                $conn,
                "SELECT *
                 FROM books
                 ORDER BY id ASC"
            );

            while ($book = mysqli_fetch_assoc($query)):

            ?>

                <div class="book-card">

                    <img
                        src="<?php echo htmlspecialchars($book['image']); ?>"
                        alt="<?php echo htmlspecialchars($book['title']); ?>">

                    <h3>
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h3>

                    <p>
                        <?php echo htmlspecialchars($book['author']); ?>
                    </p>

                    <span class="price">
                        Rp<?php echo number_format($book['price'], 0, ',', '.'); ?>
                    </span>

                    <div class="book-action">

                        <a
                            href="#"
                            class="cart-btn">
                            🛒 Keranjang
                        </a>

                        <a
                            href="#"
                            class="buy-btn">
                            Beli
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    </div>

</section>

    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        <div class="container">

            <div class="footer-content">

                <div class="footer-logo">

                    <img src="assets/img/hugeicons_book-open-02.png" alt="BookVerse">

                    <h2>BookVerse</h2>

                    <p>
                        Every Book Has a Story.
                        Discover books that inspire your journey.
                    </p>

                </div>

                <div class="footer-links">

                    <h3>Quick Links</h3>

                    <ul>
                        <li><a href="#">Home</a></li>
                        <li><a href="#">Books</a></li>
                        <li><a href="#">Categories</a></li>
                        <li><a href="#">About</a></li>
                    </ul>

                </div>

                <div class="footer-contact">

                    <h3>Contact</h3>

                    <p>Email : info@bookverse.com</p>

                    <p>Phone : +62 812 3456 7890</p>

                    <p>Jakarta, Indonesia</p>

                </div>

            </div>

            <div class="footer-bottom">

                <p>
                    © 2026 BookVerse. All Rights Reserved.
                </p>

            </div>

        </div>

    </footer>

 <script src="assets/js/script.js"></script>

</body>
</html>