<?php
include "config/database.php";

if (isset($_POST['register'])) {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password != $confirm_password) {
    echo "<script>
        alert('Password dan Confirm Password tidak sama!');
    </script>";

    exit();
}

$check_email = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

if (mysqli_num_rows($check_email) > 0) {

    echo "<script>
        alert('Email sudah terdaftar!');
    </script>";

    exit();
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$query = mysqli_query($conn, "INSERT INTO users
(fullname, email, password)
VALUES
('$fullname', '$email', '$password_hash')");
if ($query) {

    echo "<script>
        alert('Register berhasil!');
        window.location='login.php';
    </script>";

} else {

    echo "<script>
        alert('Register gagal!');
    </script>";

}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- ==========================================
                    META TAG
    =========================================== -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | BookVerse</title>

    <!-- ==========================================
                    GOOGLE FONT
    =========================================== -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- ==========================================
                    FONT AWESOME
    =========================================== -->

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- ==========================================
                        CSS
    =========================================== -->

    <link rel="stylesheet" href="assets/css/auth.css?v=10">

</head>

<body class="register-body">

    <!-- ==========================================
                REGISTER PAGE
    =========================================== -->

    <section class="login-page register-page">

        <div class="login-container">

            <!-- ==========================================
                        LEFT SIDE
            =========================================== -->

            <div class="login-left">

                <!-- Back Button -->

                <a href="index.php" class="back-home">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Home

                </a>

                <!-- Register Content -->

                <div class="login-content">

                    <span class="login-tag">

                        🚀 Join BookVerse

                    </span>

                    <h1>

                        Create <span>Account</span>

                    </h1>

                    <p>

                        Create your BookVerse account and start your reading
                        journey with thousands of inspiring books.

                    </p>

                    <!-- ==========================================
                            REGISTER FORM
                    =========================================== -->

                    <form action="" method="POST">

                        <!-- Full Name -->

                        <div class="input-box">

                            <label for="fullname">

                                Full Name

                            </label>

                            <div class="input-field">

                                <i class="fa-solid fa-user"></i>

                                <input
                                    type="text"
                                    id="fullname"
                                    name="fullname"
                                    placeholder="Enter your full name"
                                    required>

                            </div>

                        </div>

                        <!-- Email -->

                        <div class="input-box">

                            <label for="email">

                                Email Address

                            </label>

                            <div class="input-field">

                                <i class="fa-solid fa-envelope"></i>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    placeholder="Enter your email"
                                    required>

                            </div>

                        </div>

                        <!-- Password -->

                        <div class="input-box">

                            <label for="password">

                                Password

                            </label>

                            <div class="input-field">

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Create a password"
                                    required>

                                <i class="fa-solid fa-eye eye-icon"
                                    id="togglePassword"></i>

                            </div>

                        </div>

                        <!-- Confirm Password -->

                        <div class="input-box">

                            <label for="confirm_password">

                                Confirm Password

                            </label>

                            <div class="input-field">

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Confirm your password"
                                    required>

                                <i class="fa-solid fa-eye eye-icon"
                                    id="toggleConfirmPassword"></i>

                            </div>

                        </div>

                        <div class="login-option">

                            <label>

                                <input
                                    type="checkbox"
                                    name="terms"
                                    required>

                                I agree to the Terms & Conditions

                            </label>

                        </div>

                        <button
                            type="submit"
                            name="register"
                            class="login-btn">

                            Create Account

                        </button>

                    </form>

                    <div class="register-link">

                        Already have an account?

                        <a href="login.php">

                            Login

                        </a>
                    </div>
</div> <!-- login-content -->

                </div> <!-- login-left -->

                <!-- RIGHT -->

                <div class="login-right">

    <img
    src="assets/img/hero-library.jpeg"
    alt="Library">

    <div class="overlay">

        <h2>

            Join Thousands of Readers

        </h2>

        <p>

            Create your account today and enjoy thousands of books,
            exciting stories, and a modern reading experience with
            BookVerse.

        </p>

    </div>

</div>

</section>

<script src="assets/js/auth.js"></script>

</body>

</html>