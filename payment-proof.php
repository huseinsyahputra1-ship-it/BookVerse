
<?php

require_once __DIR__ . '/config/user-session.php';
require_once __DIR__ . '/config/database.php';

// =========================================================
// USER ACCESS
// =========================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// =========================================================
// REQUEST VALIDATION
// =========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}

// =========================================================
// CSRF VALIDATION
// =========================================================

$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['payment_proof_token']) ||
    !hash_equals($_SESSION['payment_proof_token'], $csrf_token)
) {
    die("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

// =========================================================
// ORDER ID
// =========================================================

$order_id = filter_input(
    INPUT_POST,
    'order_id',
    FILTER_VALIDATE_INT
);

if (!$order_id || $order_id <= 0) {
    die("Nomor pesanan tidak valid.");
}

// =========================================================
// FILE VALIDATION
// =========================================================

if (
    !isset($_FILES['payment_proof']) ||
    $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK
) {
    header("Location: payment-success.php");
    exit;
}

$file = $_FILES['payment_proof'];

$max_size = 5 * 1024 * 1024;

if ($file['size'] <= 0 || $file['size'] > $max_size) {
    die("Ukuran bukti pembayaran maksimal 5 MB.");
}

// =========================================================
// VALIDASI EXTENSION DAN MIME
// =========================================================

$allowed_mimes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);

if (!$finfo) {
    die("Gagal memeriksa file.");
}

$mime_type = finfo_file($finfo, $file['tmp_name']);

finfo_close($finfo);

if (!isset($allowed_mimes[$mime_type])) {
    die("Format file harus JPG, JPEG, atau PNG.");
}

// Pastikan file benar-benar gambar
$image_info = getimagesize($file['tmp_name']);

if ($image_info === false) {
    die("File yang diunggah bukan gambar yang valid.");
}

$extension = $allowed_mimes[$mime_type];

// =========================================================
// CEK PESANAN DAN KEPEMILIKAN
// =========================================================

mysqli_begin_transaction($conn);

$new_file_path = null;

try {

    $check_stmt = mysqli_prepare(
        $conn,
        "SELECT
            pt.id,
            pt.payment_status,
            pt.expires_at,
            pt.payment_proof,
            o.user_id
        FROM payment_transactions pt
        INNER JOIN orders o ON pt.order_id = o.id
        WHERE pt.order_id = ?
        AND o.user_id = ?
        FOR UPDATE"
    );

    if (!$check_stmt) {
        throw new Exception("Gagal memeriksa pesanan.");
    }

    mysqli_stmt_bind_param(
        $check_stmt,
        "ii",
        $order_id,
        $user_id
    );

    if (!mysqli_stmt_execute($check_stmt)) {
        throw new Exception("Gagal membaca data pesanan.");
    }

    $result = mysqli_stmt_get_result($check_stmt);

    $payment = mysqli_fetch_assoc($result);

    mysqli_stmt_close($check_stmt);

    if (!$payment) {
        throw new Exception("Pesanan tidak ditemukan.");
    }

    // ---------------------------------------------
    // VALIDASI PAYMENT STATUS
    // ---------------------------------------------

    if (
        !in_array(
            $payment['payment_status'],
            ['Pending', 'Rejected'],
            true
        )
    ) {
        throw new Exception(
            "Pesanan ini tidak dapat mengunggah bukti pembayaran."
        );
    }

    // ---------------------------------------------
    // CEK EXPIRED
    // ---------------------------------------------

    if (
        !empty($payment['expires_at']) &&
        strtotime($payment['expires_at']) < time()
    ) {

        $expire_stmt = mysqli_prepare(
            $conn,
            "UPDATE payment_transactions
            SET payment_status = 'Expired'
            WHERE id = ?
            AND payment_status IN ('Pending', 'Rejected')"
        );

        mysqli_stmt_bind_param(
            $expire_stmt,
            "i",
            $payment['id']
        );

        mysqli_stmt_execute($expire_stmt);
        mysqli_stmt_close($expire_stmt);

        throw new Exception(
            "Batas waktu pembayaran sudah berakhir."
        );
    }

    // =================================================
    // UPLOAD DIRECTORY
    // =================================================

    $upload_directory = __DIR__ . '/assets/uploads/payment-proofs/';

    if (
        !is_dir($upload_directory) &&
        !mkdir($upload_directory, 0755, true)
    ) {
        throw new Exception("Gagal membuat folder upload.");
    }

    // =================================================
    // GENERATE RANDOM FILE NAME
    // =================================================

    $file_name = 'proof_' .
        $order_id . '_' .
        bin2hex(random_bytes(16)) .
        '.' . $extension;

    $new_file_path = $upload_directory . $file_name;

    $database_path = 'assets/uploads/payment-proofs/' . $file_name;

    if (!move_uploaded_file($file['tmp_name'], $new_file_path)) {
        throw new Exception("Gagal mengunggah bukti pembayaran.");
    }

    // =================================================
    // UPDATE PAYMENT TRANSACTION
    // =================================================

    $update_stmt = mysqli_prepare(
        $conn,
        "UPDATE payment_transactions
        SET
            payment_proof = ?,
            payment_status = 'Waiting Verification',
            rejection_reason = NULL,
            updated_at = NOW()
        WHERE id = ?
        AND payment_status IN ('Pending', 'Rejected')"
    );

    if (!$update_stmt) {
        throw new Exception("Gagal menyiapkan update pembayaran.");
    }

    mysqli_stmt_bind_param(
        $update_stmt,
        "si",
        $database_path,
        $payment['id']
    );

    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception("Gagal menyimpan bukti pembayaran.");
    }

    if (mysqli_stmt_affected_rows($update_stmt) !== 1) {
        throw new Exception("Status pembayaran sudah berubah.");
    }

    mysqli_stmt_close($update_stmt);

    // =================================================
    // COMMIT
    // =================================================

    mysqli_commit($conn);

    // Hapus bukti lama setelah bukti baru berhasil disimpan
    if (!empty($payment['payment_proof'])) {

        $old_file = __DIR__ . '/' . $payment['payment_proof'];

        if (
            is_file($old_file) &&
            strpos(
                realpath($old_file),
                realpath($upload_directory) . DIRECTORY_SEPARATOR
            ) === 0
        ) {
            unlink($old_file);
        }
    }

    unset($_SESSION['payment_proof_token']);

    header("Location: orders.php?payment=uploaded");
    exit;

} catch (Throwable $e) {

    mysqli_rollback($conn);

    // Hapus file baru jika proses database gagal
    if (
        $new_file_path !== null &&
        is_file($new_file_path)
    ) {
        unlink($new_file_path);
    }

    die(
        "Upload bukti pembayaran gagal: " .
        htmlspecialchars($e->getMessage()) .
        "<br><br><a href='orders.php'>Kembali ke Pesanan</a>"
    );
}
