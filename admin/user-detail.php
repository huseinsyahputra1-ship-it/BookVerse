<?php

session_start();

include "../config/database.php";

// =========================================================
// ADMIN ACCESS
// =========================================================

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

// =========================================================
// GET USER ID
// =========================================================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    echo "<script>
        alert('ID user tidak valid!');
        window.location='users.php';
    </script>";

    exit();

}

$userId = (int) $_GET['id'];

// =========================================================
// GET USER DATA
// =========================================================

$userStmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        fullname,
        email,
        role,
        created_at
     FROM users
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $userStmt,
    "i",
    $userId
);

mysqli_stmt_execute($userStmt);

$userResult = mysqli_stmt_get_result($userStmt);

if (mysqli_num_rows($userResult) === 0) {

    mysqli_stmt_close($userStmt);

    echo "<script>
        alert('User tidak ditemukan!');
        window.location='users.php';
    </script>";

    exit();

}

$user = mysqli_fetch_assoc($userResult);

mysqli_stmt_close($userStmt);

// =========================================================
// UPDATE USER ROLE
// =========================================================

if (isset($_POST['update_role'])) {

    $newRole = $_POST['role'];

    $allowedRoles = [
        'user',
        'admin'
    ];

    // =====================================================
    // VALIDATE ROLE
    // =====================================================

    if (!in_array($newRole, $allowedRoles, true)) {

        echo "<script>
            alert('Role tidak valid!');
        </script>";

    }

    // =====================================================
    // PREVENT ADMIN FROM CHANGING OWN ROLE
    // =====================================================

    elseif ($userId === (int) $_SESSION['user_id']) {

        echo "<script>
            alert('Anda tidak dapat mengubah role akun Anda sendiri!');
        </script>";

    }

    // =====================================================
    // UPDATE ROLE
    // =====================================================

    else {

        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET role = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            "si",
            $newRole,
            $userId
        );

        if (mysqli_stmt_execute($updateStmt)) {

            mysqli_stmt_close($updateStmt);

            echo "<script>
                alert('Role user berhasil diperbarui!');
                window.location='user-detail.php?id=$userId';
            </script>";

            exit();

        }

        mysqli_stmt_close($updateStmt);

        echo "<script>
            alert('Gagal memperbarui role user!');
        </script>";

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        User Detail - BookVerse Admin
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
        href="../assets/css/style.css">

</head>


<body>


    <!-- =====================================================
         ADMIN HEADER
         ===================================================== -->

    <header class="admin-header">

        <div class="admin-header-left">

            <a
                href="index.php"
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


    <!-- =====================================================
         ADMIN LAYOUT
         ===================================================== -->

    <div class="admin-layout">


        <!-- =================================================
             SIDEBAR
             ================================================= -->

        <aside class="admin-sidebar">

            <nav class="admin-nav">

                <a
                    href="index.php"
                    class="admin-nav-link">

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
                    class="admin-nav-link active">

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


        <!-- =================================================
             MAIN CONTENT
             ================================================= -->

        <main class="admin-main">


            <!-- =================================================
                 PAGE HEADER
                 ================================================= -->

            <div class="admin-page-header">

                <div>

                    <span class="admin-page-label">
                        USER MANAGEMENT
                    </span>

                    <h1>
                        User Detail
                    </h1>

                    <p>
                        View user information and manage account role.
                    </p>

                </div>


                <a
                    href="users.php"
                    class="admin-back-button">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Users

                </a>

            </div>


            <!-- =================================================
                 USER PROFILE
                 ================================================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            USER PROFILE
                        </span>

                        <h2>
                            Account Information
                        </h2>

                    </div>

                </div>


                <div class="admin-user-detail-card">


                    <!-- USER AVATAR -->

                    <div class="admin-user-detail-avatar">

                        <i class="fa-solid fa-user"></i>

                    </div>


                    <!-- USER NAME -->

                    <div class="admin-user-detail-main">

                        <h2>

                            <?php echo htmlspecialchars(
                                $user['fullname']
                            ); ?>

                        </h2>

                        <p>

                            <?php echo htmlspecialchars(
                                $user['email']
                            ); ?>

                        </p>

                    </div>


                    <!-- ROLE -->

                    <div class="admin-user-detail-role">

                        <?php if ($user['role'] === 'admin'): ?>

                            <span class="admin-user-role admin">
                                Admin
                            </span>

                        <?php else: ?>

                            <span class="admin-user-role user">
                                User
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 USER INFORMATION
                 ================================================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            ACCOUNT INFORMATION
                        </span>

                        <h2>
                            User Information
                        </h2>

                    </div>

                </div>


                <div class="admin-user-info-grid">


                    <!-- USER ID -->

                    <div class="admin-user-info-card">

                        <span>
                            User ID
                        </span>

                        <strong>
                            #<?php echo $user['id']; ?>
                        </strong>

                    </div>


                    <!-- FULL NAME -->

                    <div class="admin-user-info-card">

                        <span>
                            Full Name
                        </span>

                        <strong>
                            <?php echo htmlspecialchars(
                                $user['fullname']
                            ); ?>
                        </strong>

                    </div>


                    <!-- EMAIL -->

                    <div class="admin-user-info-card">

                        <span>
                            Email
                        </span>

                        <strong>
                            <?php echo htmlspecialchars(
                                $user['email']
                            ); ?>
                        </strong>

                    </div>


                    <!-- JOINED -->

                    <div class="admin-user-info-card">

                        <span>
                            Joined
                        </span>

                        <strong>
                            <?php echo date(
                                'd M Y',
                                strtotime($user['created_at'])
                            ); ?>
                        </strong>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 CHANGE ROLE
                 ================================================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            ROLE MANAGEMENT
                        </span>

                        <h2>
                            Change User Role
                        </h2>

                    </div>

                </div>


                <form
                    method="POST"
                    class="admin-user-role-form">


                    <div class="admin-role-select">

                        <label for="role">
                            Account Role
                        </label>

                        <select
                            id="role"
                            name="role"
                            required>

                            <option
                                value="user"
                                <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>

                                User

                            </option>

                            <option
                                value="admin"
                                <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>

                                Admin

                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        name="update_role"
                        class="admin-form-submit">

                        <i class="fa-solid fa-user-shield"></i>

                        Update Role

                    </button>

                </form>


                <?php if ($userId === (int) $_SESSION['user_id']): ?>

                    <div class="admin-role-warning">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                        <span>
                            Anda sedang melihat akun yang sedang login.
                            Role akun ini tidak dapat diubah dari halaman ini.
                        </span>

                    </div>

                <?php endif; ?>

            </section>


        </main>

    </div>


</body>

</html>