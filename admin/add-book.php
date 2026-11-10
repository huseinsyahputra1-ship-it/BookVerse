<?php
require_once __DIR__ . '/admin-session.php';

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
// GET CATEGORIES
// =========================================================

$categoriesQuery = mysqli_query(
    $conn,
    "SELECT name FROM categories ORDER BY name ASC"
);

if (!$categoriesQuery) {
    die("Gagal mengambil data kategori: " . mysqli_error($conn));
}

$categories = [];

while ($categoryRow = mysqli_fetch_assoc($categoriesQuery)) {
    $categories[] = $categoryRow['name'];
}

// =========================================================
// ADD BOOK PROCESS
// =========================================================

if (isset($_POST['add_book'])) {

    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $price = trim($_POST['price']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;

    // VALIDATION

    if (
        empty($title) ||
        empty($author) ||
        empty($price) ||
        empty($category) ||
        empty($description)
    ) {
        echo "<script>
            alert('Semua data buku wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // CATEGORY VALIDATION

    if (!in_array($category, $categories, true)) {
        echo "<script>
            alert('Kategori tidak valid! Silakan pilih kategori yang tersedia.');
            window.history.back();
        </script>";
        exit();
    }

    // STOCK VALIDATION

    if ($stock < 0) {
        echo "<script>
            alert('Jumlah stok tidak boleh kurang dari 0!');
            window.history.back();
        </script>";
        exit();
    }

    // PRICE VALIDATION

    if (!is_numeric($price) || (float) $price < 0) {
        echo "<script>
            alert('Harga buku tidak valid!');
            window.history.back();
        </script>";
        exit();
    }

    // IMAGE UPLOAD

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        echo "<script>
            alert('Cover buku wajib diupload!');
            window.history.back();
        </script>";
        exit();
    }

    $image = $_FILES['image'];
    $imageName = $image['name'];
    $imageTmp = $image['tmp_name'];
    $imageSize = $image['size'];

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    $imageExtension = strtolower(
        pathinfo($imageName, PATHINFO_EXTENSION)
    );

    // CHECK IMAGE EXTENSION

    if (!in_array($imageExtension, $allowedExtensions, true)) {
        echo "<script>
            alert('Format gambar tidak valid! Gunakan JPG, JPEG, PNG, atau WEBP.');
            window.history.back();
        </script>";
        exit();
    }

    // CHECK IMAGE SIZE (MAXIMUM 2 MB)

    if ($imageSize > 2 * 1024 * 1024) {
        echo "<script>
            alert('Ukuran cover terlalu besar! Maksimal 2 MB.');
            window.history.back();
        </script>";
        exit();
    }

    // UPLOAD DIRECTORY

    $uploadDirectory = "../assets/img/books/";

    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }

    // GENERATE UNIQUE IMAGE NAME

    $newImageName = uniqid('book_', true) . '.' . $imageExtension;
    $imagePath = $uploadDirectory . $newImageName;

    // MOVE IMAGE

    if (!move_uploaded_file($imageTmp, $imagePath)) {
        echo "<script>
            alert('Cover buku gagal diupload!');
            window.history.back();
        </script>";
        exit();
    }

    // DATABASE IMAGE PATH

    $databaseImagePath = "assets/img/books/" . $newImageName;

    // INSERT BOOK + STOCK HISTORY

    mysqli_begin_transaction($conn);

    try {

        $query = mysqli_prepare(
            $conn,
            "INSERT INTO books
            (
                title,
                author,
                price,
                image,
                category,
                description,
                is_featured,
                stock
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$query) {
            throw new Exception("Gagal menyiapkan data buku.");
        }

        mysqli_stmt_bind_param(
            $query,
            "ssdsssii",
            $title,
            $author,
            $price,
            $databaseImagePath,
            $category,
            $description,
            $is_featured,
            $stock
        );

        if (!mysqli_stmt_execute($query)) {
            throw new Exception("Gagal menambahkan buku.");
        }

        // Ambil ID buku yang baru ditambahkan

        $book_id = mysqli_insert_id($conn);

        mysqli_stmt_close($query);

        // Catat stok awal jika lebih dari 0

        if ($stock > 0) {

            $activity_type = "Stock In";
            $stock_before = 0;
            $stock_after = $stock;
            $notes = "Initial stock saat buku ditambahkan";
            $admin_id = (int) $_SESSION['user_id'];

            $history_stmt = mysqli_prepare(
                $conn,
                "INSERT INTO stock_history
                (
                    book_id,
                    user_id,
                    activity_type,
                    quantity,
                    stock_before,
                    stock_after,
                    notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$history_stmt) {
                throw new Exception("Gagal menyiapkan riwayat stok.");
            }

            mysqli_stmt_bind_param(
                $history_stmt,
                "iisiiis",
                $book_id,
                $admin_id,
                $activity_type,
                $stock,
                $stock_before,
                $stock_after,
                $notes
            );

            if (!mysqli_stmt_execute($history_stmt)) {
                throw new Exception("Gagal menyimpan riwayat stok.");
            }

            mysqli_stmt_close($history_stmt);
        }

        // Simpan seluruh perubahan

        mysqli_commit($conn);

        echo "<script>
            alert('Buku berhasil ditambahkan!');
            window.location='books.php';
        </script>";
        exit();

    } catch (Exception $e) {

        mysqli_rollback($conn);

        // Hapus gambar jika transaksi gagal

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        echo "<script>
            alert('Buku gagal ditambahkan: " .
            addslashes($e->getMessage()) .
            "');
            window.history.back();
        </script>";
        exit();
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

    <title>Add New Book - BookVerse</title>

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

    <!-- ADMIN HEADER -->

    <header class="admin-header">

        <div class="admin-header-left">

            <a
                href="index.php"
                class="admin-logo">

                <img
                    src="../assets/img/hugeicons_book-open-02.png"
                    alt="BookVerse Logo">

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
                href="logout.php"
                class="admin-logout">

                <i class="fa-solid fa-right-from-bracket"></i>
                Logout

            </a>

        </div>

    </header>

    <!-- ADMIN LAYOUT -->

    <div class="admin-layout">

        <!-- SIDEBAR -->

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

                <!-- MANAGE CATEGORIES -->

                <a
                    href="categories.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-tags"></i>
                    <span>Manage Categories</span>

                </a>

                <a
                    href="orders.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-box"></i>
                    <span>Manage Orders</span>

                </a>

                <a
                    href="payment-transactions.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-credit-card"></i>
                    <span>Payment Transactions</span>

                </a>
                
                <a
                    href="return-transactions.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-undo"></i>
                    <span>Return Management</span>

                </a>

                <a
                    href="users.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-users"></i>
                    <span>Manage Users</span>

                </a>

                <!-- MESSAGES -->

                <a
                    href="messages.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-envelope"></i>
                    <span>Messages</span>

                </a>

                <!-- STOCK HISTORY -->

                <a
                    href="stock-history.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Stock History</span>

                </a>

            </nav>

            <div class="admin-sidebar-bottom">

                <a
                    href="books.php"
                    class="admin-back-home">

                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to Books</span>

                </a>

            </div>

        </aside>

        <!-- MAIN CONTENT -->

        <main class="admin-main">

            <div class="admin-page-header">

                <div>

                    <span class="admin-page-label">
                        BOOK MANAGEMENT
                    </span>

                    <h1>
                        Add New Book
                    </h1>

                    <p>
                        Add a new book to your BookVerse collection.
                    </p>

                </div>

                <a
                    href="books.php"
                    class="admin-back-button">

                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Books

                </a>

            </div>

            <!-- ADD BOOK FORM -->

            <section class="admin-form-section">

                <form
                    action=""
                    method="POST"
                    enctype="multipart/form-data"
                    class="admin-book-form">

                    <!-- Book Cover -->

                    <div class="admin-form-group">

                        <label for="image">
                            Book Cover
                        </label>

                        <div class="admin-upload-box">

                            <i class="fa-solid fa-cloud-arrow-up"></i>

                            <strong>
                                Upload Book Cover
                            </strong>

                            <span>
                                JPG, JPEG, PNG, or WEBP — Max 2 MB
                            </span>

                            <input
                                type="file"
                                id="image"
                                name="image"
                                accept=".jpg,.jpeg,.png,.webp"
                                required>

                        </div>

                    </div>

                    <!-- Title -->

                    <div class="admin-form-group">

                        <label for="title">
                            Book Title
                        </label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            placeholder="Enter book title"
                            required>

                    </div>

                    <!-- Author -->

                    <div class="admin-form-group">

                        <label for="author">
                            Author
                        </label>

                        <input
                            type="text"
                            id="author"
                            name="author"
                            placeholder="Enter author name"
                            required>

                    </div>

                    <!-- Price -->

                    <div class="admin-form-group">

                        <label for="price">
                            Price
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            placeholder="Enter book price"
                            min="0"
                            required>

                    </div>

                    <!-- Stock -->

                    <div class="admin-form-group">

                        <label for="stock">
                            Book Stock
                        </label>

                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            placeholder="Enter available stock"
                            min="0"
                            value="0"
                            required>

                    </div>

                    <!-- Category -->

                    <div class="admin-form-group">

                        <label for="category">
                            Category
                        </label>

                        <select
                            id="category"
                            name="category"
                            required
                            style="width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;">

                            <option value="">
                                Select Book Category
                            </option>

                            <?php foreach ($categories as $categoryName): ?>

                                <option
                                    value="<?php echo htmlspecialchars($categoryName); ?>">

                                    <?php echo htmlspecialchars($categoryName); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- Description -->

                    <div class="admin-form-group">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="6"
                            placeholder="Write a short description about this book..."
                            required></textarea>

                    </div>

                    <!-- Featured Book -->

                    <div class="admin-form-group">

                        <label>Featured Book</label>

                        <div class="featured-checkbox">

                            <input
                                type="checkbox"
                                name="is_featured"
                                id="is_featured"
                                value="1">

                            <label for="is_featured">
                                Display this book on the homepage
                            </label>

                        </div>

                    </div>

                    <!-- Form Actions -->

                    <div class="admin-form-actions">

                        <a
                            href="books.php"
                            class="admin-form-cancel">

                            Cancel

                        </a>

                        <button
                            type="submit"
                            name="add_book"
                            class="admin-form-submit">

                            <i class="fa-solid fa-plus"></i>
                            Add Book

                        </button>

                    </div>

                </form>

            </section>

        </main>

    </div>

</body>

</html>