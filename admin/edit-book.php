<?php

session_start();

include "../config/database.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    echo "<script>
        alert('Silakan login terlebih dahulu!');
        window.location='../login.php';
    </script>";

    exit();

}

if ($_SESSION['role'] !== 'admin') {

    echo "<script>
        alert('Akses hanya untuk admin!');
        window.location='../index.php';
    </script>";

    exit();

}

/*
|--------------------------------------------------------------------------
| GET BOOK ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    echo "<script>
        alert('ID buku tidak valid!');
        window.location='books.php';
    </script>";

    exit();

}

$bookId = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| GET BOOK DATA
|--------------------------------------------------------------------------
*/

$bookQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM books
     WHERE id = $bookId
     LIMIT 1"
);

if (mysqli_num_rows($bookQuery) === 0) {

    echo "<script>
        alert('Buku tidak ditemukan!');
        window.location='books.php';
    </script>";

    exit();

}

$book = mysqli_fetch_assoc($bookQuery);

/*
|--------------------------------------------------------------------------
| UPDATE BOOK
|--------------------------------------------------------------------------
*/

if (isset($_POST['update_book'])) {

    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $price = trim($_POST['price']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $title === '' ||
        $author === '' ||
        $price === '' ||
        $category === '' ||
        $description === ''
    ) {

        echo "<script>
            alert('Semua field wajib diisi!');
        </script>";

    } else {

        /*
        |--------------------------------------------------------------------------
        | IMAGE HANDLING
        |--------------------------------------------------------------------------
        */

        $imagePath = $book['image'];

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $image = $_FILES['image'];

            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            $fileName = $image['name'];
            $fileTmp = $image['tmp_name'];
            $fileSize = $image['size'];

            $fileExtension = strtolower(
                pathinfo($fileName, PATHINFO_EXTENSION)
            );

            /*
            |--------------------------------------------------------------------------
            | VALIDATE IMAGE
            |--------------------------------------------------------------------------
            */

            if (!in_array($fileExtension, $allowedExtensions)) {

                echo "<script>
                    alert('Format gambar tidak didukung!');
                </script>";

                return;

            }

            if ($fileSize > 2 * 1024 * 1024) {

                echo "<script>
                    alert('Ukuran gambar maksimal 2 MB!');
                </script>";

                return;

            }

            /*
            |--------------------------------------------------------------------------
            | UPLOAD NEW IMAGE
            |--------------------------------------------------------------------------
            */

            $uploadDirectory = "../assets/img/books/";

            $newImageName = uniqid(
                'book_',
                true
            ) . "." . $fileExtension;

            $uploadPath = $uploadDirectory . $newImageName;

            if (!move_uploaded_file($fileTmp, $uploadPath)) {

                echo "<script>
                    alert('Gagal mengupload gambar!');
                </script>";

                return;

            }

            $imagePath = "assets/img/books/" . $newImageName;

            /*
            |--------------------------------------------------------------------------
            | DELETE OLD IMAGE
            |--------------------------------------------------------------------------
            */

            if (
                !empty($book['image']) &&
                file_exists("../" . $book['image'])
            ) {

                unlink("../" . $book['image']);

            }

        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATABASE
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE books
             SET
                title = ?,
                author = ?,
                price = ?,
                category = ?,
                description = ?,
                image = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssdsssi",
            $title,
            $author,
            $price,
            $category,
            $description,
            $imagePath,
            $bookId
        );

        if (mysqli_stmt_execute($stmt)) {

            echo "<script>
                alert('Buku berhasil diperbarui!');
                window.location='books.php';
            </script>";

            exit();

        } else {

            echo "<script>
                alert('Gagal memperbarui buku!');
            </script>";

        }

        mysqli_stmt_close($stmt);

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

    <title>Edit Book - BookVerse Admin</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body>

    <!-- ================= ADMIN HEADER ================= -->

    <header class="admin-header">

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

        <div class="admin-header-right">

            <span class="admin-welcome">

                <i class="fa-solid fa-user-shield"></i>

                Welcome,
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


    <!-- ================= ADMIN LAYOUT ================= -->

    <div class="admin-layout">


        <!-- ================= SIDEBAR ================= -->

        <aside class="admin-sidebar">

            <nav class="admin-nav">

                <a
                    href="index.php"
                    class="admin-nav-link">

                    <i class="fa-solid fa-chart-line"></i>

                    Dashboard

                </a>

                <a
                    href="books.php"
                    class="admin-nav-link active">

                    <i class="fa-solid fa-book"></i>

                    Manage Books

                </a>

                <a
                    href="#"
                    class="admin-nav-link">

                    <i class="fa-solid fa-box"></i>

                    Manage Orders

                </a>

                <a
                    href="#"
                    class="admin-nav-link">

                    <i class="fa-solid fa-users"></i>

                    Manage Users

                </a>

            </nav>

            <div class="admin-sidebar-bottom">

                <a
                    href="../index.php"
                    class="admin-back-home">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Store

                </a>

            </div>

        </aside>


        <!-- ================= MAIN CONTENT ================= -->

        <main class="admin-main">

            <div class="admin-page-header">

                <div>

                    <span class="admin-page-label">
                        BOOK MANAGEMENT
                    </span>

                    <h1>
                        Edit Book
                    </h1>

                    <p>
                        Update your book information.
                    </p>

                </div>

                <a
                    href="books.php"
                    class="admin-back-button">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Books

                </a>

            </div>


            <!-- ================= EDIT FORM ================= -->

            <section class="admin-form-section">

                <form
                    action=""
                    method="POST"
                    enctype="multipart/form-data"
                    class="admin-book-form">


                    <!-- BOOK COVER -->

                    <div class="admin-form-group">

                        <label>
                            Book Cover
                        </label>

                        <div class="admin-upload-box">

                            <i class="fa-solid fa-cloud-arrow-up"></i>

                            <strong>
                                Change book cover
                            </strong>

                            <span>
                                JPG, JPEG, PNG or WEBP — Max 2 MB
                            </span>

                            <input
                                type="file"
                                name="image"
                                accept=".jpg,.jpeg,.png,.webp">

                        </div>

                    </div>


                    <!-- TITLE -->

                    <div class="admin-form-group">

                        <label for="title">
                            Book Title
                        </label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="<?php echo htmlspecialchars($book['title']); ?>"
                            placeholder="Enter book title"
                            required>

                    </div>


                    <!-- AUTHOR -->

                    <div class="admin-form-group">

                        <label for="author">
                            Author
                        </label>

                        <input
                            type="text"
                            id="author"
                            name="author"
                            value="<?php echo htmlspecialchars($book['author']); ?>"
                            placeholder="Enter author name"
                            required>

                    </div>


                    <!-- PRICE -->

                    <div class="admin-form-group">

                        <label for="price">
                            Price
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            value="<?php echo htmlspecialchars($book['price']); ?>"
                            placeholder="Enter book price"
                            min="0"
                            required>

                    </div>


                    <!-- CATEGORY -->

                    <div class="admin-form-group">

                        <label for="category">
                            Category
                        </label>

                        <input
                            type="text"
                            id="category"
                            name="category"
                            value="<?php echo htmlspecialchars($book['category']); ?>"
                            placeholder="Example: Fiction, Fantasy, Romance"
                            required>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="admin-form-group">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            placeholder="Enter book description"
                            required><?php echo htmlspecialchars($book['description']); ?></textarea>

                    </div>


                    <!-- FORM ACTIONS -->

                    <div class="admin-form-actions">

                        <a
                            href="books.php"
                            class="admin-form-cancel">

                            Cancel

                        </a>

                        <button
                            type="submit"
                            name="update_book"
                            class="admin-form-submit">

                            <i class="fa-solid fa-floppy-disk"></i>

                            Save Changes

                        </button>

                    </div>

                </form>

            </section>

        </main>

    </div>

</body>

</html>