<?php
include 'koneksi.php';

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Generate CSRF Token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fungsi cek login admin
if (!function_exists('cek_login')) {
    function cek_login() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo "<script>alert('⚠️ Silakan login sebagai admin!'); window.location='login.php';</script>";
            exit;
        }
    }
}
cek_login();

// Fungsi helper untuk flash message
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================
// 🔐 PROSES: HAPUS PESANAN
// ============================================
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    // Validasi CSRF untuk GET request
    if (!isset($_GET['token']) || $_GET['token'] !== $_SESSION['csrf_token']) {
        set_flash('error', '❌ Token keamanan tidak valid!');
        header("Location: admin_order.php");
        exit;
    }
    
    $id = intval($_GET['hapus']);
    
    // Prepared Statement untuk DELETE
    $stmt = mysqli_prepare($koneksi, "DELETE FROM orders WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', '✅ Pesanan #' . $id . ' berhasil dihapus!');
    } else {
        error_log("Gagal hapus order #$id: " . mysqli_error($koneksi));
        set_flash('error', '❌ Gagal menghapus pesanan.');
    }
    mysqli_stmt_close($stmt);
    
    header("Location: admin_order.php");
    exit;
}

// ============================================
// 🔐 PROSES: UPDATE STATUS (status_pesanan)
// ============================================
if (isset($_POST['update_status'])) {
    // Validasi CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        set_flash('error', '❌ Token keamanan tidak valid!');
        header("Location: admin_order.php");
        exit;
    }
    
    // Validasi input
    $id = filter_input(INPUT_POST, 'id_order', FILTER_VALIDATE_INT);
    $status_baru = trim($_POST['status_baru'] ?? '');
    
    // Whitelist status yang diizinkan (SESUAIKAN dengan value di database Anda)
    $status_valid = ['Sedang Diproses', 'Selesai', 'Dibatalkan', 'pending', 'dikirim'];
    
    if (!$id || !in_array($status_baru, $status_valid)) {
        set_flash('error', '❌ Data tidak valid!');
        header("Location: admin_order.php");
        exit;
    }
    
    // Prepared Statement - PERHATIKAN: status_pesanan
    $stmt = mysqli_prepare($koneksi, "UPDATE orders SET status_pesanan = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $status_baru, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', '✅ Status pesanan #' . $id . ' berhasil diupdate!');
    } else {
        error_log("Gagal update order #$id: " . mysqli_error($koneksi));
        set_flash('error', '❌ Gagal mengupdate status.');
    }
    mysqli_stmt_close($stmt);
    
    header("Location: admin_order.php");
    exit;
}

// ============================================
// 📊 QUERY DATA PESANAN (dengan alias status)
// ============================================
$query = mysqli_query($koneksi, "SELECT orders.*, 
                                  users.nama_lengkap as nama_user,
                                  orders.status_pesanan as status
                                  FROM orders 
                                  LEFT JOIN users ON orders.user_id = users.id 
                                  ORDER BY orders.id DESC");

if (!$query) {
    error_log("Query Error: " . mysqli_error($koneksi));
    die("❌ Terjadi kesalahan saat mengambil data.");
}

$total = mysqli_num_rows($query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Pesanan | Mashchickburn</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #320810 0%, #4a0f18 50%, #6b1220 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            border-top: 5px solid #320810;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        h1 {
            color: #320810;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .nav a {
            padding: 10px 20px;
            background: linear-gradient(135deg, #320810 0%, #4a0f18 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .nav a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(50, 8, 16, 0.4);
            background: linear-gradient(135deg, #4a0f18 0%, #6b1220 100%);
        }
        
        .stats-bar {
            background: linear-gradient(135deg, #320810 0%, #4a0f18 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .stats-bar strong {
            font-size: 24px;
        }
        
        /* Flash Message Styles */
        .flash-message {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .flash-success {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .flash-error {
            background: linear-gradient(135deg, #f44336, #da190b);
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        thead {
            background: linear-gradient(135deg, #320810 0%, #4a0f18 100%);
            color: white;
        }
        
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        tbody tr:hover {
            background: #fff9f9;
            transform: scale(1.01);
            transition: all 0.2s;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #da190b 100%);
            color: white;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
        }
        
        .status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-Selesai {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .status-Dibatalkan {
            background: linear-gradient(135deg, #f44336, #da190b);
            color: white;
        }
        
        .status-Sedang_Diproses,
        .status-pending,
        .status-dikirim {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #333;
        }
        
        select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 2px solid #320810;
            font-size: 12px;
            background: white;
            cursor: pointer;
            outline: none;
        }
        
        select:focus {
            border-color: #4a0f18;
            box-shadow: 0 0 0 3px rgba(50, 8, 16, 0.1);
        }
        
        .action {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            background: #fafafa;
            border-radius: 10px;
            border: 2px dashed #320810;
        }
        
        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 15px;
            display: block;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: rgba(255,255,255,0.8);
            font-size: 13px;
            padding: 15px;
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .stats-bar {
                flex-direction: column;
                text-align: center;
            }
            th, td {
                padding: 10px 8px;
                font-size: 13px;
            }
            .action {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="page-header">
        <h1>📋 Manajemen Pesanan</h1>
        <div class="nav">
            <a href="admin_laporan.php">📈 Laporan</a>
            <a href="index.php">🏠 Toko</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
    
    <!-- Flash Message -->
    <?php 
    $flash = get_flash();
    if ($flash): 
    ?>
        <div class="flash-message flash-<?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats Bar -->
    <div class="stats-bar">
        <span>📦 Total Pesanan:</span>
        <strong><?= $total ?></strong>
    </div>
    
    <!-- Content -->
    <?php if($total == 0): ?>
        <div class="empty-state">
            <span class="icon">🎉</span>
            <h3 style="color: #320810; margin-bottom: 10px;">Belum ada pesanan</h3>
            <p>Tunggu pesanan dari pelanggan ya! 🍗</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelanggan</th>
                    <th>Telepon</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><strong>#<?= (int)$row['id'] ?></strong></td>
                    <td><?= htmlspecialchars($row['nama_user'] ?? $row['nama_pelanggan'] ?? 'N/A') ?></td>
                    <td>📱 <?= htmlspecialchars($row['no_hp_penerima'] ?? '-') ?></td>
                    <td>
                        <span class="status status-<?= htmlspecialchars(str_replace(' ', '_', $row['status'] ?? '')) ?>">
                            <?= htmlspecialchars($row['status'] ?? '-') ?>
                        </span>
                    </td>
                    <td class="action" style="justify-content: center;">
                        <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id_order" value="<?= (int)$row['id'] ?>">
                            
                            <select name="status_baru">
                                <option value="Sedang Diproses" <?= ($row['status']??'')==='Sedang Diproses'?'selected':'' ?>>⏳ Proses</option>
                                <option value="Selesai" <?= ($row['status']??'')==='Selesai'?'selected':'' ?>>✅ Selesai</option>
                                <option value="Dibatalkan" <?= ($row['status']??'')==='Dibatalkan'?'selected':'' ?>>❌ Batal</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-success" title="Update">💾</button>
                        </form>
                        
                        <!-- Tombol Hapus dengan CSRF Token -->
                        <a href="?hapus=<?= (int)$row['id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('⚠️ Yakin ingin menghapus pesanan #<?= (int)$row['id'] ?>?\n\nTindakan ini tidak dapat dibatalkan!')" 
                           title="Hapus">
                            🗑️
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Footer -->
<div class="footer">
    🍗 Mashchickburn Admin Panel | <?= date('d/m/Y H:i:s') ?>
</div>

</body>
</html>