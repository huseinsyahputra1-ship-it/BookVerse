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
// GET USERS
// =========================================================

$usersQuery = mysqli_query(
    $conn,
    "SELECT
        id,
        fullname,
        email,
        role,
        created_at
     FROM users
     ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Manage Users - BookVerse
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
                        Manage Users
                    </h1>

                    <p>
                        Manage all users registered in your BookVerse store.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 USER SUMMARY
                 ================================================= -->

            <div class="admin-book-summary">

                <div>

                    <i class="fa-solid fa-users"></i>

                    <div>

                        <span>
                            Total Users
                        </span>

                        <strong>
                            <?php echo mysqli_num_rows($usersQuery); ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 USER TABLE
                 ================================================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            USER COLLECTION
                        </span>

                        <h2>
                            All Users
                        </h2>

                    </div>

                </div>


                <!-- ================= USER SEARCH ================= -->

                <div class="admin-users-search">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="adminUserSearch"
                        placeholder="Search users...">

                </div>


                <div class="admin-users-table-wrapper">

                    <table class="admin-users-table">

                        <thead>

                            <tr>

                                <th>
                                    User
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Joined
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (mysqli_num_rows($usersQuery) > 0): ?>

                                <?php while ($user = mysqli_fetch_assoc($usersQuery)): ?>

                                    <tr class="admin-user-row">


                                        <!-- USER -->

                                        <td>

                                            <div class="admin-user-info">

                                                <div class="admin-user-avatar">

                                                    <i class="fa-solid fa-user"></i>

                                                </div>

                                                <strong class="admin-user-name">

                                                    <?php echo htmlspecialchars(
                                                        $user['fullname']
                                                    ); ?>

                                                </strong>

                                            </div>

                                        </td>


                                        <!-- EMAIL -->

                                        <td>

                                            <span class="admin-user-email">

                                                <?php echo htmlspecialchars(
                                                    $user['email']
                                                ); ?>

                                            </span>

                                        </td>


                                        <!-- ROLE -->

                                        <td>

                                            <?php if ($user['role'] === 'admin'): ?>

                                                <span class="admin-user-role admin">
                                                    Admin
                                                </span>

                                            <?php else: ?>

                                                <span class="admin-user-role user">
                                                    User
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- JOINED -->

                                        <td>

                                            <?php echo date(
                                                'd M Y',
                                                strtotime($user['created_at'])
                                            ); ?>

                                        </td>


                                        <!-- ACTION -->

                                        <td>

                                            <div class="admin-user-actions">

                                                <!-- VIEW -->

                                                <a
                                                    href="user-detail.php?id=<?php echo $user['id']; ?>"
                                                    class="admin-action-button edit">

                                                    <i class="fa-solid fa-eye"></i>

                                                    View

                                                </a>


                                                <!-- DELETE -->

                                                <?php if (
                                                    (int) $user['id'] !==
                                                    (int) $_SESSION['user_id']
                                                ): ?>

                                                    <a
                                                        href="delete-user.php?id=<?php echo $user['id']; ?>"
                                                        class="admin-action-button delete"
                                                        onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');">

                                                        <i class="fa-solid fa-trash"></i>

                                                        Delete

                                                    </a>

                                                <?php endif; ?>

                                            </div>

                                        </td>


                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="5"
                                        class="admin-empty-users">

                                        <i class="fa-solid fa-users-slash"></i>

                                        <strong>
                                            No users found
                                        </strong>

                                        <span>
                                            There are currently no registered users.
                                        </span>

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>


        </main>

    </div>


    <!-- =====================================================
         USER SEARCH SCRIPT
         ===================================================== -->

    <script>

        const adminUserSearch =
            document.getElementById("adminUserSearch");

        const adminUserRows =
            document.querySelectorAll(".admin-user-row");


        if (adminUserSearch) {

            adminUserSearch.addEventListener("keyup", function () {

                const keyword =
                    this.value.toLowerCase().trim();


                adminUserRows.forEach(row => {

                    const name =
                        row.querySelector(".admin-user-name")
                            .textContent
                            .toLowerCase();

                    const email =
                        row.querySelector(".admin-user-email")
                            .textContent
                            .toLowerCase();

                    const role =
                        row.querySelector(".admin-user-role")
                            .textContent
                            .toLowerCase();


                    if (
                        name.includes(keyword) ||
                        email.includes(keyword) ||
                        role.includes(keyword)
                    ) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                });

            });

        }

    </script>


</body>

</html>