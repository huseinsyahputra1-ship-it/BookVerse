<?php
session_start();

include "config/database.php";

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");

    if (mysqli_num_rows($query) == 0) {

        echo "<script>
            alert('Email tidak ditemukan!');
            window.location='login.php';
        </script>";

        exit();
    }

    $user = mysqli_fetch_assoc($query);

    if (!password_verify($password, $user['password'])) {

        echo "<script>
            alert('Password salah!');
            window.location='login.php';
        </script>";

        exit();
    }

// ================= LOGIN BERHASIL =================

$_SESSION['user_id'] = $user['id'];
$_SESSION['fullname'] = $user['fullname'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'admin') {

    echo "<script>
        alert('Login berhasil! Selamat datang di Admin Dashboard!');
        window.location='admin/index.php';
    </script>";

} else {

    echo "<script>
        alert('Login berhasil! Selamat datang di BookVerse!');
        window.location='index.php';
    </script>";

}

exit();

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | BookVerse</title>

    <!-- Google Font -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- CSS -->

    <link rel="stylesheet" href="assets/css/auth.css?v=2 ">

</head>

<body>

<section class="login-page">

<div class="login-container">

    <!-- LEFT -->

    <div class="login-left">

        <a href="index.php" class="back-home">

            <i class="fa-solid fa-arrow-left"></i>

            Back to Home

        </a>

        <div class="login-content">

            <span class="login-tag">

                📚 Welcome Back

            </span>

            <h1>

                Login to <span>BookVerse</span>

            </h1>

            <p>

                Continue your reading journey and discover thousands
                of inspiring books waiting for you.

            </p>

            <form action="" method="POST">

                <div class="input-box">

                    <label for="email">

                        Email   Address
         
                    </label>

                    <div class="input-field">

                        <i class="fa-solid fa-envelope"></i>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            placeholder="Enter your email"
                            required>

                    </div>

                </div>

                <div class="input-box">

                    <label for="password">

                        password
                    
                    </label>

                    <div class="input-field">

                        <i class="fa-solid fa-lock"></i>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Enter your password"
                            required>

                        <i class="fa-solid fa-eye eye-icon"
                        id="togglePassword"></i>

                    </div>

                </div>

                <div class="login-option">

                    <label>

                        <input type="checkbox"
                               name="remember_me">

                        Remember Me

                    </label>

                    <a href="#">

                        Forgot Password?

                    </a>

                </div>

                <button
                    type="submit"
                    name="login"
                    class="login-btn">

                    Login

                </button>

            </form>

            <div class="register-link">

                Don't have an account?

                <a href="register.php">

                    Register

                </a>

            </div>

        </div>

    </div>

    <!-- RIGHT -->

    <div class="login-right">

        <img
        src="assets/img/hero-library.jpeg"
        alt="Library">

        <div class="overlay">

            <h2>

                Every Book Has a Story

            </h2>

            <p>

                Sign in to continue exploring thousands of books,
                save your favorites, and enjoy your reading journey.
                

            </p>

        </div>

    </div>

</div>

</section>

<script src="assets/js/auth.js"></script>

</body>

</html>