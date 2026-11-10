<?php

require_once __DIR__ . '/config/user-session.php';

include "config/database.php";

// Ambil kategori dari URL jika ada.
$selectedCategory = isset($_GET['category']) && is_string($_GET['category'])
    ? trim($_GET['category'])
    : '';

// Jika kategori dipilih, tampilkan buku sesuai kategori.
if ($selectedCategory !== '') {

    $safeCategory = mysqli_real_escape_string($conn, $selectedCategory);

    $query = mysqli_query(
        $conn,
        "SELECT *
         FROM books
         WHERE category = '$safeCategory'
         ORDER BY id DESC"
    );

} else {

    // Jika tidak ada kategori, tampilkan semua buku.
    $query = mysqli_query(
        $conn,
        "SELECT *
         FROM books
         ORDER BY id DESC"
    );

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Books - BookVerse</title>

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

    <style>
        .book-image-wrapper {
            position: relative;
            display: block;
        }

        .stock-light {
            position: absolute;
            top: 12px;
            left: 12px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            z-index: 5;
            border: 2px solid #fff;
        }

        .stock-light.available {
            background: #22c55e;
        }

        .stock-light.sold-out {
            background: #ef4444;
        }

        .stock-label {
            font-size: 12px;
            font-weight: 600;
            margin: 8px 0;
        }

        .stock-label.low {
            color: #e59a18;
        }

        .stock-label.empty {
            color: #e53935;
        }

        .book-action a.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
    </style>

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

                            <a
                                href="books.php"
                                class="active">

                                Books

                            </a>

                        </li>

                        <li>

                            <a href="categories.php">

                                Categories

                            </a>

                        </li>

                        <li>

                            <a href="about.php">

                                About

                            </a>

                        </li>

                        <li>

                            <a href="contact.php">

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


                    <a
                        href="wishlist.php"
                        class="icon-btn">

                        <i class="fa-regular fa-heart"></i>

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


    <!-- ================= BOOKS PAGE ================= -->

    <main class="books-page">

        <div class="container">

            <div class="section-title">

                <h2>
                    All Books
                </h2>

                <p>
                    Explore our collection and find your next favorite book.
                </p>

            </div>


            <div class="book-grid">

                <?php if ($query && mysqli_num_rows($query) > 0): ?>

                    <?php while ($book = mysqli_fetch_assoc($query)): ?>

                        <div class="book-card">

                            <!-- Wishlist -->

                            <a
                                href="add-to-wishlist.php?id=<?php echo $book['id']; ?>"
                                class="wishlist-btn"
                                title="Add to Wishlist">

                                <i class="fa-regular fa-heart"></i>

                            </a>


                            <!-- Book Detail -->

                            <a
                                href="book-detail.php?id=<?php echo $book['id']; ?>"
                                class="book-detail-link">

                                <div class="book-image-wrapper">

                                    <span class="stock-light <?php echo (int) $book['stock'] > 0 ? 'available' : 'sold-out'; ?>"></span>

                                    <img
                                        src="<?php echo htmlspecialchars($book['image']); ?>"
                                        alt="<?php echo htmlspecialchars($book['title']); ?>">

                                </div>

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


                            <!-- Stock Information -->

                            <?php if ((int) $book['stock'] > 0 && (int) $book['stock'] <= 5): ?>

                                <p class="stock-label low">
                                    Only <?php echo (int) $book['stock']; ?> left
                                </p>

                            <?php elseif ((int) $book['stock'] <= 0): ?>

                                <p class="stock-label empty">
                                    Out of Stock
                                </p>

                            <?php endif; ?>


                            <!-- Actions -->

                            <div class="book-action">

                                <?php if ((int) $book['stock'] > 0): ?>

                                    <a
                                        href="add-to-cart.php?id=<?php echo $book['id']; ?>"
                                        class="cart-btn">

                                        🛒 Cart

                                    </a>


                                    <a
                                        href="book-detail.php?id=<?php echo $book['id']; ?>"
                                        class="buy-btn">

                                        Beli

                                    </a>

                                <?php else: ?>

                                    <a
                                        href="javascript:void(0)"
                                        class="cart-btn disabled"
                                        aria-disabled="true">

                                        🛒 Cart

                                    </a>


                                    <a
                                        href="javascript:void(0)"
                                        class="buy-btn disabled"
                                        aria-disabled="true">

                                        Habis

                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p style="grid-column: 1 / -1; text-align: center; padding: 30px 0; color: #777;">

                        <?php if ($selectedCategory !== ''): ?>

                            Belum ada buku dalam kategori
                            "<?php echo htmlspecialchars($selectedCategory); ?>".

                        <?php else: ?>

                            Belum ada buku yang tersedia.

                        <?php endif; ?>

                    </p>

                <?php endif; ?>

            </div>

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

                            <a href="books.php">

                                Books

                            </a>

                        </li>

                        <li>

                            <a href="categories.php">

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


    <script src="assets/js/script.js"></script>

</body>

</html>