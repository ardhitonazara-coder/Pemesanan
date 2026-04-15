<?php
// admin_produk.php - Admin: Kelola Produk, Stok, Rating & Ulasan ⭐
include 'koneksi.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// 🔒 Keamanan: Cek Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('⛔ Akses Ditolak! Halaman ini khusus Admin.'); window.location='index.php';</script>";
    exit;
}

$pesan = ''; $jenis_pesan = '';

// ============================================
// 🔥 PROSES: TAMBAH PRODUK BARU
// ============================================
if (isset($_POST['tambah_produk'])) {
    $nama = trim($_POST['nama_produk']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = floatval($_POST['harga']);
    $stok = intval($_POST['stok']);
    $gambar = '';
    
    // Upload gambar
    if (!empty($_FILES['gambar']['name'])) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $nama_file = 'produk_'.time().'.'.$ext;
        $target = 'uploads/'.$nama_file;
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
            $gambar = $nama_file;
        }
    }
    
    $stmt = mysqli_prepare($koneksi, "INSERT INTO products (nama_produk, deskripsi, harga, stok, gambar) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssdis", $nama, $deskripsi, $harga, $stok, $gambar);
    
    if (mysqli_stmt_execute($stmt)) {
        $pesan = "✅ Produk '$nama' berhasil ditambahkan!"; $jenis_pesan = 'success';
    } else {
        $pesan = "❌ Gagal: ".mysqli_error($koneksi); $jenis_pesan = 'error';
    }
    mysqli_stmt_close($stmt);
}

// ============================================
// 🔥 PROSES: EDIT PRODUK
// ============================================
if (isset($_POST['edit_produk'])) {
    $id = intval($_POST['id_produk']);
    $nama = trim($_POST['nama_produk']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = floatval($_POST['harga']);
    $stok = intval($_POST['stok']);
    
    // Cek upload gambar baru
    $gambar_sql = "";
    if (!empty($_FILES['gambar']['name'])) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $nama_file = 'produk_'.time().'.'.$ext;
        $target = 'uploads/'.$nama_file;
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
            // Hapus gambar lama
            $old = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT gambar FROM products WHERE id=$id"));
            if ($old['gambar'] && file_exists('uploads/'.$old['gambar'])) {
                unlink('uploads/'.$old['gambar']);
            }
            $gambar_sql = ", gambar = '$nama_file'";
        }
    }
    
    $stmt = mysqli_prepare($koneksi, "UPDATE products SET nama_produk=?, deskripsi=?, harga=?, stok=? $gambar_sql WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssdii", $nama, $deskripsi, $harga, $stok, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $pesan = "✅ Produk berhasil diupdate!"; $jenis_pesan = 'success';
    } else {
        $pesan = "❌ Gagal: ".mysqli_error($koneksi); $jenis_pesan = 'error';
    }
    mysqli_stmt_close($stmt);
}

// ============================================
// 🔥 PROSES: HAPUS PRODUK
// ============================================
if (isset($_GET['hapus_produk']) && is_numeric($_GET['hapus_produk'])) {
    $id = intval($_GET['hapus_produk']);
    
    // Hapus gambar dulu
    $old = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT gambar FROM products WHERE id=$id"));
    if ($old['gambar'] && file_exists('uploads/'.$old['gambar'])) {
        unlink('uploads/'.$old['gambar']);
    }
    
    // Hapus produk (reviews ikut terhapus karena ON DELETE CASCADE)
    $stmt = mysqli_prepare($koneksi, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $pesan = "✅ Produk berhasil dihapus!"; $jenis_pesan = 'success';
    } else {
        $pesan = "❌ Gagal: ".mysqli_error($koneksi); $jenis_pesan = 'error';
    }
    mysqli_stmt_close($stmt);
    header("Location: admin_produk.php"); exit;
}

