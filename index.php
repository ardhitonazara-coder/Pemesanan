<?php
// index.php - Halaman Utama Mashchickburn dengan Fitur Ulasan ⭐
include 'koneksi.php';

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 🔥 FUNGSI HELPER: Rating & Reviews
function get_avg_rating($koneksi, $product_id) {
    $pid = intval($product_id);
    $result = mysqli_query($koneksi, "SELECT AVG(rating) as avg, COUNT(*) as total FROM reviews WHERE product_id = $pid");
    $data = mysqli_fetch_assoc($result);
    return ['avg' => $data['avg'] ? round($data['avg'], 1) : 0, 'total' => $data['total'] ?? 0];
}

function get_reviews($koneksi, $product_id, $limit = 3) {
    $pid = intval($product_id);
    $lim = intval($limit);
    $reviews = [];
    $query = mysqli_query($koneksi, "SELECT * FROM reviews WHERE product_id = $pid ORDER BY created_at DESC LIMIT $lim");
    while($row = mysqli_fetch_assoc($query)) { $reviews[] = $row; }
    return $reviews;
}

function render_stars($rating, $readonly = true) {
    $stars = '';
    for($i = 1; $i <= 5; $i++) {
        $active = $i <= $rating ? 'active' : '';
        if($readonly) {
            $stars .= '<span class="star-display '.$active.'">★</span>';
        } else {
            $stars .= '<span class="star-input" data-value="'.$i.'" onclick="selectStar(this)">★</span>';
        }
    }
    return $stars;
}

// 🔥 FUNGSI: Format Rupiah
if (!function_exists('rupiah')) {
    function rupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}

// 🔥 FUNGSI: Cek Login
if (!function_exists('cek_login')) {
    function cek_login() {
        if (!isset($_SESSION['user_id'])) {
            echo "<script>alert('⚠️ Silakan login terlebih dahulu!'); window.location='login.php';</script>";
            exit;
        }
    }
}
cek_login();

// ============================================
// ⭐ PROSES: SUBMIT ULASAN / RATING
// ============================================
if (isset($_POST['submit_ulasan']) && isset($_SESSION['user_id'])) {
    $product_id = intval($_POST['product_id']);
    $user_id = intval($_SESSION['user_id']);
    $nama_user = htmlspecialchars($_SESSION['nama'] ?? 'Anonim');
    $rating = intval($_POST['rating']);
    $komentar = trim($_POST['komentar'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        echo "<script>alert('❌ Rating harus antara 1-5 bintang!'); window.history.back();</script>";
        exit;
    }
    
    $cek = mysqli_query($koneksi, "SELECT id FROM reviews WHERE product_id = $product_id AND user_id = $user_id");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('ℹ️ Anda sudah memberikan ulasan untuk produk ini!'); window.history.back();</script>";
        exit;
    }
    
    $stmt = mysqli_prepare($koneksi, "INSERT INTO reviews (product_id, user_id, nama_user, rating, komentar) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiiss", $product_id, $user_id, $nama_user, $rating, $komentar);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('✅ Terima kasih! Ulasan Anda berhasil dikirim! ⭐'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal mengirim ulasan. Silakan coba lagi.'); window.history.back();</script>";
        error_log("Review Error: ".mysqli_error($koneksi));
    }
    mysqli_stmt_close($stmt);
    exit;
}

