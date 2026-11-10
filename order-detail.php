<?php

require_once __DIR__ . '/config/user-session.php';

include "config/database.php";



/*

|--------------------------------------------------------------------------

| Pastikan User Sudah Login

|--------------------------------------------------------------------------

*/



if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");

    exit();

}



/*

|--------------------------------------------------------------------------

| Validasi Order ID

|--------------------------------------------------------------------------

*/



if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: orders.php");

    exit();

}



$orderId = (int) $_GET['id'];

$userId = (int) $_SESSION['user_id'];



/*

|--------------------------------------------------------------------------

| Ambil Data Order

|--------------------------------------------------------------------------

*/



$orderStmt = mysqli_prepare(

    $conn,

    "SELECT

        id,

        user_id,

        recipient_name,

        phone,

        address,

        payment_method,

        order_number,

        total_price,

        status,

        created_at

     FROM orders

     WHERE id = ?

     AND user_id = ?

     LIMIT 1"

);



mysqli_stmt_bind_param(

    $orderStmt,

    "ii",

    $orderId,

    $userId

);



mysqli_stmt_execute($orderStmt);



$orderResult = mysqli_stmt_get_result($orderStmt);



$order = mysqli_fetch_assoc($orderResult);



mysqli_stmt_close($orderStmt);



/*

|--------------------------------------------------------------------------

| Jika Order Tidak Ditemukan

|--------------------------------------------------------------------------

*/



if (!$order) {

    header("Location: orders.php");

    exit();

}



/*

|--------------------------------------------------------------------------

| CSRF TOKEN

|--------------------------------------------------------------------------

*/



if (empty($_SESSION['confirm_order_token'])) {

    $_SESSION['confirm_order_token'] = bin2hex(random_bytes(32));

}



/*

|--------------------------------------------------------------------------

| KONFIRMASI PESANAN DITERIMA

|--------------------------------------------------------------------------

*/



if (

    $_SERVER['REQUEST_METHOD'] === 'POST' &&

    isset($_POST['confirm_received'])

) {



    $csrf_token = $_POST['csrf_token'] ?? '';



    if (

        !hash_equals(

            $_SESSION['confirm_order_token'],

            $csrf_token

        )

    ) {

        die("Permintaan tidak valid. Silakan refresh halaman.");

    }



    mysqli_begin_transaction($conn);



    try {



        // Kunci data order



        $checkOrder = mysqli_prepare(

            $conn,

            "SELECT status

             FROM orders

             WHERE id = ?

             AND user_id = ?

             FOR UPDATE"

        );



        mysqli_stmt_bind_param(

            $checkOrder,

            "ii",

            $orderId,

            $userId

        );



        mysqli_stmt_execute($checkOrder);



        $orderCheck = mysqli_fetch_assoc(

            mysqli_stmt_get_result($checkOrder)

        );



        mysqli_stmt_close($checkOrder);



        if (

            !$orderCheck ||

            $orderCheck['status'] !== 'Shipped'

        ) {

            throw new Exception(

                "Pesanan belum dikirim atau sudah dikonfirmasi."

            );

        }



        // Kunci data pengiriman



        $checkShipping = mysqli_prepare(

            $conn,

            "SELECT id, shipping_status

             FROM order_shipping

             WHERE order_id = ?

             FOR UPDATE"

        );



        mysqli_stmt_bind_param(

            $checkShipping,

            "i",

            $orderId

        );



        mysqli_stmt_execute($checkShipping);



        $shippingCheck = mysqli_fetch_assoc(

            mysqli_stmt_get_result($checkShipping)

        );



        mysqli_stmt_close($checkShipping);



        if (

            !$shippingCheck ||

            $shippingCheck['shipping_status'] !== 'Shipped'

        ) {

            throw new Exception(

                "Pengiriman belum berstatus Shipped."

            );

        }



        // Update status pengiriman



        $updateShipping = mysqli_prepare(

            $conn,

            "UPDATE order_shipping

             SET shipping_status = 'Delivered',

                 received_at = NOW(),

                 received_by = ?,

                 updated_at = NOW()

             WHERE id = ?

             AND shipping_status = 'Shipped'"

        );



        mysqli_stmt_bind_param(

            $updateShipping,

            "ii",

            $userId,

            $shippingCheck['id']

        );



        if (!mysqli_stmt_execute($updateShipping)) {

            throw new Exception(

                "Gagal memperbarui pengiriman."

            );

        }



        if (mysqli_stmt_affected_rows($updateShipping) !== 1) {

            throw new Exception(

                "Konfirmasi pesanan gagal."

            );

        }



        mysqli_stmt_close($updateShipping);



        // Update status order



        $updateOrder = mysqli_prepare(

            $conn,

            "UPDATE orders

             SET status = 'Completed'

             WHERE id = ?

             AND user_id = ?

             AND status = 'Shipped'"

        );



        mysqli_stmt_bind_param(

            $updateOrder,

            "ii",

            $orderId,

            $userId

        );



        if (!mysqli_stmt_execute($updateOrder)) {

            throw new Exception(

                "Gagal memperbarui status pesanan."

            );

        }



        if (mysqli_stmt_affected_rows($updateOrder) !== 1) {

            throw new Exception(

                "Status pesanan gagal diperbarui."

            );

        }



        mysqli_stmt_close($updateOrder);



        mysqli_commit($conn);



        unset($_SESSION['confirm_order_token']);



        header(

            "Location: order-detail.php?id=" .

            $orderId .

            "&received=success"

        );



        exit();



    } catch (Throwable $e) {



        mysqli_rollback($conn);



        die(

            htmlspecialchars($e->getMessage()) .

            "<br><a href='order-detail.php?id=" .

            $orderId .

            "'>Kembali</a>"

        );

    }

}



