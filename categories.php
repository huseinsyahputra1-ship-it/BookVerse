<?php

session_start();

include "config/database.php";

// Mengambil kategori dan menghitung jumlah buku
$query = mysqli_query(
    $conn,
    "SELECT category, COUNT(*) AS total_books
     FROM books
     WHERE category IS NOT NULL
       AND TRIM(category) != ''
     GROUP BY category
     ORDER BY category ASC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Categories - BookVerse</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

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

    <!-- CSS Utama -->
    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <!-- CSS Khusus Categories -->
    <link
        rel="stylesheet"
        href="assets/css/categories.css">

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

                        <h2>BookVerse</h2>

                        <p>Every Book Has a Story</p>

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
                            <a href="books.php">
                                Books
                            </a>
                        </li>

                        <li>
                            <a
                                href="categories.php"
                                class="active">

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


    <!-- ================= CATEGORIES PAGE ================= -->

    <main class="category">

        <div class="container">

            <div class="section-title">

                <h2>
                    Shop by Category
                </h2>

                <p>
                    Explore our book collections by category
                    and discover your next favorite story.
                </p>

            </div>


            <div class="category-wrapper">

                <?php if ($query && mysqli_num_rows($query) > 0): ?>

                    <?php while ($category = mysqli_fetch_assoc($query)): ?>

                        <?php

                        $categoryName = strtolower(
                            trim($category['category'])
                        );

                        // Menyamakan format nama kategori
                        $categoryName = str_replace(
                            ' ',
                            '-',
                            $categoryName
                        );

                        // Logo dan ikon kategori
                        $categoryIcons = [

                            'self-development' => [
                                'image' => 'assets/img/mdi_brain.png',
                                'class' => 'icon-self-development'
                            ],

                            'novel' => [
                                'image' => 'assets/img/tabler_book.png',
                                'class' => 'icon-fiction'
                            ],

                            'business' => [
                                'image' => 'assets/img/streamline_office-building-1.png',
                                'class' => 'icon-business'
                            ],

                            'education' => [
                                'image' => 'assets/img/qlementine-icons_book-16.png',
                                'class' => 'icon-education'
                            ],

                            'comics' => [
                                'image' => 'assets/img/hugeicons_book-open-02.png',
                                'class' => 'icon-comics'
                            ],

                            'fantasy' => [
                                'icon' => 'fa-wand-magic-sparkles',
                                'class' => 'icon-fantasy'
                            ],

                            'fiction' => [
                                'icon' => 'fa-book-open',
                                'class' => 'icon-fiction'
                            ],

                            'romance' => [
                                'icon' => 'fa-heart',
                                'class' => 'icon-romance'
                            ]

                        ];

                        $categoryStyle = $categoryIcons[$categoryName] ?? [
                            'icon' => 'fa-book-open',
                            'class' => 'icon-default'
                        ];

                        ?>


                        <a
                            href="books.php?category=<?php echo urlencode($category['category']); ?>"
                            class="category-card">

                            <!-- Category Icon -->

                            <?php if (isset($categoryStyle['image'])): ?>

                                <img
                                    src="<?php echo htmlspecialchars($categoryStyle['image']); ?>"
                                    alt="<?php echo htmlspecialchars($category['category']); ?>"
                                    class="category-icon">

                            <?php else: ?>

                                <i class="fa-solid <?php echo $categoryStyle['icon']; ?> category-icon <?php echo $categoryStyle['class']; ?>"></i>

                            <?php endif; ?>


                            <!-- Category Name -->

                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $category['category']
                                );
                                ?>

                            </h3>


                            <!-- Total Books -->

                            <span>

                                <?php

                                $totalBooks = (int) $category['total_books'];

                                echo $totalBooks;

                                echo ($totalBooks == 1)
                                    ? " Book Available"
                                    : " Books Available";

                                ?>

                            </span>

                        </a>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p>
                        No categories available yet.
                    </p>

                <?php endif; ?>

            </div>

        </div>

    </main>


    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        <div class="container">

            <div class="footer-content">


                <!-- Footer Logo -->

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


                <!-- Footer Links -->

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
                            <a href="about.php">
                                About
                            </a>
                        </li>

                    </ul>

                </div>


                <!-- Footer Contact -->

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


            <!-- Footer Bottom -->

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