<?php

session_start();

include "config/database.php";

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();

}

$userId = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        wishlist.id AS wishlist_id,
        books.id AS book_id,
        books.title,
        books.author,
        books.price,
        books.image
     FROM wishlist
     INNER JOIN books
        ON wishlist.book_id = books.id
     WHERE wishlist.user_id = ?
     ORDER BY wishlist.created_at DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$wishlistBooks = [];

while ($book = mysqli_fetch_assoc($result)) {

    $wishlistBooks[] = $book;

}

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>My Wishlist - BookVerse</title>

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

    <!-- CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>

<body>

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

                            <a href="index.php#books">

                                Books

                            </a>

                        </li>

                        <li>

                            <a href="index.php#categories">

                                Categories

                            </a>

                        </li>

                        <li>

                            <a href="index.php#about">

                                About

                            </a>

                        </li>

                        <li>

                            <a href="index.php#contact">

                                Contact

                            </a>

                        </li>

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

                    <a
                        href="wishlist.php"
                        class="icon-btn">

                        <i class="fa-solid fa-heart"></i>

                    </a>

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


    <!-- ================= WISHLIST ================= -->

    <section class="featured-books wishlist-page">

        <div class="container">

            <div class="section-title">

                <h2>
                    My Wishlist
                </h2>

                <p>
                    Books you've saved for later.
                </p>

                <a
                    href="index.php"
                    class="wishlist-back-btn">

                    <i class="fa-solid fa-arrow-left"></i>

                    Kembali ke Beranda

                </a>

            </div>


            <?php if (empty($wishlistBooks)): ?>

                <div class="empty-state">

                    <div class="empty-icon">

                        ❤️

                    </div>

                    <h2>
                        Your wishlist is empty
                    </h2>

                    <p>
                        Save your favorite books here and find them easily later.
                    </p>

                    <a
                        href="index.php"
                        class="buy-btn">

                        Explore Books

                    </a>

                </div>

            <?php else: ?>

                <div class="book-grid">

                    <?php foreach ($wishlistBooks as $book): ?>

                        <div class="book-card">

                            <a
                                href="book-detail.php?id=<?php echo $book['book_id']; ?>"
                                class="book-detail-link">

                                <img
                                    src="<?php echo htmlspecialchars($book['image']); ?>"
                                    alt="<?php echo htmlspecialchars($book['title']); ?>">

                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $book['title']
                                    );
                                    ?>

                                </h3>

                                <p>

                                    <?php
                                    echo htmlspecialchars(
                                        $book['author']
                                    );
                                    ?>

                                </p>

                                <span class="price">

                                    Rp<?php
                                    echo number_format(
                                        $book['price'],
                                        0,
                                        ',',
                                        '.'
                                    );
                                    ?>

                                </span>

                            </a>

                            <div class="book-action">

                                <a
                                    href="remove-from-wishlist.php?id=<?php echo $book['book_id']; ?>"
                                    class="cart-btn">

                                    <i class="fa-solid fa-heart-crack"></i>

                                    Remove

                                </a>

                                <a
                                    href="add-to-cart.php?id=<?php echo $book['book_id']; ?>"
                                    class="buy-btn">

                                    🛒 Cart

                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>


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

                            <a href="index.php#books">

                                Books

                            </a>

                        </li>

                        <li>

                            <a href="index.php#categories">

                                Categories

                            </a>

                        </li>

                        <li>

                            <a href="index.php#about">

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


    <script src="assets/js/script.js"></script>

</body>

</html>