/*
|--------------------------------------------------------------------------
| AJUKAN RETURN & REFUND
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_return'])
) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['confirm_order_token']) ||
        !hash_equals($_SESSION['confirm_order_token'], $csrfToken)
    ) {
        die("Permintaan tidak valid. Silakan refresh halaman.");
    }

    $reason = trim($_POST['return_reason'] ?? '');
    $description = trim($_POST['return_description'] ?? '');

    $allowedReasons = [
        'Buku rusak',
        'Buku sobek',
        'Buku tidak sesuai pesanan',
        'Kesalahan pengiriman',
        'Lainnya'
    ];

    if (!in_array($reason, $allowedReasons, true)) {
        die("Alasan return tidak valid.");
    }

    if (mb_strlen($description) < 10 || mb_strlen($description) > 2000) {
        die("Deskripsi return harus 10–2000 karakter.");
    }

    if (
        !isset($_FILES['return_images']) ||
        !is_array($_FILES['return_images']['name'])
    ) {
        die("Foto bukti wajib diupload.");
    }

    $files = $_FILES['return_images'];
    $fileCount = count($files['name']);

    if ($fileCount < 1 || $fileCount > 5) {
        die("Upload minimal 1 dan maksimal 5 foto bukti.");
    }

    // Periksa kelayakan pesanan dan batas 3 x 24 jam.
    $eligibilityStmt = mysqli_prepare(
        $conn,
        "SELECT o.status, s.shipping_status, s.received_at
         FROM orders o
         INNER JOIN order_shipping s ON s.order_id = o.id
         WHERE o.id = ? AND o.user_id = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($eligibilityStmt, "ii", $orderId, $userId);
    mysqli_stmt_execute($eligibilityStmt);
    $eligibility = mysqli_fetch_assoc(mysqli_stmt_get_result($eligibilityStmt));
    mysqli_stmt_close($eligibilityStmt);

    if (
        !$eligibility ||
        $eligibility['status'] !== 'Completed' ||
        $eligibility['shipping_status'] !== 'Delivered' ||
        empty($eligibility['received_at'])
    ) {
        die("Return hanya dapat diajukan setelah pesanan diterima.");
    }

    $receivedTimestamp = strtotime($eligibility['received_at']);

    if (time() > ($receivedTimestamp + 259200)) {
        die("Batas pengajuan return sudah lewat 3 x 24 jam.");
    }

    // Validasi seluruh file sebelum menyimpan.
    $uploadDir = __DIR__ . '/assets/uploads/returns/';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        die("Folder upload bukti return gagal dibuat.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $validatedFiles = [];

    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            die("Ada foto yang gagal diupload.");
        }

        if ($files['size'][$i] > 5 * 1024 * 1024) {
            die("Ukuran setiap foto maksimal 5 MB.");
        }

        $mime = $finfo->file($files['tmp_name'][$i]);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png'
        ];

        if (
            !isset($extensions[$mime]) ||
            @getimagesize($files['tmp_name'][$i]) === false
        ) {
            die("Bukti harus berupa gambar JPG atau PNG.");
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];

        $validatedFiles[] = [
            'temporary' => $files['tmp_name'][$i],
            'path' => 'assets/uploads/returns/' . $filename,
            'absolute' => $uploadDir . $filename
        ];
    }

    $savedFiles = [];
    $transactionStarted = false;

    try {
        mysqli_begin_transaction($conn);
        $transactionStarted = true;

        // Kunci pesanan agar pengajuan ganda tidak terjadi bersamaan.
        $lockOrder = mysqli_prepare(
            $conn,
            "SELECT o.status, s.shipping_status, s.received_at
             FROM orders o
             INNER JOIN order_shipping s ON s.order_id = o.id
             WHERE o.id = ? AND o.user_id = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param($lockOrder, "ii", $orderId, $userId);
        mysqli_stmt_execute($lockOrder);
        $lockedOrder = mysqli_fetch_assoc(mysqli_stmt_get_result($lockOrder));
        mysqli_stmt_close($lockOrder);

        if (
            !$lockedOrder ||
            $lockedOrder['status'] !== 'Completed' ||
            $lockedOrder['shipping_status'] !== 'Delivered' ||
            empty($lockedOrder['received_at']) ||
            time() > (strtotime($lockedOrder['received_at']) + 259200)
        ) {
            throw new Exception("Pesanan tidak lagi memenuhi syarat return.");
        }

        $duplicateStmt = mysqli_prepare(
            $conn,
            "SELECT id FROM return_requests WHERE order_id = ? LIMIT 1 FOR UPDATE"
        );

        mysqli_stmt_bind_param($duplicateStmt, "i", $orderId);
        mysqli_stmt_execute($duplicateStmt);
        $duplicateReturn = mysqli_fetch_assoc(mysqli_stmt_get_result($duplicateStmt));
        mysqli_stmt_close($duplicateStmt);

        if ($duplicateReturn) {
            throw new Exception("Pengajuan return untuk pesanan ini sudah pernah dibuat.");
        }

        // Simpan foto bukti ke folder upload.
        foreach ($validatedFiles as $file) {
            if (!move_uploaded_file($file['temporary'], $file['absolute'])) {
                throw new Exception("Gagal menyimpan foto bukti.");
            }
            $savedFiles[] = $file['absolute'];
        }

        // Simpan permintaan return.
        $insertReturn = mysqli_prepare(
            $conn,
            "INSERT INTO return_requests
             (order_id, user_id, return_reason, description, return_status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'Requested', NOW(), NOW())"
        );

        mysqli_stmt_bind_param($insertReturn, "iiss", $orderId, $userId, $reason, $description);

        if (!mysqli_stmt_execute($insertReturn)) {
            throw new Exception("Gagal membuat pengajuan return.");
        }

        $returnId = mysqli_insert_id($conn);
        mysqli_stmt_close($insertReturn);

        // Catat item yang termasuk dalam pengajuan return.
        $insertItems = mysqli_prepare(
            $conn,
            "INSERT INTO return_items (return_id, book_id, quantity, condition_notes)
             SELECT ?, book_id, quantity, ?
             FROM order_items
             WHERE order_id = ?"
        );

        mysqli_stmt_bind_param($insertItems, "isi", $returnId, $description, $orderId);

        if (!mysqli_stmt_execute($insertItems) || mysqli_stmt_affected_rows($insertItems) < 1) {
            throw new Exception("Gagal menyimpan item return.");
        }

        mysqli_stmt_close($insertItems);

        // Simpan lokasi foto bukti di database.
        $insertImage = mysqli_prepare(
            $conn,
            "INSERT INTO return_images (return_id, image_path, created_at)
             VALUES (?, ?, NOW())"
        );

        foreach ($validatedFiles as $file) {
            mysqli_stmt_bind_param($insertImage, "is", $returnId, $file['path']);

            if (!mysqli_stmt_execute($insertImage)) {
                throw new Exception("Gagal menyimpan data foto bukti.");
            }
        }

        mysqli_stmt_close($insertImage);
        mysqli_commit($conn);

        header("Location: order-detail.php?id=" . $orderId . "&return=success");
        exit();

    } catch (Throwable $e) {
        if ($transactionStarted) {
            mysqli_rollback($conn);
        }

        foreach ($savedFiles as $savedFile) {
            if (is_file($savedFile)) {
                unlink($savedFile);
            }
        }

        die(
            htmlspecialchars($e->getMessage()) .
            "<br><a href='order-detail.php?id=" . $orderId . "'>Kembali</a>"
        );
    }
}


/*
|--------------------------------------------------------------------------
| KIRIM BARANG RETURN
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_return_shipping'])
) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['confirm_order_token']) ||
        !hash_equals($_SESSION['confirm_order_token'], $csrfToken)
    ) {
        die("Permintaan tidak valid. Silakan refresh halaman.");
    }

    $returnCourier = trim($_POST['return_courier'] ?? '');
    $returnTracking = strtoupper(trim($_POST['return_tracking_number'] ?? ''));

    $allowedReturnCouriers = [
        'JNE',
        'J&T Express',
        'SiCepat',
        'AnterAja',
        'POS Indonesia',
        'Ninja Xpress',
        'Other'
    ];

    if (!in_array($returnCourier, $allowedReturnCouriers, true)) {
        die("Kurir retur tidak valid.");
    }

    if (
        strlen($returnTracking) < 5 ||
        strlen($returnTracking) > 50 ||
        !preg_match('/^[A-Z0-9-]+$/', $returnTracking)
    ) {
        die("Nomor resi harus 5–50 karakter dan hanya berisi huruf, angka, atau tanda hubung.");
    }

    mysqli_begin_transaction($conn);

    try {
        // Kunci pengajuan return dan pastikan pengajuan milik User ini.
        $lockReturn = mysqli_prepare(
            $conn,
            "SELECT rr.id, rr.return_status
             FROM return_requests rr
             INNER JOIN orders o ON o.id = rr.order_id
             WHERE rr.order_id = ?
               AND rr.user_id = ?
               AND o.user_id = ?
             LIMIT 1
             FOR UPDATE"
        );

        mysqli_stmt_bind_param(
            $lockReturn,
            "iii",
            $orderId,
            $userId,
            $userId
        );

        mysqli_stmt_execute($lockReturn);
        $lockedReturn = mysqli_fetch_assoc(mysqli_stmt_get_result($lockReturn));
        mysqli_stmt_close($lockReturn);

        if (!$lockedReturn || $lockedReturn['return_status'] !== 'Approved') {
            throw new Exception("Data return belum disetujui Admin atau sudah diproses.");
        }

        $updateReturnShipping = mysqli_prepare(
            $conn,
            "UPDATE return_requests
             SET return_courier = ?,
                 return_tracking_number = ?,
                 return_status = 'Return Shipped',
                 updated_at = NOW()
             WHERE id = ?
               AND return_status = 'Approved'"
        );

        mysqli_stmt_bind_param(
            $updateReturnShipping,
            "ssi",
            $returnCourier,
            $returnTracking,
            $lockedReturn['id']
        );

        if (
            !mysqli_stmt_execute($updateReturnShipping) ||
            mysqli_stmt_affected_rows($updateReturnShipping) !== 1
        ) {
            throw new Exception("Gagal menyimpan informasi pengiriman retur.");
        }

        mysqli_stmt_close($updateReturnShipping);
        mysqli_commit($conn);

        header("Location: order-detail.php?id=" . $orderId . "&return_shipping=success");
        exit();

    } catch (Throwable $e) {
        mysqli_rollback($conn);

        die(
            htmlspecialchars($e->getMessage()) .
            "<br><a href='order-detail.php?id=" . $orderId . "'>Kembali</a>"
        );
    }
}

/*
|--------------------------------------------------------------------------
| CEK PENGAJUAN RETURN YANG SUDAH ADA
|--------------------------------------------------------------------------
*/

