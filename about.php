<?php

session_start();

include "config/database.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>About Us - BookVerse</title>

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


    <!-- ================= ABOUT PAGE CSS ================= -->

    <style>

        /* ABOUT PAGE */

        .about-page {
            padding: 65px 0 80px;
        }

        .about-container {
            max-width: 1150px;
            margin: auto;
        }


        /* HERO SECTION */

        .about-hero {
            background: linear-gradient(135deg, #f5f0ff, #ffffff);
            padding: 55px 45px;
            border-radius: 25px;
            margin-bottom: 55px;
            border: 1px solid #eee7ff;
        }

        .about-label {
            display: inline-block;
            color: #6c3ef4;
            background: #eee8ff;
            padding: 8px 17px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .about-hero h1 {
            font-size: 38px;
            font-weight: 700;
            margin-bottom: 18px;
            color: #222;
            line-height: 1.35;
        }

        .about-hero h1 span {
            color: #6c3ef4;
        }

        .about-hero p {
            max-width: 650px;
            font-size: 15px;
            line-height: 1.9;
            color: #777;
        }


        /* STORY & MISSION */

        .about-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 65px;
        }

        .about-info-card {
            background: #fff;
            padding: 32px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,.06);
            border: 1px solid #f0f0f0;
            transition: .3s;
        }

        .about-info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,.09);
        }

        .about-icon {
            width: 52px;
            height: 52px;
            background: #eee8ff;
            color: #6c3ef4;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            font-size: 22px;
            margin-bottom: 22px;
        }

        .about-info-card h2 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #222;
        }

        .about-info-card p {
            font-size: 14px;
            line-height: 1.9;
            color: #777;
        }


        /* WHY CHOOSE BOOKVERSE */

        .about-why {
            margin-bottom: 65px;
        }

        .about-heading {
            text-align: center;
            margin-bottom: 35px;
        }

        .about-heading h2 {
            font-size: 30px;
            margin-bottom: 12px;
            color: #222;
        }

        .about-heading p {
            color: #888;
            font-size: 14px;
        }

        .about-feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .about-feature-card {
            background: #fff;
            padding: 35px 25px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid #f0f0f0;
            box-shadow: 0 8px 30px rgba(0,0,0,.06);
            transition: .3s;
        }

        .about-feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,.10);
        }

        .about-feature-card i {
            font-size: 35px;
            color: #6c3ef4;
            margin-bottom: 20px;
        }

        .about-feature-card h3 {
            font-size: 17px;
            margin-bottom: 12px;
        }

        .about-feature-card p {
            color: #888;
            font-size: 13px;
            line-height: 1.8;
        }


        /* CLOSING SECTION */

        .about-closing {
            background: #6c3ef4;
            padding: 45px 25px;
            border-radius: 22px;
            text-align: center;
            color: white;
        }

        .about-closing h2 {
            font-size: 27px;
            margin-bottom: 12px;
        }

        .about-closing p {
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 25px;
            color: #eee7ff;
        }

        .about-closing a {
            display: inline-block;
            background: white;
            color: #6c3ef4;
            padding: 13px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: .3s;
        }

        .about-closing a:hover {
            transform: translateY(-3px);
        }


        /* RESPONSIVE */

        @media (max-width: 768px) {

            .about-page {
                padding: 35px 0;
            }

            .about-hero {
                padding: 35px 25px;
            }

            .about-hero h1 {
                font-size: 29px;
            }

            .about-info-grid,
            .about-feature-grid {
                grid-template-columns: 1fr;
            }

            .about-heading h2 {
                font-size: 25px;
            }

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

                        <h2>BookVerse</h2>

                        <p>Every Book Has a Story</p>

                    </div>

                </div>


                <!-- Navigation -->

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
                            <a href="categories.php">
                                Categories
                            </a>
                        </li>

                        <li>
                            <a href="about.php" class="active">
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


    <!-- ================= ABOUT PAGE ================= -->

    <main class="about-page">

        <div class="container about-container">


            <!-- HERO SECTION -->

            <section class="about-hero">

                <span class="about-label">
                    ABOUT BOOKVERSE
                </span>

                <h1>
                    Discover the World<br>
                    Through <span>Books.</span>
                </h1>

                <p>
                    Every Book Has a Story. BookVerse is your destination
                    to discover inspiring stories, explore new knowledge,
                    and find your next favorite book.
                </p>

            </section>


            <!-- STORY & MISSION -->

            <section class="about-info-grid">


                <!-- OUR STORY -->

                <div class="about-info-card">

                    <div class="about-icon">

                        <i class="fa-solid fa-book-open"></i>

                    </div>

                    <h2>Our Story</h2>

                    <p>
                        BookVerse is an online bookstore created to make
                        discovering and exploring books easier and more enjoyable.

                        We believe every book has its own story, knowledge,
                        and inspiration. Through BookVerse, readers can explore
                        different collections and find books that match
                        their interests.
                    </p>

                </div>


                <!-- OUR MISSION -->

                <div class="about-info-card">

                    <div class="about-icon">

                        <i class="fa-solid fa-bullseye"></i>

                    </div>

                    <h2>Our Mission</h2>

                    <p>
                        Our mission is to provide a convenient and enjoyable
                        online shopping experience for book lovers.

                        We aim to help readers discover inspiring stories,
                        gain new knowledge, and build a stronger connection
                        with the world of reading.
                    </p>

                </div>


            </section>


            <!-- WHY CHOOSE BOOKVERSE -->

            <section class="about-why">


                <div class="about-heading">

                    <h2>Why Choose BookVerse?</h2>

                    <p>
                        Here's what makes BookVerse a great place for book lovers.
                    </p>

                </div>


                <div class="about-feature-grid">


                    <!-- FEATURE 1 -->

                    <div class="about-feature-card">

                        <i class="fa-solid fa-book"></i>

                        <h3>Diverse Collections</h3>

                        <p>
                            Explore books from various categories
                            and genres to discover something new.
                        </p>

                    </div>


                    <!-- FEATURE 2 -->

                    <div class="about-feature-card">

                        <i class="fa-solid fa-cart-shopping"></i>

                        <h3>Easy Shopping</h3>

                        <p>
                            Enjoy a simple and convenient experience
                            when finding and purchasing your books.
                        </p>

                    </div>


                    <!-- FEATURE 3 -->

                    <div class="about-feature-card">

                        <i class="fa-solid fa-heart"></i>

                        <h3>For Book Lovers</h3>

                        <p>
                            A place for readers to discover stories
                            and their next favorite book.
                        </p>

                    </div>


                </div>

            </section>


            <!-- CLOSING SECTION -->

            <section class="about-closing">

                <h2>
                    Every Book Has a Story.
                </h2>

                <p>
                    Start your reading journey and discover
                    your next favorite book with BookVerse.
                </p>

                <a href="books.php">

                    Explore Books

                    <i class="fa-solid fa-arrow-right"></i>

                </a>

            </section>


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

                    <h2>BookVerse</h2>

                    <p>
                        Every Book Has a Story.
                        Discover books that inspire your journey.
                    </p>

                </div>


                <!-- Footer Links -->

                <div class="footer-links">

                    <h3>Quick Links</h3>

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

                    <h3>Contact</h3>

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


    <!-- JAVASCRIPT -->

    <script src="assets/js/script.js"></script>


</body>

</html>