// ============================================
// 🔥 PROSES: UPDATE STOK
// ============================================
if (isset($_POST['update_stok'])) {
    $id_produk = intval($_POST['id_produk']);
    $aksi = $_POST['aksi'];
    $jumlah = intval($_POST['jumlah']);
    
    if ($jumlah > 0) {
        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($koneksi, "UPDATE products SET stok = stok + ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $jumlah, $id_produk);
            $ket_aksi = "Ditambahkan";
        } elseif ($aksi === 'kurangi') {
            $stmt = mysqli_prepare($koneksi, "UPDATE products SET stok = GREATEST(0, stok - ?) WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $jumlah, $id_produk);
            $ket_aksi = "Dikurangi";
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $pesan = "✅ Stok produk ID #$id_produk <b>$ket_aksi</b> $jumlah!"; $jenis_pesan = 'success';
        } else {
            $pesan = "❌ Gagal: ".mysqli_error($koneksi); $jenis_pesan = 'error';
        }
        mysqli_stmt_close($stmt);
    }
}

// ============================================
// ⭐ PROSES: HAPUS ULASAN (REVIEW)
// ============================================
if (isset($_GET['hapus_ulasan']) && is_numeric($_GET['hapus_ulasan'])) {
    $id_review = intval($_GET['hapus_ulasan']);
    
    $stmt = mysqli_prepare($koneksi, "DELETE FROM reviews WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_review);
    
    if (mysqli_stmt_execute($stmt)) {
        $pesan = "✅ Ulasan berhasil dihapus!"; $jenis_pesan = 'success';
    } else {
        $pesan = "❌ Gagal menghapus ulasan."; $jenis_pesan = 'error';
    }
    mysqli_stmt_close($stmt);
    header("Location: admin_produk.php#reviews-section"); exit;
}

// ============================================
// 📊 QUERY DATA UNTUK GRAFIK & TABEL
// ============================================

// 1. Data Produk
$produk = mysqli_query($koneksi, "SELECT * FROM products ORDER BY nama_produk ASC");

// 2. Data Rating per Produk (untuk Chart)
$chart_data = [];
$chart_labels = [];
$chart_ratings = [];
$query_chart = mysqli_query($koneksi, "
    SELECT p.nama_produk, AVG(r.rating) as avg_rating, COUNT(r.id) as total_review 
    FROM products p 
    LEFT JOIN reviews r ON p.id = r.product_id 
    GROUP BY p.id 
    ORDER BY avg_rating DESC
");
while($row = mysqli_fetch_assoc($query_chart)) {
    $chart_labels[] = $row['nama_produk'];
    $chart_ratings[] = $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
    $chart_data[] = $row;
}

// 3. Data Semua Ulasan (untuk manajemen)
$all_reviews = mysqli_query($koneksi, "
    SELECT r.*, p.nama_produk as produk 
    FROM reviews r 
    JOIN products p ON r.product_id = p.id 
    ORDER BY r.created_at DESC
");

// 4. Data Statistik Ringkas
$stats = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        (SELECT COUNT(*) FROM products) as total_produk,
        (SELECT SUM(stok) FROM products) as total_stok,
        (SELECT COUNT(*) FROM reviews) as total_ulasan,
        (SELECT AVG(rating) FROM reviews) as avg_rating_global
"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Produk | Mashchickburn</title>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid #eee; flex-wrap: wrap; gap: 15px; }
        .header h1 { color: #320810; font-size: 24px; }
        .nav a { color: #555; text-decoration: none; margin-left: 15px; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .nav a:hover { color: #320810; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 5px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 5px solid #dc3545; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #320810, #4a0f18); color: white; padding: 20px; border-radius: 12px; text-align: center; }
        .stat-card .number { font-size: 28px; font-weight: bold; margin: 5px 0; }
        .stat-card .label { font-size: 12px; opacity: 0.9; }
        
        /* Section Styles */
        .section { margin-bottom: 40px; }
        .section-title { color: #320810; font-size: 20px; font-weight: bold; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #320810; display: flex; align-items: center; gap: 10px; }
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        th { background: #320810; color: white; padding: 15px; text-align: left; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #fff9f9; }
        
        .stok-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; text-align: center; min-width: 80px; }
        .stok-ok { background: #d4edda; color: #155724; }
        .stok-low { background: #fff3cd; color: #856404; }
        .stok-out { background: #f8d7da; color: #721c24; }
        
        .rating-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #fff3cd; color: #856404; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .rating-badge .stars { color: #ffc107; }
        
        /* Stock Control */
        .stock-control { display: flex; align-items: center; gap: 8px; background: #f8f9fa; padding: 5px; border-radius: 8px; border: 1px solid #ddd; }
        .stock-input { width: 60px; padding: 6px; border: 1px solid #ccc; border-radius: 5px; text-align: center; font-weight: bold; font-size: 14px; }
        .stock-input:focus { outline: none; border-color: #320810; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; transition: all 0.2s; }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .btn-add { background: #28a745; color: white; }
        .btn-sub { background: #ffc107; color: #333; }
        
        /* Buttons */
        .btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-primary { background: #320810; color: white; }
        .btn-primary:hover { background: #4a0f18; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 25px; border-radius: 15px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
        .modal-close:hover { color: #320810; }
        
        /* Form Styles */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #320810; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #320810; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        
        /* Chart Container */
        .chart-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .chart-wrapper { position: relative; height: 300px; }
        
        /* Reviews Section */
        .review-item { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 10px; border-left: 4px solid #320810; }
        .review-item .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
        .review-item .produk { font-weight: 600; color: #320810; }
        .review-item .user { font-size: 13px; color: #666; }
        .review-item .stars { color: #ffc107; font-size: 14px; }
        .review-item .komentar { font-size: 14px; color: #333; margin: 8px 0; }
        .review-item .date { font-size: 11px; color: #999; }
        .review-item .actions { margin-top: 10px; display: flex; gap: 8px; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: flex-start; }
            .nav { margin-top: 10px; }
            .nav a { margin-left: 0; margin-right: 15px; }
            table { font-size: 13px; }
            .stock-control { flex-wrap: wrap; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>📦 Kelola Produk & Ulasan</h1>
        <div class="nav">
            <a href="admin_order.php">📋 Pesanan</a>
            <a href="admin_laporan.php">📈 Laporan</a>
            <a href="index.php">🏠 Ke Toko</a>
            <a href="logout.php" style="color:#dc3545;">🚪 Logout</a>
        </div>
    </div>
    
    <!-- Notifikasi -->
    <?php if ($pesan): ?>
        <div class="alert alert-<?= $jenis_pesan ?>">
            <span><?= $pesan ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Produk</div>
            <div class="number"><?= $stats['total_produk'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Stok</div>
            <div class="number"><?= $stats['total_stok'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Ulasan</div>
            <div class="number"><?= $stats['total_ulasan'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Rating Rata-rata</div>
            <div class="number"><?= $stats['avg_rating_global'] ? round($stats['avg_rating_global'], 1) : '-' ?>/5 ⭐</div>
        </div>
    </div>
    
    <!-- 📊 Grafik Rating -->
    <div class="section" id="chart-section">
        <div class="section-title">📈 Grafik Rating Produk</div>
        <div class="chart-container">
            <div class="chart-wrapper">
                <canvas id="ratingChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- 📦 Manajemen Produk -->
    <div class="section" id="products-section">
        <div class="section-title">
            🍗 Daftar Produk 
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah')">+ Tambah Produk</button>
        </div>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produk</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Rating</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($produk) > 0): while($row = mysqli_fetch_assoc($produk)): 
                        $stok = intval($row['stok']);
                        if ($stok == 0) { $status_class = 'out'; $status_text = 'Habis'; $icon = '🔴'; } 
                        elseif ($stok < 5) { $status_class = 'low'; $status_text = 'Sisa '.$stok; $icon = '🟡'; } 
                        else { $status_class = 'ok'; $status_text = 'Aman'; $icon = '🟢'; }
                        
                        // Ambil rating produk ini
                        $rating_q = mysqli_query($koneksi, "SELECT AVG(rating) as avg, COUNT(*) as total FROM reviews WHERE product_id = {$row['id']}");
                        $rating_d = mysqli_fetch_assoc($rating_q);
                        $avg_rating = $rating_d['avg'] ? round($rating_d['avg'], 1) : 0;
                        $total_review = $rating_d['total'] ?? 0;
                    ?>
                    <tr>
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_produk']) ?></strong><br>
                            <small style="color:#666;"><?= htmlspecialchars(substr($row['deskripsi'], 0, 50)) ?><?= strlen($row['deskripsi']) > 50 ? '...' : '' ?></small>
                        </td>
                        <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                        <td style="font-weight:bold;"><?= $stok ?></td>
                        <td><span class="stok-badge stok-<?= $status_class ?>"><?= $icon ?> <?= $status_text ?></span></td>
                        <td>
                            <?php if ($total_review > 0): ?>
                                <span class="rating-badge">
                                    <span class="stars"><?= str_repeat('★', round($avg_rating)) ?><?= str_repeat('☆', 5-round($avg_rating)) ?></span>
                                    <?= $avg_rating ?> <small>(<?= $total_review ?>)</small>
                                </span>
                            <?php else: ?>
                                <small style="color:#999;">-</small>
                            <?php endif; ?>
                        </td>
                        <td style="display:flex; gap:5px; flex-wrap:wrap;">
                            <button class="btn btn-warning btn-sm" onclick="editProduk(<?= htmlspecialchars(json_encode($row)) ?>)">✏️</button>
                            <a href="?hapus_produk=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('⚠️ Hapus produk ini? Ulasan terkait juga akan terhapus.')">🗑️</a>
                            
                            <!-- Stock Control Mini -->
                            <form method="POST" class="stock-control" style="margin-top:5px;">
                                <input type="hidden" name="id_produk" value="<?= $row['id'] ?>">
                                <input type="number" name="jumlah" class="stock-input" value="1" min="1" max="100">
                                <button type="submit" name="update_stok" class="btn-action btn-sub" onclick="this.form.aksi.value='kurangi'" <?= $stok==0?'disabled':'' ?>>➖</button>
                                <button type="submit" name="update_stok" class="btn-action btn-add" onclick="this.form.aksi.value='tambah'">➕</button>
                                <input type="hidden" name="aksi" value="">
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px; color:#999;">📭 Belum ada produk.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ⭐ Manajemen Ulasan -->
    <div class="section" id="reviews-section">
        <div class="section-title">⭐ Kelola Ulasan Pelanggan</div>
        
        <?php if (mysqli_num_rows($all_reviews) > 0): ?>
            <?php while($rev = mysqli_fetch_assoc($all_reviews)): ?>
            <div class="review-item">
                <div class="header">
                    <div>
                        <span class="produk"><?= htmlspecialchars($rev['produk']) ?></span><br>
                        <span class="user">👤 <?= htmlspecialchars($rev['nama_user']) ?> • <span class="stars"><?= str_repeat('★', $rev['rating']) ?><?= str_repeat('☆', 5-$rev['rating']) ?></span></span>
                    </div>
                    <a href="?hapus_ulasan=<?= $rev['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('⚠️ Hapus ulasan ini?')">🗑️ Hapus</a>
                </div>
                <p class="komentar">"<?= htmlspecialchars($rev['komentar']) ?>"</p>
                <span class="date">📅 <?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?></span>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color:#666; text-align:center; padding:20px;">📭 Belum ada ulasan dari pelanggan.</p>
        <?php endif; ?>
    </div>
</div>

<!-- 🔹 Modal Tambah Produk -->
<div class="modal" id="modal-tambah">
    <div class="modal-content">
        <div class="modal-header">
            <h3>+ Tambah Produk Baru</h3>
            <button class="modal-close" onclick="closeModal('modal-tambah')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nama Produk *</label>
                <input type="text" name="nama_produk" required placeholder="Contoh: Mash Chick Burn Original">
            </div>
            <div class="form-group">
                <label>Deskripsi *</label>
                <textarea name="deskripsi" required placeholder="Jelaskan produk..."></textarea>
            </div>
            <div class="form-group">
                <label>Harga (Rp) *</label>
                <input type="number" name="harga" required min="0" placeholder="15000">
            </div>
            <div class="form-group">
                <label>Stok Awal *</label>
                <input type="number" name="stok" required min="0" value="0">
            </div>
            <div class="form-group">
                <label>Gambar Produk</label>
                <input type="file" name="gambar" accept="image/*">
                <small style="color:#666;">Format: JPG, PNG, MAX 2MB</small>
            </div>
            <button type="submit" name="tambah_produk" class="btn btn-primary" style="width:100%;">💾 Simpan Produk</button>
        </form>
    </div>
</div>

<!-- 🔹 Modal Edit Produk -->
<div class="modal" id="modal-edit">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✏️ Edit Produk</h3>
            <button class="modal-close" onclick="closeModal('modal-edit')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="form-edit">
            <input type="hidden" name="id_produk" id="edit-id">
            <div class="form-group">
                <label>Nama Produk *</label>
                <input type="text" name="nama_produk" id="edit-nama" required>
            </div>
            <div class="form-group">
                <label>Deskripsi *</label>
                <textarea name="deskripsi" id="edit-deskripsi" required></textarea>
            </div>
            <div class="form-group">
                <label>Harga (Rp) *</label>
                <input type="number" name="harga" id="edit-harga" required min="0">
            </div>
            <div class="form-group">
                <label>Stok *</label>
                <input type="number" name="stok" id="edit-stok" required min="0">
            </div>
            <div class="form-group">
                <label>Ganti Gambar</label>
                <input type="file" name="gambar" accept="image/*">
                <small style="color:#666;">Kosongkan jika tidak ingin mengganti</small>
            </div>
            <button type="submit" name="edit_produk" class="btn btn-primary" style="width:100%;">💾 Update Produk</button>
        </form>
    </div>
</div>

<!-- Chart.js Script -->
<script>
// 📊 Inisialisasi Chart Rating
const ctx = document.getElementById('ratingChart').getContext('2d');
const ratingData = {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
        label: '⭐ Rating Rata-rata',
        data: <?= json_encode($chart_ratings) ?>,
        backgroundColor: 'rgba(50, 8, 16, 0.7)',
        borderColor: '#320810',
        borderWidth: 2,
        borderRadius: 6,
    }]
};

new Chart(ctx, {
    type: 'bar',
    data: ratingData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 5,
                ticks: { stepSize: 1 },
                title: { display: true, text: 'Rating (1-5 ⭐)' }
            },
            x: {
                ticks: { maxRotation: 45, minRotation: 45, font: {size: 10} }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y > 0 ? context.parsed.y + ' ⭐' : 'Belum ada rating';
                    }
                }
            }
        }
    }
});

// 🔹 Modal Functions
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

// Tutup modal jika klik di luar
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('active');
    });
});

// 🔹 Edit Produk - Isi Form Modal
function editProduk(data) {
    document.getElementById('edit-id').value = data.id;
    document.getElementById('edit-nama').value = data.nama_produk;
    document.getElementById('edit-deskripsi').value = data.deskripsi;
    document.getElementById('edit-harga').value = data.harga;
    document.getElementById('edit-stok').value = data.stok;
    openModal('modal-edit');
}

// 💡 Auto-hide alert setelah 4 detik
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    }
});
</script>

</body>
</html>