$returnStmt = mysqli_prepare(
    $conn,
    "SELECT id, return_reason, description, return_status, rejection_reason,
            return_courier, return_tracking_number, created_at, updated_at
     FROM return_requests
     WHERE order_id = ? AND user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($returnStmt, "ii", $orderId, $userId);
mysqli_stmt_execute($returnStmt);
$returnRequest = mysqli_fetch_assoc(mysqli_stmt_get_result($returnStmt));
mysqli_stmt_close($returnStmt);

$returnImages = [];

if ($returnRequest) {
    $returnImageStmt = mysqli_prepare(
        $conn,
        "SELECT image_path FROM return_images WHERE return_id = ? ORDER BY id ASC"
    );

    mysqli_stmt_bind_param($returnImageStmt, "i", $returnRequest['id']);
    mysqli_stmt_execute($returnImageStmt);
    $returnImageResult = mysqli_stmt_get_result($returnImageStmt);

    while ($returnImage = mysqli_fetch_assoc($returnImageResult)) {
        $returnImages[] = $returnImage['image_path'];
    }

    mysqli_stmt_close($returnImageStmt);
}

/*

|--------------------------------------------------------------------------

| Ambil Detail Item Order

|--------------------------------------------------------------------------

*/



$itemStmt = mysqli_prepare(

    $conn,

    "SELECT

        oi.id,

        oi.book_id,

        oi.quantity,

        oi.price,

        b.title,

        b.author,

        b.image

     FROM order_items oi

     INNER JOIN books b

        ON oi.book_id = b.id

     WHERE oi.order_id = ?

     ORDER BY oi.id ASC"

);



mysqli_stmt_bind_param(

    $itemStmt,

    "i",

    $orderId

);



mysqli_stmt_execute($itemStmt);



$itemResult = mysqli_stmt_get_result($itemStmt);



$orderItems = [];



while ($item = mysqli_fetch_assoc($itemResult)) {



    $item['subtotal'] =

        (float) $item['price'] *

        (int) $item['quantity'];



    $orderItems[] = $item;

}



mysqli_stmt_close($itemStmt);



/*

|--------------------------------------------------------------------------

| DATA PENGIRIMAN

|--------------------------------------------------------------------------

*/



$shippingStmt = mysqli_prepare(

    $conn,

    "SELECT

        courier,

        tracking_number,

        shipping_status,

        shipped_at,

        received_at

     FROM order_shipping

     WHERE order_id = ?

     LIMIT 1"

);



mysqli_stmt_bind_param(

    $shippingStmt,

    "i",

    $orderId

);



mysqli_stmt_execute($shippingStmt);



$shippingResult = mysqli_stmt_get_result($shippingStmt);



$shipping = mysqli_fetch_assoc($shippingResult);



mysqli_stmt_close($shippingStmt);



/*

|--------------------------------------------------------------------------

| Status Pesanan

|--------------------------------------------------------------------------

*/



$statusSteps = [

    'Pending',

    'Processing',

    'Shipped',

    'Completed'

];



$currentStatus = $order['status'];



$currentStatusIndex = array_search(

    $currentStatus,

    $statusSteps,

    true

);



if ($currentStatusIndex === false) {

    $currentStatusIndex = 0;

}



/*

|--------------------------------------------------------------------------

| Format Tanggal

|--------------------------------------------------------------------------

*/



$orderDate = date(

    'd M Y, H:i',

    strtotime($order['created_at'])

);



/*

|--------------------------------------------------------------------------

| Data User

|--------------------------------------------------------------------------

*/



$fullname = $_SESSION['fullname'] ?? '';

$email = $_SESSION['email'] ?? '';



/*

|--------------------------------------------------------------------------

| Format Payment Method

|--------------------------------------------------------------------------

*/



$paymentLabels = [

    'bank_transfer' => 'Bank Transfer',

    'e_wallet' => 'E-Wallet'

];



$paymentMethod = $paymentLabels[

    $order['payment_method'] ?? ''

] ?? 'Unknown';



?>



<!DOCTYPE html>

<html lang="en">



<head>



    <meta charset="UTF-8">



    <meta

        name="viewport"

        content="width=device-width, initial-scale=1.0">



    <title>

        Order Detail | BookVerse

    </title>



    <!-- Google Font -->



    <link

        rel="preconnect"

        href="https\://fonts.googleapis.com">



    <link

        rel="preconnect"

        href="https\://fonts.gstatic.com"

        crossorigin>



    <link

        href="https\://fonts.googleapis.com/css2?family=Poppins:wght\@300;400;500;600;700;800&display=swap"

        rel="stylesheet">



    <!-- Font Awesome -->



    <link

        rel="stylesheet"

        href="https\://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">



    <!-- Main CSS -->



    <link

        rel="stylesheet"

        href="assets/css/style.css">

    <link rel="stylesheet" href="assets/css/order-detail.css">



    



</head>



<body>



    <!-- ================= HEADER ================= -->



    <header class="header">



        <div class="container">



            <div class="profile-navbar">



                <!-- LOGO -->



                <a

                    href="index.php"

                    class="profile-logo">



                    <img

                        src="assets/img/hugeicons_book-open-02.png"

                        alt="BookVerse">



                    <div>



                        <h2>

                            BookVerse

                        </h2>



                        <span>

                            Every Book Has a Story

                        </span>



                    </div>



                </a>



                <!-- BACK TO ORDERS -->



                <a

                    href="orders.php"

                    class="profile-back">



                    <i class="fa-solid fa-arrow-left"></i>



                    Back to Orders



                </a>



            </div>



        </div>



    </header>





    <!-- ================= ORDER DETAIL ================= -->



    <main class="order-detail-page">



        <div class="container">



            <div class="order-detail-container">



                <!-- ================= PAGE HEADER ================= -->



                <div class="order-detail-top">



                    <div class="order-detail-heading">



                        <span>

                            ORDER DETAILS

                        </span>



                        <h1>

                            Order Details

                        </h1>



                        <p>

                            Review your BookVerse order information.

                        </p>



                    </div>



                    <a

                        href="orders.php"

                        class="order-detail-back">



                        <i class="fa-solid fa-arrow-left"></i>



                        Kembali ke Pesanan



                    </a>



                </div>





                <!-- ================= STATUS ================= -->



                <section class="order-status-card">



                    <div class="order-status-header">



                        <div class="order-number-box">



                            <span>

                                ORDER NUMBER

                            </span>



                            <strong>

                                #<?php

                                echo htmlspecialchars(

                                    $order['order_number']

                                );

                                ?>

                            </strong>



                        </div>



                        <span class="order-status-badge">



                            <i class="fa-solid fa-circle"></i>



                            <?php

                            echo htmlspecialchars(

                                $order['status']

                            );

                            ?>



                        </span>



                    </div>





                    <!-- STATUS TIMELINE -->



                    <div class="order-timeline">



                        <?php foreach (

                            $statusSteps as $index => $status

                        ): ?>



                            <?php

                            $isActive =

                                $index <= $currentStatusIndex;



                            $isCurrent =

                                $index === $currentStatusIndex;

                            ?>



                            <div

                                class="timeline-item

                                <?php echo $isActive ? 'active' : ''; ?>

                                <?php echo $isCurrent ? 'current' : ''; ?>">



                                <div class="timeline-icon">



                                    <?php if ($isActive): ?>



                                        <i class="fa-solid fa-check"></i>



                                    <?php else: ?>



                                        <i class="fa-solid fa-circle"></i>



                                    <?php endif; ?>



                                </div>



                                <strong>

                                    <?php echo $status; ?>

                                </strong>



                            </div>



                        <?php endforeach; ?>



                    </div>



                </section>





                <!-- ================= MAIN CONTENT ================= -->



                <div class="order-detail-grid">



                    <!-- ================= LEFT ================= -->



                    <div class="order-detail-main">



                        <!-- ORDER ITEMS -->



                        <section class="order-detail-card">



                            <div class="order-detail-card-header">



                                <h2>

                                    Order Items

                                </h2>



                                <span>

                                    <?php

                                    echo count($orderItems);

                                    ?>

                                    item(s)

                                </span>



                            </div>



                            <div class="order-detail-items">



                                <?php foreach ($orderItems as $item): ?>



                                    <div class="order-detail-item">



                                        <div class="order-detail-item-image">



                                            <img

                                                src="<?php

                                                echo htmlspecialchars(

                                                    $item['image']

                                                );

                                                ?>"

                                                alt="<?php

                                                echo htmlspecialchars(

                                                    $item['title']

                                                );

                                                ?>">



                                        </div>



                                        <div class="order-detail-item-info">



                                            <h3>

                                                <?php

                                                echo htmlspecialchars(

                                                    $item['title']

                                                );

                                                ?>

                                            </h3>



                                            <p>

                                                <?php

                                                echo htmlspecialchars(

                                                    $item['author']

                                                );

                                                ?>

                                            </p>



                                            <span>

                                                <?php

                                                echo $item['quantity'];

                                                ?>

                                                ×

                                                Rp<?php

                                                echo number_format(

                                                    $item['price'],

                                                    0,

                                                    ',',

                                                    '.'

                                                );

                                                ?>

                                            </span>



                                        </div>



                                        <div class="order-detail-item-total">



                                            Rp<?php

                                            echo number_format(

                                                $item['subtotal'],

                                                0,

                                                ',',

                                                '.'

                                            );

                                            ?>



                                        </div>



                                    </div>



                                <?php endforeach; ?>



                            </div>



                        </section>





                        <!-- CUSTOMER INFORMATION -->



                        <section class="order-detail-card">



                            <div class="order-detail-card-header">



                                <h2>

                                    Shipping Information

                                </h2>



                                <span>

                                    Delivery Details

                                </span>



                            </div>



                            <div class="customer-info">



                                <div class="customer-info-box">



                                    <span>

                                        RECIPIENT NAME

                                    </span>



                                    <strong>

                                        <?php

                                        echo htmlspecialchars(

                                            $order['recipient_name']

                                        );

                                        ?>

                                    </strong>



                                </div>



                                <div class="customer-info-box">



                                    <span>

                                        PHONE NUMBER

                                    </span>



                                    <strong>

                                        <?php

                                        echo htmlspecialchars(

                                            $order['phone']

                                        );

                                        ?>

                                    </strong>



                                </div>



                                <div class="customer-info-box full">



                                    <span>

                                        SHIPPING ADDRESS

                                    </span>



                                    <strong>

                                        <?php

                                        echo nl2br(

                                            htmlspecialchars(

                                                $order['address']

                                            )

                                        );

                                        ?>

                                    </strong>



                                </div>



                            </div>



                        </section>





                        <!-- TRACKING INFORMATION -->



                        <section class="order-detail-card">



                            <div class="order-detail-card-header">



                                <h2>

                                    Tracking Information

                                </h2>



                                <span>

                                    Shipment Status

                                </span>



                            </div>



                            <?php if ($shipping): ?>



                                <div class="customer-info">



                                    <div class="customer-info-box">



                                        <span>

                                            COURIER

                                        </span>



                                        <strong>

                                            <?php

                                            echo htmlspecialchars(

                                                $shipping['courier']

                                            );

                                            ?>

                                        </strong>



                                    </div>



                                    <div class="customer-info-box">



                                        <span>

                                            TRACKING NUMBER

                                        </span>



                                        <strong>

                                            <?php

                                            echo htmlspecialchars(

                                                $shipping['tracking_number']

                                            );

                                            ?>

                                        </strong>



                                    </div>



                                    <div class="customer-info-box">



                                        <span>

                                            SHIPPING STATUS

                                        </span>



                                        <strong>

                                            <?php

                                            echo htmlspecialchars(

                                                $shipping['shipping_status']

                                            );

                                            ?>

                                        </strong>



                                    </div>



                                    <div class="customer-info-box">



                                        <span>

                                            SHIPPED AT

                                        </span>



                                        <strong>

                                            <?php

                                            echo $shipping['shipped_at']

                                                ? date(

                                                    'd M Y, H:i',

                                                    strtotime($shipping['shipped_at'])

                                                )

                                                : '-';

                                            ?>

                                        </strong>



                                    </div>



                                    <?php if ($shipping['received_at']): ?>



                                        <div class="customer-info-box full">



                                            <span>

                                                RECEIVED AT

                                            </span>



                                            <strong>

                                                <?php

                                                echo date(

                                                    'd M Y, H:i',

                                                    strtotime($shipping['received_at'])

                                                );

                                                ?>

                                            </strong>



                                        </div>



                                    <?php endif; ?>



                                </div>



                                <?php if (

                                    $order['status'] === 'Shipped' &&

                                    $shipping['shipping_status'] === 'Shipped'

                                ): ?>



                                    <div class="tracking-confirmation">



                                        <p>

                                            Jika buku sudah sampai dan kamu telah menerimanya,

                                            silakan konfirmasi pesanan di bawah ini.

                                        </p>



                                        <form method="POST">



                                            <input

                                                type="hidden"

                                                name="csrf_token"

                                                value="<?php

                                                echo htmlspecialchars(

                                                    $_SESSION['confirm_order_token']

                                                );

                                                ?>">



                                            <button

                                                type="submit"

                                                name="confirm_received"

                                                value="1"

                                                class="order-summary-btn order-summary-btn-primary"

                                                onclick="return confirm('Apakah kamu yakin pesanan sudah diterima?')">



                                                <i class="fa-solid fa-check-circle"></i>



                                                Pesanan Diterima



                                            </button>



                                        </form>



                                    </div>



                                <?php elseif (

                                    $order['status'] === 'Completed' &&

                                    $shipping['shipping_status'] === 'Delivered'

                                ): ?>



                                    <div class="tracking-delivered">



                                        <i class="fa-solid fa-circle-check"></i>



                                        <strong>

                                            Pesanan sudah diterima!

                                        </strong>



                                        <br>



                                        Terima kasih telah berbelanja di BookVerse.



                                    </div>



                                <?php endif; ?>



                            <?php else: ?>



                                <p>

                                    Informasi pengiriman belum tersedia.

                                </p>



                            <?php endif; ?>



                        </section>


                        <!-- RETURN & REFUND -->

                        <section class="order-detail-card">

                            <div class="order-detail-card-header">
                                <h2>Return &amp; Refund</h2>
                                <span>Return Request</span>
                            </div>

                            <?php if ($returnRequest): ?>

                                <div class="return-status-box">
                                    Status pengajuan: <strong><?php echo htmlspecialchars($returnRequest['return_status']); ?></strong>
                                    <br>
                                    Alasan: <?php echo htmlspecialchars($returnRequest['return_reason']); ?>
                                    <br>
                                    Deskripsi: <?php echo nl2br(htmlspecialchars($returnRequest['description'])); ?>

                                    <?php if (!empty($returnRequest['return_courier']) || !empty($returnRequest['return_tracking_number'])): ?>
                                        <br><br>
                                        Kurir retur: <strong><?php echo htmlspecialchars($returnRequest['return_courier'] ?? '-'); ?></strong>
                                        <br>
                                        Nomor resi retur: <strong><?php echo htmlspecialchars($returnRequest['return_tracking_number'] ?? '-'); ?></strong>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($returnRequest['rejection_reason'])): ?>
                                    <div class="return-rejection">
                                        <strong>Alasan penolakan Admin:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($returnRequest['rejection_reason'])); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($returnImages): ?>
                                    <div class="return-evidence-list">
                                        <?php foreach ($returnImages as $imagePath): ?>
                                            <a href="<?php echo htmlspecialchars($imagePath); ?>" target="_blank" rel="noopener">
                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Bukti return">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($returnRequest['return_status'] === 'Approved'): ?>
                                    <div class="return-status-box return-shipping-details">
                                        <strong>Pengajuan disetujui Admin!</strong><br>
                                        Silakan kirim buku yang akan diretur ke alamat/petunjuk pengembalian yang diberikan Admin.
                                        Setelah mengirim paket, isi nama kurir dan nomor resi di bawah ini.
                                    </div>

                                    <form method="POST" class="return-shipping-form">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['confirm_order_token']); ?>">

                                        <h3>Informasi Pengiriman Retur</h3>
                                        <p>Pastikan nomor resi benar. Setelah dikirim, informasi ini tidak dapat diubah.</p>

                                        <div class="return-form-group">
                                            <label for="return_courier">Kurir Pengiriman</label>
                                            <select name="return_courier" id="return_courier" required>
                                                <option value="">Pilih kurir</option>
                                                <option value="JNE">JNE</option>
                                                <option value="J&T Express">J&amp;T Express</option>
                                                <option value="SiCepat">SiCepat</option>
                                                <option value="AnterAja">AnterAja</option>
                                                <option value="POS Indonesia">POS Indonesia</option>
                                                <option value="Ninja Xpress">Ninja Xpress</option>
                                                <option value="Other">Lainnya</option>
                                            </select>
                                        </div>

                                        <div class="return-form-group">
                                            <label for="return_tracking_number">Nomor Resi</label>
                                            <input
                                                type="text"
                                                name="return_tracking_number"
                                                id="return_tracking_number"
                                                minlength="5"
                                                maxlength="50"
                                                pattern="[A-Za-z0-9-]+"
                                                required
                                                placeholder="Masukkan nomor resi pengiriman retur">
                                        </div>

                                        <button
                                            type="submit"
                                            name="submit_return_shipping"
                                            value="1"
                                            class="order-summary-btn order-summary-btn-primary"
                                            onclick="return confirm('Pastikan buku sudah dikirim dan nomor resi sudah benar. Lanjutkan?')">
                                            <i class="fa-solid fa-truck"></i>
                                            Kirim Nomor Resi
                                        </button>
                                    </form>

                                <?php elseif ($returnRequest['return_status'] === 'Return Shipped'): ?>
                                    <div class="return-status-box return-shipping-details">
                                        <strong>Barang retur sedang dalam perjalanan.</strong><br>
                                        Kurir: <?php echo htmlspecialchars($returnRequest['return_courier'] ?? '-'); ?><br>
                                        Nomor resi: <?php echo htmlspecialchars($returnRequest['return_tracking_number'] ?? '-'); ?><br>
                                        Admin akan mengonfirmasi setelah barang diterima dan diperiksa.
                                    </div>

                                <?php elseif ($returnRequest['return_status'] === 'Item Received'): ?>
                                    <div class="return-status-box return-shipping-details">
                                        <strong>Barang retur sudah diterima Admin.</strong><br>
                                        Pesanan sedang menunggu pemeriksaan barang sebelum proses refund.
                                    </div>

                                <?php elseif (in_array($returnRequest['return_status'], ['Refund Processing', 'Refunded'], true)): ?>
                                    <div class="return-status-box return-shipping-details">
                                        <strong><?php echo htmlspecialchars($returnRequest['return_status']); ?></strong><br>
                                        <?php if ($returnRequest['return_status'] === 'Refund Processing'): ?>
                                            Refund sedang diproses oleh Admin.
                                        <?php else: ?>
                                            Refund telah selesai diproses.
                                        <?php endif; ?>
                                    </div>

                                <?php else: ?>
                                    <p class="return-help-text">
                                        Pengajuan return sudah tercatat. Pantau status pemeriksaan dari Admin di halaman ini.
                                    </p>
                                <?php endif; ?>

                            <?php elseif (
                                $order['status'] === 'Completed' &&
                                $shipping &&
                                $shipping['shipping_status'] === 'Delivered' &&
                                !empty($shipping['received_at']) &&
                                time() <= (strtotime($shipping['received_at']) + 259200)
                            ): ?>

                                <p class="return-description">
                                    Jika buku rusak, sobek, tidak sesuai pesanan, atau terjadi kesalahan pengiriman,
                                    kamu dapat mengajukan Return &amp; Refund. Foto bukti wajib disertakan.
                                    Batas pengajuan adalah 3 × 24 jam sejak pesanan diterima.
                                </p>

                                <form method="POST" enctype="multipart/form-data">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['confirm_order_token']); ?>">

                                    <div class="return-form-group">
                                        <label for="return_reason">Alasan Return</label>
                                        <select name="return_reason" id="return_reason" required>
                                            <option value="">Pilih alasan return</option>
                                            <option value="Buku rusak">Buku rusak</option>
                                            <option value="Buku sobek">Buku sobek</option>
                                            <option value="Buku tidak sesuai pesanan">Buku tidak sesuai pesanan</option>
                                            <option value="Kesalahan pengiriman">Kesalahan pengiriman</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>

                                    <div class="return-form-group">
                                        <label for="return_description">Deskripsi Masalah</label>
                                        <textarea
                                            name="return_description"
                                            id="return_description"
                                            rows="4"
                                            minlength="10"
                                            maxlength="2000"
                                            required
                                            placeholder="Jelaskan masalah pada buku yang kamu terima..."></textarea>
                                    </div>

                                    <div class="return-form-group">
                                        <label for="return_images">Foto Bukti (Wajib)</label>
                                        <input
                                            type="file"
                                            name="return_images[]"
                                            id="return_images"
                                            accept="image/jpeg,image/png"
                                            multiple
                                            required>
                                        <small class="return-help-text">
                                            Upload 1–5 foto JPG/PNG. Maksimal 5 MB untuk setiap foto.
                                        </small>
                                    </div>

                                    <button
                                        type="submit"
                                        name="submit_return"
                                        value="1"
                                        class="order-summary-btn order-summary-btn-primary"
                                        onclick="return confirm('Yakin ingin mengajukan Return & Refund?')">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        Ajukan Return
                                    </button>
                                </form>

                            <?php elseif (
                                $order['status'] === 'Completed' &&
                                $shipping &&
                                $shipping['shipping_status'] === 'Delivered'
                            ): ?>

                                <div class="return-status-box">
                                    Batas pengajuan Return &amp; Refund sudah lewat 3 × 24 jam sejak pesanan diterima.
                                </div>

                            <?php else: ?>

                                <p class="return-description">
                                    Tombol Ajukan Return akan tersedia setelah pesanan berstatus Completed dan sudah diterima.
                                </p>

                            <?php endif; ?>

                        </section>




                    </div>





                    <!-- ================= RIGHT ================= -->



                    <aside>



                        <div class="order-summary-card">



                            <h2>

                                Order Summary

                            </h2>



                            <div class="summary-detail-row">



                                <span>

                                    Order Date

                                </span>



                                <strong>

                                    <?php

                                    echo htmlspecialchars(

                                        $orderDate

                                    );

                                    ?>

                                </strong>



                            </div>



                            <div class="summary-detail-row">



                                <span>

                                    Payment

                                </span>



                                <strong>

                                    <?php

                                    echo htmlspecialchars(

                                        $paymentMethod

                                    );

                                    ?>

                                </strong>



                            </div>



                            <div class="summary-detail-row">



                                <span>

                                    Delivery

                                </span>



                                <strong>

                                    Free

                                </strong>



                            </div>



                            <hr>



                            <div class="summary-total-row">



                                <span>

                                    Total

                                </span>



                                <strong>



                                    Rp<?php

                                    echo number_format(

                                        $order['total_price'],

                                        0,

                                        ',',

                                        '.'

                                    );

                                    ?>



                                </strong>



                            </div>



                            <div class="payment-simulation">



                                <i class="fa-solid fa-shield-halved"></i>



                                <span>

                                    This order uses BookVerse's

                                    simulated payment system

                                    for educational purposes.

                                </span>



                            </div>



                            <div class="order-summary-actions">



                                <!-- VIEW RECEIPT -->



                                <a

                                    href="receipt.php?id=<?php echo (int) $order['id']; ?>"

                                    class="order-summary-btn order-summary-btn-primary">



                                    <i class="fa-solid fa-receipt"></i>



                                    View Receipt



                                </a>



                                <a

                                    href="orders.php"

                                    class="order-summary-btn order-summary-btn-primary">



                                    <i class="fa-solid fa-arrow-left"></i>



                                    Back to My Orders



                                </a>



                                <a

                                    href="index.php"

                                    class="order-summary-btn order-summary-btn-secondary">



                                    <i class="fa-solid fa-book"></i>



                                    Continue Shopping



                                </a>



                            </div>



                        </div>



                    </aside>



                </div>



            </div>



        </div>



    </main>





    <!-- ================= FOOTER ================= -->



    <footer class="footer orders-footer">



        <div class="container">



            <div class="footer-bottom">



                <p>

                    © 2026 BookVerse. All Rights Reserved.

                </p>



            </div>



        </div>



    </footer>



</body>



</html>
