<?php
session_start();

include "config/database.php";

/*
|--------------------------------------------------------------------------
| Ambil Data Cart dan Stok Terbaru
|--------------------------------------------------------------------------
*/

$cart = $_SESSION['cart'] ?? [];

$total = 0;
$stock_error = false;
$cart_stock = [];

// Ambil stok terbaru dari database
if (!empty($cart)) {
    $stockQuery = mysqli_query($conn, "SELECT id, stock FROM books");

    if ($stockQuery) {
        while ($stockRow = mysqli_fetch_assoc($stockQuery)) {
            $cart_stock[(int)$stockRow['id']] = (int)$stockRow['stock'];
        }
    }
}

// Periksa stok setiap buku di cart
foreach ($cart as $id => $item) {
    $id = (int)$id;
    $stock = $cart_stock[$id] ?? 0;
    $quantity = (int)$item['quantity'];

    if ($stock <= 0 || $quantity > $stock) {
        $stock_error = true;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart - BookVerse</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="header">

        <div class="container">

            <div class="navbar">

                <div class="logo">

                    <img src="assets/img/hugeicons_book-open-02.png" alt="BookVerse">

                    <div class="logo-text">
                        <h2>BookVerse</h2>
                        <p>Every Book Has a Story</p>
                    </div>

                </div>

                <nav>

                    <ul class="menu">

                        <li><a href="index.php">Home</a></li>
                        <li><a href="#">Books</a></li>
                        <li><a href="#">Categories</a></li>
                        <li><a href="#">About</a></li>
                        <li><a href="#">Contact</a></li>

                    </ul>

                </nav>

                <div class="right-menu">

                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchInput" placeholder="Search Books...">
                    </div>

                    <button type="button" class="icon-btn">
                        <i class="fa-regular fa-heart"></i>
                    </button>

                    <a href="cart.php" class="icon-btn">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>

                        <div class="profile-menu">

                            <button type="button" class="profile-btn" id="profileBtn">

                                <i class="fa-solid fa-user"></i>

                                <span><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>

                                <i class="fa-solid fa-chevron-down"></i>

                            </button>

                            <div class="profile-dropdown" id="profileDropdown">

                                <div class="profile-header">

                                    <i class="fa-solid fa-circle-user"></i>

                                    <div>

                                        <strong><?php echo htmlspecialchars($_SESSION['fullname']); ?></strong>

                                        <small><?php echo htmlspecialchars($_SESSION['email']); ?></small>

                                    </div>

                                </div>

                                <hr>

                                <a href="profile.php">
                                    <i class="fa-solid fa-user"></i>
                                    <span>Profile</span>
                                </a>

                                <a href="orders.php">
                                    <i class="fa-solid fa-box"></i>
                                    <span>Pesanan Saya</span>
                                </a>

                                <hr>

                                <a href="logout.php" class="logout-link">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    <span>Logout</span>
                                </a>

                            </div>

                        </div>

                    <?php else: ?>

                        <a href="login.php" class="login-btn">Login</a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </header>

    <!-- ================= CART ================= -->

    <main class="cart-page">

        <div class="container">

            <div class="cart-page-header">

                <div class="section-title">
                    <h2>Shopping Cart</h2>
                    <p>Review the books you want to purchase.</p>
                </div>

                <a href="index.php" class="cart-back-btn">
                    <i class="fa-solid fa-arrow-left"></i>
                    Kembali ke Home
                </a>

            </div>

            <?php if (empty($cart)): ?>

                <div class="cart-empty">

                    <i class="fa-solid fa-cart-shopping"></i>

                    <h3>Keranjang Masih Kosong</h3>

                    <p>Yuk cari buku favorit kamu dan tambahkan ke keranjang.</p>

                    <a href="index.php" class="btn-purple">
                        <i class="fa-solid fa-book"></i>
                        Mulai Belanja
                    </a>

                </div>

            <?php else: ?>

                <div class="cart-layout">

                    <!-- ================= CART ITEMS ================= -->

                    <div class="cart-items">

                        <?php foreach ($cart as $item): ?>

                            <?php
                            $bookId = (int)$item['id'];
                            $quantity = (int)$item['quantity'];
                            $stock = $cart_stock[$bookId] ?? 0;

                            $subtotal = $item['price'] * $quantity;
                            $total += $subtotal;

                            $outOfStock = $stock <= 0;
                            $quantityExceeded = $quantity > $stock;
                            ?>

                            <div class="cart-item">

                                <div class="cart-item-image">

                                    <img
                                        src="<?php echo htmlspecialchars($item['image']); ?>"
                                        alt="<?php echo htmlspecialchars($item['title']); ?>">

                                </div>

                                <div class="cart-item-info">

                                    <h3><?php echo htmlspecialchars($item['title']); ?></h3>

                                    <p><?php echo htmlspecialchars($item['author']); ?></p>

                                    <span class="cart-item-price">
                                        Rp<?php echo number_format($item['price'], 0, ',', '.'); ?>
                                    </span>

                                    <?php if ($outOfStock): ?>

                                        <p style="color:#e53935;font-size:12px;font-weight:600;margin-top:6px;">
                                            Out of Stock
                                        </p>

                                    <?php elseif ($quantityExceeded): ?>

                                        <p style="color:#e53935;font-size:12px;font-weight:600;margin-top:6px;">
                                            Only <?php echo $stock; ?> left. Please reduce quantity.
                                        </p>

                                    <?php elseif ($stock <= 5): ?>

                                        <p style="color:#e59a18;font-size:12px;font-weight:600;margin-top:6px;">
                                            Only <?php echo $stock; ?> left
                                        </p>

                                    <?php endif; ?>

                                </div>

                                <!-- Quantity -->

                                <div class="cart-item-quantity">

                                    <a
                                        href="update-cart.php?id=<?php echo $bookId; ?>&action=decrease"
                                        class="quantity-btn">

                                        <i class="fa-solid fa-minus"></i>

                                    </a>

                                    <span><?php echo $quantity; ?></span>

                                    <?php if (!$outOfStock && $quantity < $stock): ?>

                                        <a
                                            href="update-cart.php?id=<?php echo $bookId; ?>&action=increase"
                                            class="quantity-btn">

                                            <i class="fa-solid fa-plus"></i>

                                        </a>

                                    <?php else: ?>

                                        <a
                                            href="javascript:void(0)"
                                            class="quantity-btn"
                                            aria-disabled="true"
                                            style="opacity:0.4;pointer-events:none;cursor:not-allowed;">

                                            <i class="fa-solid fa-plus"></i>

                                        </a>

                                    <?php endif; ?>

                                </div>

                                <!-- Subtotal -->

                                <div class="cart-item-subtotal">

                                    <strong>
                                        Rp<?php echo number_format($subtotal, 0, ',', '.'); ?>
                                    </strong>

                                </div>

                                <!-- Remove -->

                                <a
                                    href="remove-from-cart.php?id=<?php echo $bookId; ?>"
                                    class="cart-remove">

                                    <i class="fa-solid fa-trash"></i>

                                </a>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <!-- ================= CART SUMMARY ================= -->

                    <div class="cart-summary">

                        <h3>Order Summary</h3>

                        <div class="summary-row">

                            <span>Subtotal</span>

                            <strong>
                                Rp<?php echo number_format($total, 0, ',', '.'); ?>
                            </strong>

                        </div>

                        <div class="summary-row">

                            <span>Delivery</span>

                            <strong>Free</strong>

                        </div>

                        <hr>

                        <div class="summary-total">

                            <span>Total</span>

                            <strong>
                                Rp<?php echo number_format($total, 0, ',', '.'); ?>
                            </strong>

                        </div>

                        <!-- Checkout -->

                        <?php if (!$stock_error): ?>

                            <a href="checkout.php" class="checkout-btn">
                                Proceed to Checkout
                            </a>

                        <?php else: ?>

                            <a
                                href="javascript:void(0)"
                                class="checkout-btn"
                                aria-disabled="true"
                                style="opacity:0.5;pointer-events:none;cursor:not-allowed;">

                                Stock Unavailable

                            </a>

                            <p style="color:#e53935;font-size:12px;text-align:center;margin-top:10px;">
                                Please update your cart quantity before checkout.
                            </p>

                        <?php endif; ?>

                        <a href="index.php" class="continue-shopping">

                            <i class="fa-solid fa-arrow-left"></i>

                            Continue Shopping

                        </a>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </main>

    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        <div class="container">

            <div class="footer-content">

                <div class="footer-logo">

                    <img src="assets/img/hugeicons_book-open-02.png" alt="BookVerse">

                    <h2>BookVerse</h2>

                    <p>
                        Every Book Has a Story.
                        Discover books that inspire your journey.
                    </p>

                </div>

                <div class="footer-links">

                    <h3>Quick Links</h3>

                    <ul>

                        <li><a href="index.php">Home</a></li>
                        <li><a href="#">Books</a></li>
                        <li><a href="#">Categories</a></li>
                        <li><a href="#">About</a></li>

                    </ul>

                </div>

                <div class="footer-contact">

                    <h3>Contact</h3>

                    <p>Email : info@bookverse.com</p>
                    <p>Phone : +62 812 3456 7890</p>
                    <p>Jakarta, Indonesia</p>

                </div>

            </div>

            <div class="footer-bottom">

                <p>© 2026 BookVerse. All Rights Reserved.</p>

            </div>

        </div>

    </footer>

    <script src="assets/js/script.js"></script>

</body>
</html>