// 🔥 LOGIKA: Tambah ke Keranjang
if (isset($_POST['tambah_keranjang'])) {
    $id_produk = intval($_POST['id_produk']);
    $nama_produk = trim($_POST['nama_produk']);
    $harga = floatval($_POST['harga']);
    $stok_tersedia = intval($_POST['stok']);
    $qty_input = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
    
    if ($qty_input < 1) $qty_input = 1;
    if ($stok_tersedia < $qty_input) {
        echo "<script>alert('❌ Maaf, stok tidak mencukupi! Stok tersedia: " . $stok_tersedia . "'); window.history.back();</script>";
        exit;
    }
    
    $item = "$id_produk|$nama_produk|$harga|$qty_input";
    
    if (!isset($_SESSION['keranjang'])) { $_SESSION['keranjang'] = []; }
    
    $found = false;
    foreach ($_SESSION['keranjang'] as $key => $val) {
        $data = explode("|", $val);
        if ($data[0] == $id_produk) {
            $new_qty = intval($data[3]) + $qty_input;
            if ($new_qty > $stok_tersedia) {
                 echo "<script>alert('❌ Stok tidak cukup jika ditambahkan ke keranjang. Maksimal pembelian: " . $stok_tersedia . "'); window.history.back();</script>";
                 exit;
            }
            $data[3] = $new_qty;
            $_SESSION['keranjang'][$key] = implode("|", $data);
            $found = true;
            break;
        }
    }
    
    if (!$found) { $_SESSION['keranjang'][] = $item; }
    
    echo "<script>alert('✅ Berhasil menambahkan " . $qty_input . " porsi ke keranjang!'); window.location='index.php';</script>";
    exit;
}

// 🔥 QUERY: Ambil semua produk
$query_produk = mysqli_query($koneksi, "SELECT * FROM products ORDER BY nama_produk ASC");

