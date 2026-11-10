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
// CSRF TOKEN
// =========================================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$message = "";
$messageType = "";

// =========================================================
// ADD CATEGORY
// =========================================================

if (isset($_POST['add_category'])) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($csrfToken, $_POST['csrf_token'])
    ) {
        $message = "Permintaan tidak valid!";
        $messageType = "error";

    } else {

        $categoryName = trim($_POST['category_name'] ?? '');

        if ($categoryName === '') {

            $message = "Nama kategori wajib diisi!";
            $messageType = "error";

        } elseif (strlen($categoryName) > 100) {

            $message = "Nama kategori maksimal 100 karakter!";
            $messageType = "error";

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO categories (name) VALUES (?)"
            );

            mysqli_stmt_bind_param($stmt, "s", $categoryName);

            if (mysqli_stmt_execute($stmt)) {

                $message = "Kategori berhasil ditambahkan!";
                $messageType = "success";

            } else {

                $message = mysqli_errno($conn) == 1062
                    ? "Kategori tersebut sudah tersedia!"
                    : "Gagal menambahkan kategori.";

                $messageType = "error";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// =========================================================
// EDIT CATEGORY
// =========================================================

if (isset($_POST['update_category'])) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($csrfToken, $_POST['csrf_token'])
    ) {
        $message = "Permintaan tidak valid!";
        $messageType = "error";

    } else {

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $newCategoryName = trim($_POST['category_name'] ?? '');

        if ($categoryId <= 0 || $newCategoryName === '') {

            $message = "Data kategori tidak valid!";
            $messageType = "error";

        } elseif (strlen($newCategoryName) > 100) {

            $message = "Nama kategori maksimal 100 karakter!";
            $messageType = "error";

        } else {

            mysqli_begin_transaction($conn);

            try {

                // Ambil kategori lama

                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT name FROM categories WHERE id = ? FOR UPDATE"
                );

                mysqli_stmt_bind_param($stmt, "i", $categoryId);
                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);
                $oldCategory = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if (!$oldCategory) {
                    throw new Exception("Kategori tidak ditemukan.");
                }

                $oldCategoryName = $oldCategory['name'];

                // Update kategori

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE categories SET name = ? WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "si",
                    $newCategoryName,
                    $categoryId
                );

                if (!mysqli_stmt_execute($stmt)) {

                    if (mysqli_errno($conn) == 1062) {
                        throw new Exception("Nama kategori sudah digunakan.");
                    }

                    throw new Exception("Gagal memperbarui kategori.");
                }

                mysqli_stmt_close($stmt);

                // Update kategori pada seluruh buku terkait

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE books SET category = ? WHERE category = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ss",
                    $newCategoryName,
                    $oldCategoryName
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Gagal memperbarui kategori buku.");
                }

                mysqli_stmt_close($stmt);

                mysqli_commit($conn);

                $message = "Kategori dan data buku berhasil diperbarui!";
                $messageType = "success";

            } catch (Exception $e) {

                mysqli_rollback($conn);

                $message = $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// =========================================================
// DELETE CATEGORY
// =========================================================

if (isset($_POST['delete_category'])) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($csrfToken, $_POST['csrf_token'])
    ) {
        $message = "Permintaan tidak valid!";
        $messageType = "error";

    } else {

        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($categoryId <= 0) {

            $message = "ID kategori tidak valid!";
            $messageType = "error";

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "SELECT name FROM categories WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $categoryId);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $category = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (!$category) {

                $message = "Kategori tidak ditemukan!";
                $messageType = "error";

            } else {

                // Periksa buku yang menggunakan kategori

                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT COUNT(*) AS total
                     FROM books
                     WHERE category = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "s",
                    $category['name']
                );

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);
                $bookCount = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if ((int) $bookCount['total'] > 0) {

                    $message = "Kategori tidak dapat dihapus karena masih digunakan oleh " .
                        $bookCount['total'] . " buku.";

                    $messageType = "error";

                } else {

                    $stmt = mysqli_prepare(
                        $conn,
                        "DELETE FROM categories WHERE id = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $categoryId);

                    if (mysqli_stmt_execute($stmt)) {

                        $message = "Kategori berhasil dihapus!";
                        $messageType = "success";

                    } else {

                        $message = "Gagal menghapus kategori.";
                        $messageType = "error";
                    }

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// =========================================================
// GET EDIT CATEGORY
// =========================================================

$editCategory = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM categories WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $editId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $editCategory = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
}

// =========================================================
// GET CATEGORY LIST
// =========================================================

$categoriesQuery = mysqli_query(
    $conn,
    "SELECT
        c.id,
        c.name,
        c.created_at,
        COUNT(b.id) AS total_books
     FROM categories c
     LEFT JOIN books b ON b.category = c.name
     GROUP BY c.id, c.name, c.created_at
     ORDER BY c.name ASC"
);

if (!$categoriesQuery) {
    die("Gagal mengambil data kategori: " . mysqli_error($conn));
}

$totalCategories = mysqli_num_rows($categoriesQuery);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Manage Categories - BookVerse</title>

    <!-- GOOGLE FONT -->

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

    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <!-- CATEGORY CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/categories.css">

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
                    class="admin-nav-link">

                    <i class="fa-solid fa-book"></i>

                    <span>Manage Books</span>

                </a>

                <a
                    href="categories.php"
                    class="admin-nav-link active">

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

                    <i class="fa-solid  fa-money-bill-transfer"></i>
                    <span>Payment Transactions</span>

                </a>

                <a
                    href="return-management.php"
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

                <a
                    href="messages.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-envelope"></i>

                    <span>Messages</span>

                </a>

                <a
                    href="stock-history.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-clock-rotate-left"></i>

                    <span>Stock History</span>

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

        <!-- MAIN CONTENT -->

        <main class="admin-main">

            <div class="admin-page-header">

                <div>

                    <span class="admin-page-label">
                        STORE MANAGEMENT
                    </span>

                    <h1>
                        Manage Categories
                    </h1>

                    <p>
                        Organize and manage your BookVerse book categories.
                    </p>

                </div>

            </div>

            <!-- NOTIFICATION -->

            <?php if ($message !== ''): ?>

                <div class="category-notification <?php echo $messageType; ?>">

                    <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>

            <!-- CATEGORY SUMMARY -->

            <div class="category-summary">

                <div class="category-summary-icon">

                    <i class="fa-solid fa-tags"></i>

                </div>

                <div>

                    <span>Total Categories</span>

                    <strong>
                        <?php echo $totalCategories; ?>
                    </strong>

                </div>

            </div>

            <!-- CATEGORY MANAGEMENT -->

            <div class="category-management-grid">

                <!-- CATEGORY FORM -->

                <section class="category-form-card">

                    <h2>

                        <i class="fa-solid fa-<?php echo $editCategory ? 'pen-to-square' : 'plus-circle'; ?>"></i>

                        <?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?>

                    </h2>

                    <p class="category-description">

                        <?php echo $editCategory
                            ? 'Update the category name.'
                            : 'Create a new category for your book collection.'; ?>

                    </p>

                    <form
                        action="categories.php<?php echo $editCategory ? '?edit=' . (int) $editCategory['id'] : ''; ?>"
                        method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <?php if ($editCategory): ?>

                            <input
                                type="hidden"
                                name="category_id"
                                value="<?php echo (int) $editCategory['id']; ?>">

                        <?php endif; ?>

                        <div class="category-form-group">

                            <label for="category_name">
                                Category Name
                            </label>

                            <input
                                type="text"
                                id="category_name"
                                name="category_name"
                                placeholder="Example: Fantasy, Romance, Business"
                                value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>"
                                maxlength="100"
                                required>

                        </div>

                        <button
                            type="submit"
                            name="<?php echo $editCategory ? 'update_category' : 'add_category'; ?>"
                            class="category-submit-button">

                            <i class="fa-solid fa-<?php echo $editCategory ? 'floppy-disk' : 'plus'; ?>"></i>

                            <?php echo $editCategory ? 'Save Changes' : 'Add Category'; ?>

                        </button>

                        <?php if ($editCategory): ?>

                            <a
                                href="categories.php"
                                class="category-cancel-button">

                                Cancel Edit

                            </a>

                        <?php endif; ?>

                    </form>

                </section>

                <!-- CATEGORY LIST -->

                <section class="category-list-card">

                    <h2>
                        <i class="fa-solid fa-list"></i>
                        Category List
                    </h2>

                    <p class="category-description">
                        All categories registered in BookVerse.
                    </p>

                    <div class="category-table-wrapper">

                        <table class="category-table">

                            <thead>

                                <tr>

                                    <th>Category</th>

                                    <th>Books</th>

                                    <th>Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php if ($totalCategories > 0): ?>

                                    <?php while ($category = mysqli_fetch_assoc($categoriesQuery)): ?>

                                        <tr>

                                            <td>

                                                <span class="category-name">

                                                    <i class="fa-solid fa-folder"></i>

                                                    <?php echo htmlspecialchars($category['name']); ?>

                                                </span>

                                            </td>

                                            <td>

                                                <span class="category-book-count">

                                                    <?php echo (int) $category['total_books']; ?>

                                                    Books

                                                </span>

                                            </td>

                                            <td>

                                                <div class="category-actions">

                                                    <a
                                                        href="categories.php?edit=<?php echo (int) $category['id']; ?>"
                                                        class="category-action-button edit"
                                                        title="Edit Category">

                                                        <i class="fa-solid fa-pen"></i>

                                                    </a>

                                                    <form
                                                        action="categories.php"
                                                        method="POST"
                                                        onsubmit="return confirm('Yakin ingin menghapus kategori ini?');">

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?php echo htmlspecialchars($csrfToken); ?>">

                                                        <input
                                                            type="hidden"
                                                            name="category_id"
                                                            value="<?php echo (int) $category['id']; ?>">

                                                        <button
                                                            type="submit"
                                                            name="delete_category"
                                                            class="category-action-button delete"
                                                            title="Delete Category">

                                                            <i class="fa-solid fa-trash"></i>

                                                        </button>

                                                    </form>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>

                                        <td colspan="3" class="category-empty">

                                            <i class="fa-solid fa-folder-open"></i>

                                            No categories found.

                                            Add your first category!

                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </section>

            </div>

        </main>

    </div>

</body>

</html>