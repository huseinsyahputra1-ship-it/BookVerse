
<?php
session_start();

include "config/database.php";

$status = $_GET['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us - BookVerse</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Contact CSS -->
    <link rel="stylesheet" href="assets/css/contact.css">
</head>

<body>

    <!-- HEADER -->
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

                <!-- Navigation -->
                <nav>
                    <ul class="menu">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="books.php">Books</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php" class="active">Contact</a></li>
                    </ul>
                </nav>

                <!-- Right Menu -->
                <div class="right-menu">

                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchInput" placeholder="Search Books...">
                    </div>

                    <a href="wishlist.php" class="icon-btn">
                        <i class="fa-regular fa-heart"></i>
                    </a>

                    <a href="cart.php" class="icon-btn">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>

                        <div class="profile-menu">
                            <button type="button" class="profile-btn" id="profileBtn">
                                <i class="fa-solid fa-user"></i>

                                <span>
                                    <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                                </span>

                                <i class="fa-solid fa-chevron-down"></i>
                            </button>

                            <div class="profile-dropdown" id="profileDropdown">

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

                                <a href="logout.php" class="logout-link">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    <span>Logout</span>
                                </a>

                            </div>
                        </div>

                    <?php else: ?>

                        <a href="login.php" class="login-btn">
                            Login
                        </a>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </header>


    <!-- CONTACT PAGE -->
    <main class="contact-page">

        <div class="container contact-container">

            <!-- HERO -->
            <section class="contact-hero">

                <span class="contact-label">
                    CONTACT BOOKVERSE
                </span>

                <h1>
                    Get in Touch<br>
                    With <span>Us.</span>
                </h1>

                <p>
                    Have a question, suggestion, or need assistance?
                    We'd love to hear from you. Feel free to reach out
                    to the BookVerse team.
                </p>

            </section>


            <!-- CONTACT CONTENT -->
            <section class="contact-content">

                <!-- CONTACT INFORMATION -->
                <div class="contact-info">

                    <h2>Contact Information</h2>

                    <p>
                        Feel free to contact us through the information
                        provided below. We're happy to hear from you.
                    </p>

                    <!-- EMAIL -->
                    <div class="contact-info-item">

                        <div class="contact-info-icon">
                            <i class="fa-solid fa-envelope"></i>
                        </div>

                        <div>
                            <h3>Email Address</h3>
                            <p>info@bookverse.com</p>
                        </div>

                    </div>

                    <!-- PHONE -->
                    <div class="contact-info-item">

                        <div class="contact-info-icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>

                        <div>
                            <h3>Phone Number</h3>
                            <p>+62 812 3456 7890</p>
                        </div>

                    </div>

                    <!-- LOCATION -->
                    <div class="contact-info-item">

                        <div class="contact-info-icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>

                        <div>
                            <h3>Our Location</h3>
                            <p>Jakarta, Indonesia</p>
                        </div>

                    </div>

                    <!-- SOCIAL MEDIA -->
                    <div class="contact-social">

                        <h3>Follow Us</h3>

                        <div class="contact-social-links">

                            <a href="#" aria-label="Instagram">
                                <i class="fa-brands fa-instagram"></i>
                            </a>

                            <a href="#" aria-label="Facebook">
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>

                            <a href="#" aria-label="Twitter">
                                <i class="fa-brands fa-x-twitter"></i>
                            </a>

                        </div>

                    </div>

                </div>


                <!-- CONTACT FORM -->
                <div class="contact-form-wrapper">

                    <h2>Send Us a Message</h2>

                    <p>
                        Fill in the form below and tell us how we can help you.
                    </p>

                    <!-- NOTIFICATION -->
                    <?php if ($status === 'success'): ?>

                        <div class="contact-alert success">
                            <i class="fa-solid fa-circle-check"></i>
                            Your message has been sent successfully!
                            Thank you for contacting BookVerse.
                        </div>

                    <?php elseif ($status === 'empty'): ?>

                        <div class="contact-alert error">
                            Please complete all fields before sending your message.
                        </div>

                    <?php elseif ($status === 'invalid_email'): ?>

                        <div class="contact-alert error">
                            Please enter a valid email address.
                        </div>

                    <?php elseif ($status === 'error'): ?>

                        <div class="contact-alert error">
                            Sorry, your message could not be sent.
                            Please try again.
                        </div>

                    <?php endif; ?>


                    <!-- FORM -->
                    <form
                        class="contact-form"
                        id="contactForm"
                        action="send-message.php"
                        method="POST">

                        <!-- NAME -->
                        <div class="contact-form-group">

                            <label for="contactName">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="contactName"
                                name="name"
                                placeholder="Enter your full name"
                                maxlength="100"
                                required>

                        </div>


                        <!-- EMAIL -->
                        <div class="contact-form-group">

                            <label for="contactEmail">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="contactEmail"
                                name="email"
                                placeholder="Enter your email address"
                                maxlength="150"
                                required>

                        </div>


                        <!-- SUBJECT -->
                        <div class="contact-form-group">

                            <label for="contactSubject">
                                Subject
                            </label>

                            <input
                                type="text"
                                id="contactSubject"
                                name="subject"
                                placeholder="Enter message subject"
                                maxlength="200"
                                required>

                        </div>


                        <!-- MESSAGE -->
                        <div class="contact-form-group">

                            <label for="contactMessage">
                                Your Message
                            </label>

                            <textarea
                                id="contactMessage"
                                name="message"
                                placeholder="Write your message here..."
                                required></textarea>

                        </div>


                        <!-- SUBMIT -->
                        <button
                            type="submit"
                            class="contact-submit">

                            Send Message

                            <i class="fa-solid fa-paper-plane"></i>

                        </button>

                    </form>

                </div>

            </section>


            <!-- CLOSING -->
            <section class="contact-closing">

                <h2>
                    We're Always Happy to Hear From You!
                </h2>

                <p>
                    Thank you for being part of the BookVerse community.
                    Your feedback and suggestions mean a lot to us.
                </p>

            </section>

        </div>

    </main>


    <!-- FOOTER -->
    <footer class="footer">

        <div class="container">

            <div class="footer-content">

                <!-- FOOTER LOGO -->
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


                <!-- FOOTER LINKS -->
                <div class="footer-links">

                    <h3>Quick Links</h3>

                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="books.php">Books</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="about.php">About</a></li>
                    </ul>

                </div>


                <!-- FOOTER CONTACT -->
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


    <!-- JAVASCRIPT -->
    <script src="assets/js/script.js"></script>

</body>
</html>