// Info user
$nama_user = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Pengguna';
$role_user = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$total_keranjang = isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mashchickburn - Menu Lezat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%); min-height: 100vh; }
        .header { background: linear-gradient(135deg, #320810 0%, #4a0f18 100%); color: white; padding: 0; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .header-top { display: flex; align-items: center; justify-content: space-between; padding: 15px 30px; max-width: 1400px; margin: 0 auto; flex-wrap: wrap; gap: 15px; }
        .logo-section { display: flex; align-items: center; gap: 20px; }
        .logo-section img { width: 80px; height: 80px; object-fit: contain; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .brand-text h1 { font-size: 28px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); margin-bottom: 3px; }
        .brand-text p { font-size: 13px; opacity: 0.9; font-style: italic; }
        .user-welcome { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.15); padding: 10px 20px; border-radius: 30px; backdrop-filter: blur(5px); }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #ff6b35, #f7c59f); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; color: #320810; }
        .user-info { text-align: left; }
        .user-info .greeting { font-size: 11px; opacity: 0.9; margin-bottom: 2px; }
        .user-info .name { font-weight: 600; font-size: 15px; }
        .user-info .role { font-size: 10px; opacity: 0.8; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px; display: inline-block; margin-top: 3px; }
        .nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .nav a { color: white; text-decoration: none; padding: 10px 18px; border-radius: 25px; background: rgba(255,255,255,0.1); transition: all 0.3s; font-weight: 500; display: flex; align-items: center; gap: 6px; font-size: 14px; }
        .nav a:hover { background: rgba(255,255,255,0.25); transform: translateY(-2px); }
        .nav a.logout { background: rgba(244, 67, 54, 0.3); }
        .nav a.logout:hover { background: rgba(244, 67, 54, 0.6); }
        .hero { background: linear-gradient(135deg, #320810 0%, #6b1220 100%); padding: 40px 30px; text-align: center; color: white; position: relative; overflow: hidden; }
        .hero::before { content: ''; position: absolute; top: -50%; right: -10%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%; }
        .hero h2 { font-size: 32px; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .hero p { font-size: 16px; opacity: 0.95; }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .section-title { text-align: center; margin: 30px 0; color: #320810; font-size: 28px; font-weight: bold; position: relative; padding-bottom: 15px; }
        .section-title::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 100px; height: 4px; background: linear-gradient(90deg, #320810, #ff6b35); border-radius: 2px; }
        .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; margin-top: 30px; }
        .product-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(50, 8, 16, 0.15); transition: all 0.3s; border: 2px solid transparent; }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(50, 8, 16, 0.25); border-color: #320810; }
        .product-card img { width: 100%; height: 220px; object-fit: cover; display: block; background-color: #eee; }
        .product-info { padding: 18px; }
        .product-info h3 { color: #320810; font-size: 20px; margin-bottom: 8px; font-weight: bold; }
        .product-info p { color: #666; font-size: 13px; margin-bottom: 12px; line-height: 1.5; }
        .price { color: #320810; font-weight: bold; font-size: 22px; margin-bottom: 10px; }
        
        /* 🔥 QTY CONTROL */
        .qty-control { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 15px; background: #f8f9fa; padding: 8px; border-radius: 25px; width: fit-content; margin-left: auto; margin-right: auto; }
        .qty-btn { width: 32px; height: 32px; border: none; border-radius: 50%; background: #320810; color: white; font-weight: bold; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .qty-btn:hover { background: #4a0f18; transform: scale(1.1); }
        .qty-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .qty-display { font-weight: bold; color: #320810; min-width: 25px; text-align: center; font-size: 16px; }
        
        /* 📦 STOCK BADGE */
        .stock-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 12px; }
        .stock-badge.tersedia { background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; border: 1px solid #28a745; }
        .stock-badge.sedikit { background: linear-gradient(135deg, #fff3cd, #ffeaa7); color: #856404; border: 1px solid #ffc107; }
        .stock-badge.habis { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; border: 1px solid #dc3545; }
        .stock-badge .icon { font-size: 14px; }
        .stock-badge .jumlah { font-weight: 700; }
        
        /* 🛒 BUTTON */
        .btn { background: linear-gradient(135deg, #320810 0%, #4a0f18 100%); color: white; padding: 10px 20px; border: none; border-radius: 25px; cursor: pointer; width: 100%; font-size: 14px; font-weight: 600; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .btn:hover { background: linear-gradient(135deg, #4a0f18 0%, #6b1220 100%); transform: scale(1.02); box-shadow: 0 5px 15px rgba(50, 8, 16, 0.3); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; background: #ccc; }
        
        /* ⭐ STAR RATING STYLES */
        .star-display, .star-input { font-size: 18px; color: #ddd; cursor: default; transition: color 0.2s; user-select: none; }
        .star-display.active, .star-input.active, .star-input:hover { color: #ffc107; }
        .star-input { cursor: pointer; }
        .star-input:hover ~ .star-input { color: #ddd !important; }
        
        /* 📝 REVIEW SECTION */
        .review-section { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .review-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px; }
        .review-avg { display: flex; align-items: center; gap: 5px; font-weight: 600; color: #320810; }
        .review-avg .stars { color: #ffc107; font-size: 16px; }
        .review-avg .count { font-size: 12px; color: #666; margin-left: 5px; }
        .btn-ulasan { background: linear-gradient(135deg, #ffc107, #e0a800); color: #333; padding: 6px 14px; border: none; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-ulasan:hover { transform: scale(1.05); box-shadow: 0 3px 10px rgba(255, 193, 7, 0.4); }
        
        /* 💬 REVIEW LIST */
        .review-list { max-height: 150px; overflow-y: auto; margin-bottom: 12px; padding-right: 5px; }
        .review-list::-webkit-scrollbar { width: 4px; }
        .review-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
        .review-item { background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 8px; font-size: 12px; }
        .review-item .reviewer { font-weight: 600; color: #320810; display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
        .review-item .reviewer .stars { color: #ffc107; font-size: 14px; }
        .review-item .comment { color: #555; line-height: 1.4; }
        .review-item .date { font-size: 10px; color: #999; margin-top: 4px; }
        
        /* ✍️ REVIEW FORM */
        .review-form-container { display: none; margin-top: 10px; padding: 12px; background: #fff9f9; border-radius: 10px; border: 1px solid #ffe0e0; }
        .review-form-container.active { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .review-form .star-inputs { display: flex; gap: 3px; margin-bottom: 10px; justify-content: center; }
        .review-form textarea { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 13px; resize: vertical; min-height: 60px; margin-bottom: 10px; font-family: inherit; }
        .review-form textarea:focus { outline: none; border-color: #320810; }
        .review-form .form-actions { display: flex; gap: 8px; }
        .review-form .btn-submit { flex: 1; background: linear-gradient(135deg, #320810, #4a0f18); color: white; padding: 8px; border: none; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .review-form .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(50, 8, 16, 0.3); }
        .review-form .btn-cancel { flex: 1; background: #eee; color: #333; padding: 8px; border: none; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .review-form .btn-cancel:hover { background: #ddd; }
        
        /* 📦 Empty State & Footer */
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .empty-state p { color: #320810; font-size: 16px; margin-top: 15px; }
        .footer { background: #320810; color: white; text-align: center; padding: 20px; margin-top: 50px; font-size: 13px; }
        
        @media (max-width: 768px) { 
            .header-top { flex-direction: column; align-items: flex-start; } 
            .logo-section { margin-bottom: 15px; } 
            .user-welcome { order: -1; margin-bottom: 10px; } 
            .nav { width: 100%; justify-content: flex-start; overflow-x: auto; padding-bottom: 5px; } 
            .hero h2 { font-size: 24px; } 
            .products { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); } 
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-top">
        <div class="logo-section">
            <img src="assets/chaos.png" alt="Mashchickburn Logo" onerror="this.src='https://via.placeholder.com/80?text=Logo'">
            <div class="brand-text">
                <h1>🍗 Mashchickburn</h1>
                <p>Mash chick burn & Mash Nugget sausage burn</p>
            </div>
        </div>
        
        <div class="user-welcome">
            <div class="user-avatar"><?= strtoupper(substr($nama_user, 0, 1)) ?></div>
            <div class="user-info">
                <div class="greeting">👋 Halo,</div>
                <div class="name"><?= $nama_user ?></div>
                <?php if ($role_user == 'admin'): ?>
                    <span class="role">👑 Admin</span>
                <?php else: ?>
                    <span class="role">👤 Member</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="nav">
            <a href="index.php">🏠 Beranda</a>
            <a href="checkout.php">🛒 Keranjang (<?= $total_keranjang ?>)</a>
            <a href="status_pesanan.php">📦 Status</a>
            <?php if ($role_user == 'admin'): ?>
                <a href="admin_order.php">⚙️ Admin</a>
                <a href="admin_produk.php">📦 Kelola Stok</a>
            <?php endif; ?>
            <a href="logout.php" class="logout">🚪 Keluar</a>
        </div>
    </div>
    
    <div class="hero">
        <h2>Selamat Datang di Mashchickburn!</h2>
        <p>Pilih menu favorit Anda dan nikmati kelezatannya</p>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <h2 class="section-title">🍽️ Menu Kami</h2>
    
    <?php if (!$query_produk || mysqli_num_rows($query_produk) == 0): ?>
        <div class="empty-state">
            <div style="font-size: 64px;">🍗</div>
            <p style="color: #320810; font-weight: bold;">⚠️ Belum ada produk tersedia</p>
            <p>Hubungi admin untuk menambahkan menu</p>
        </div>
    <?php else: ?>
        <div class="products">
            <?php while ($row = mysqli_fetch_assoc($query_produk)): 
                $nama_produk = strtolower($row['nama_produk']);
                $gambar_produk = '';
                
                // Logika Gambar
                if (!empty($row['gambar'])) {
                    $path_cek = 'uploads/' . $row['gambar'];
                    if (file_exists($path_cek)) { $gambar_produk = $path_cek; } 
                    else {
                        if (strpos($nama_produk, 'nugget') !== false || strpos($nama_produk, 'sausage') !== false) { $gambar_produk = 'assets/lebih_tajam2.jpg'; } 
                        else { $gambar_produk = 'assets/menuaja.jpg'; }
                    }
                } elseif (strpos($nama_produk, 'nugget') !== false || strpos($nama_produk, 'sausage') !== false) {
                    $gambar_produk = 'assets/lebih_tajam2.jpg';
                } else {
                    $gambar_produk = 'assets/menuaja.jpg';
                }

                if (!file_exists($gambar_produk) && strpos($gambar_produk, 'http') === false) {
                    $gambar_produk = 'https://via.placeholder.com/300x220?text=Gambar+Tidak+Ditemukan';
                }
                
                // Logika Stok
                $stok = intval($row['stok']);
                if ($stok == 0) { $stock_class = 'habis'; $stock_icon = '🔴'; $stock_text = 'Stok Habis'; $stock_detail = ''; } 
                elseif ($stok < 5) { $stock_class = 'sedikit'; $stock_icon = '🟡'; $stock_text = 'Sisa'; $stock_detail = $stok; } 
                else { $stock_class = 'tersedia'; $stock_icon = '🟢'; $stock_text = 'Tersedia'; $stock_detail = $stok; }
                
                // Data Reviews
                $rating_data = get_avg_rating($koneksi, $row['id']);
                $reviews = get_reviews($koneksi, $row['id'], 3);
                $already_reviewed = false;
                if (isset($_SESSION['user_id'])) {
                    $uid = intval($_SESSION['user_id']);
                    $pid = intval($row['id']);
                    $cek_review = mysqli_query($koneksi, "SELECT id FROM reviews WHERE product_id = $pid AND user_id = $uid");
                    $already_reviewed = mysqli_num_rows($cek_review) > 0;
                }
            ?>
            
            <!-- Product Card -->
            <div class="product-card">
                <img src="<?= $gambar_produk ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>" onerror="this.src='https://via.placeholder.com/300x220?text=Gambar+Tidak+Ditemukan';">
                
                <div class="product-info">
                    <h3><?= htmlspecialchars($row['nama_produk']) ?></h3>
                    <p><?= htmlspecialchars($row['deskripsi']) ?></p>
                    <div class="price"><?= rupiah($row['harga']) ?></div>
                    
                    <div class="stock-badge <?= $stock_class ?>">
                        <span class="icon"><?= $stock_icon ?></span>
                        <span class="label"><?= $stock_text ?></span>
                        <?php if ($stock_detail): ?><span class="jumlah">: <?= $stock_detail ?></span><?php endif; ?>
                    </div>
                    
                    <!-- Form Tambah Keranjang -->
                    <form method="POST">
                        <input type="hidden" name="id_produk" value="<?= $row['id'] ?>">
                        <input type="hidden" name="nama_produk" value="<?= htmlspecialchars($row['nama_produk']) ?>">
                        <input type="hidden" name="harga" value="<?= $row['harga'] ?>">
                        <input type="hidden" name="stok" value="<?= $stok ?>">
                        
                        <?php if ($stok > 0): ?>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" onclick="updateQty(this, -1)">-</button>
                            <span class="qty-display" id="qty-display-<?= $row['id'] ?>">1</span>
                            <input type="hidden" name="qty" id="qty-input-<?= $row['id'] ?>" value="1">
                            <button type="button" class="qty-btn" onclick="updateQty(this, 1, <?= $stok ?>)">+</button>
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" name="tambah_keranjang" class="btn" <?= $stok == 0 ? 'disabled' : '' ?>>
                            <?= $stok == 0 ? '❌ Stok Habis' : '🛒 Tambah ke Keranjang' ?>
                        </button>
                    </form>
                    
                    <!-- ⭐ REVIEW SECTION -->
                    <div class="review-section">
                        <div class="review-header">
                            <div class="review-avg">
                                <span class="stars"><?= render_stars(round($rating_data['avg'])) ?></span>
                                <span class="avg-value"><?= $rating_data['avg'] > 0 ? $rating_data['avg'] : '-' ?>/5</span>
                                <span class="count">(<?= $rating_data['total'] ?>)</span>
                            </div>
                            <?php if (isset($_SESSION['user_id']) && !$already_reviewed): ?>
                                <button type="button" class="btn-ulasan" onclick="toggleReviewForm(<?= $row['id'] ?>)">✍️ Ulasan</button>
                            <?php elseif ($already_reviewed): ?>
                                <span style="font-size:11px;color:#28a745;font-weight:500">✓ Diulas</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- List Ulasan -->
                        <?php if (!empty($reviews)): ?>
                        <div class="review-list">
                            <?php foreach($reviews as $rev): ?>
                            <div class="review-item">
                                <div class="reviewer">
                                    <span><?= htmlspecialchars($rev['nama_user']) ?></span>
                                    <span class="stars"><?= render_stars($rev['rating']) ?></span>
                                </div>
                                <p class="comment"><?= htmlspecialchars($rev['komentar']) ?></p>
                                <span class="date"><?= date('d/m/y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($rating_data['total'] > 3): ?>
                            <div style="text-align:center;font-size:11px;color:#666;">+<?= $rating_data['total'] - 3 ?> lainnya</div>
                        <?php endif; ?>
                        <?php else: ?>
                            <p style="font-size:12px;color:#999;text-align:center;">Belum ada ulasan. Jadilah yang pertama! ⭐</p>
                        <?php endif; ?>
                        
                        <!-- Form Ulasan -->
                        <?php if (isset($_SESSION['user_id']) && !$already_reviewed): ?>
                        <div class="review-form-container" id="review-form-<?= $row['id'] ?>">
                            <form method="POST" class="review-form">
                                <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="rating" id="rating-input-<?= $row['id'] ?>" value="0" required>
                                <div class="star-inputs" id="star-inputs-<?= $row['id'] ?>"><?= render_stars(0, false) ?></div>
                                <textarea name="komentar" placeholder="Tulis pengalaman Anda..." maxlength="500" required></textarea>
                                <div class="form-actions">
                                    <button type="button" class="btn-cancel" onclick="toggleReviewForm(<?= $row['id'] ?>)">Batal</button>
                                    <button type="submit" name="submit_ulasan" class="btn-submit">Kirim ⭐</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- END REVIEW SECTION -->
                    
                </div>
            </div>
            
            <!-- Script JS per Produk (Qty) -->
            <script>
            function updateQty(btn, change, maxStock = 999) {
                const card = btn.closest('.product-card');
                const display = card.querySelector('.qty-display');
                const input = card.querySelector('input[name="qty"]');
                let currentQty = parseInt(display.innerText);
                let newQty = currentQty + change;
                if (newQty < 1) newQty = 1;
                if (newQty > maxStock) { alert('⚠️ Maksimal: ' + maxStock); newQty = maxStock; }
                display.innerText = newQty; input.value = newQty;
            }
            </script>
            
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2026 Mashchickburn. All Rights Reserved. | 🍗 Nikmatnya Mashpotato & Chick Burn</p>
</div>

<!-- Global JavaScript -->
<script>
// ⭐ Pilih bintang rating
function selectStar(el) {
    const value = parseInt(el.dataset.value);
    const container = el.closest('.star-inputs');
    const input = container.closest('form').querySelector('input[name="rating"]');
    container.querySelectorAll('.star-input').forEach((star, idx) => {
        star.classList.toggle('active', idx < value);
    });
    input.value = value;
}

// ✍️ Toggle form ulasan
function toggleReviewForm(productId) {
    const form = document.getElementById('review-form-' + productId);
    if (form) {
        form.classList.toggle('active');
        if (form.classList.contains('active')) {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

// 💡 Auto-close alert (jika ada flash message)
document.addEventListener('DOMContentLoaded', function() {
    const flash = document.querySelector('.flash-message');
    if (flash) {
        setTimeout(() => { flash.style.opacity = '0'; setTimeout(() => flash.remove(), 300); }, 3000);
    }
});
</script>

</body>
</html>