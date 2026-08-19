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
// GET BOOKS
// =========================================================

$booksQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM books
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

    <title>Manage Books - BookVerse</title>


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
                    class="admin-nav-link active">

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
                    class="admin-nav-link">

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
                        BOOK MANAGEMENT
                    </span>

                    <h1>
                        Manage Books
                    </h1>

                    <p>
                        Manage all books available in your BookVerse store.
                    </p>

                </div>


                <a
                    href="add-book.php"
                    class="admin-add-button">

                    <i class="fa-solid fa-plus"></i>

                    Add New Book

                </a>

            </div>


            <!-- =================================================
                 BOOK SEARCH
                 ================================================= -->

            <div class="admin-books-search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    id="adminBookSearch"
                    placeholder="Search books...">

            </div>


            <!-- =================================================
                 BOOK SUMMARY
                 ================================================= -->

            <div class="admin-book-summary">

                <div>

                    <i class="fa-solid fa-book"></i>

                    <div>

                        <span>Total Books</span>

                        <strong>
                            <?php echo mysqli_num_rows($booksQuery); ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 BOOK TABLE
                 ================================================= -->

            <section class="admin-section">

                <div class="admin-section-header">

                    <div>

                        <span class="admin-section-label">
                            BOOK COLLECTION
                        </span>

                        <h2>
                            All Books
                        </h2>

                    </div>

                </div>


                <div class="admin-books-table-wrapper">

                    <table class="admin-books-table">

                        <thead>

                            <tr>

                                <th>
                                    Cover
                                </th>

                                <th>
                                    Title
                                </th>

                                <th>
                                    Author
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody id="adminBookTableBody">

                            <?php if (mysqli_num_rows($booksQuery) > 0): ?>

                                <?php while ($book = mysqli_fetch_assoc($booksQuery)): ?>

                                    <tr class="admin-book-row">

                                        <td>

                                            <img
                                                src="../<?php echo htmlspecialchars($book['image']); ?>"
                                                alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                class="admin-book-cover">

                                        </td>


                                        <td>

                                            <strong class="admin-book-title">

                                                <?php echo htmlspecialchars($book['title']); ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?php echo htmlspecialchars($book['author']); ?>

                                        </td>


                                        <td>

                                            <?php if (!empty($book['category'])): ?>

                                                <span class="admin-category">

                                                    <?php echo htmlspecialchars($book['category']); ?>

                                                </span>

                                            <?php else: ?>

                                                <span class="admin-no-category">
                                                    -
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <strong class="admin-table-price">

                                                Rp<?php echo number_format(
                                                    $book['price'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ); ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <div class="admin-book-actions">

                                                <a
                                                    href="edit-book.php?id=<?php echo $book['id']; ?>"
                                                    class="admin-action-button edit">

                                                    <i class="fa-solid fa-pen"></i>

                                                    Edit

                                                </a>


                                                <a
                                                    href="delete-book.php?id=<?php echo $book['id']; ?>"
                                                    class="admin-action-button delete"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini?');">

                                                    <i class="fa-solid fa-trash"></i>

                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="6"
                                        class="admin-empty-books">

                                        <i class="fa-solid fa-book-open"></i>

                                        <strong>
                                            No books found
                                        </strong>

                                        <span>
                                            Your book collection is currently empty.
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
         ADMIN BOOK SEARCH SCRIPT
         ===================================================== -->

    <script>

        const adminBookSearch = document.getElementById("adminBookSearch");

        const adminBookRows = document.querySelectorAll(".admin-book-row");


        if (adminBookSearch) {

            adminBookSearch.addEventListener("keyup", function () {

                const keyword = this.value.toLowerCase().trim();


                adminBookRows.forEach(row => {

                    const title = row
                        .querySelector(".admin-book-title")
                        .textContent
                        .toLowerCase();

                    const author = row
                        .children[2]
                        .textContent
                        .toLowerCase();

                    const category = row
                        .children[3]
                        .textContent
                        .toLowerCase();


                    if (
                        title.includes(keyword) ||
                        author.includes(keyword) ||
                        category.includes(keyword)
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