<?php
include 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role']!='admin') {
    echo "<script>alert('Akses ditolak!'); window.location='index.php';</script>"; exit;
}

// 🔥 HAPUS DATA
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM orders WHERE id=$id");
    echo "<script>alert('✅ Data dihapus!'); window.location='admin_laporan.php';</script>";
}

// 🔥 EXPORT EXCEL
if (isset($_GET['export_excel'])) {
    header("Content-type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_".date('Y-m-d').".xls");
}

// 🔥 CEK KOLOM TANGGAL YANG ADA DI DATABASE
$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM orders");
$date_column = 'id'; // default fallback (pasti ada)
while($col = mysqli_fetch_assoc($check_cols)) {
    $field = $col['Field'];
    // Cari kolom yang mengandung kata 'tanggal' atau 'date' atau 'time'
    if (stripos($field, 'tanggal') !== false || 
        stripos($field, 'date') !== false || 
        stripos($field, 'time') !== false ||
        stripos($field, 'created') !== false) {
        $date_column = $field;
        break;
    }
}

// 🔥 FILTER
$start = $_GET['start_date'] ?? date('Y-m-01');
$end = $_GET['end_date'] ?? date('Y-m-d');
$filter_status = $_GET['status_pesanan'] ?? '';

// 🔥 QUERY UTAMA - PAKAI KOLOM YANG SUDAH TERDETEKSI
$sql = "SELECT * FROM orders WHERE DATE($date_column) BETWEEN '$start' AND '$end'";
if ($filter_status) $sql .= " AND status='$filter_status'";
$sql .= " ORDER BY $date_column DESC";

$orders = mysqli_query($koneksi, $sql);

if (!$orders) {
    // Fallback: coba tanpa filter tanggal
    $sql = "SELECT * FROM orders ORDER BY id DESC";
    $orders = mysqli_query($koneksi, $sql);
}

// 🔥 HITUNG TOTAL
$total_pesanan = 0; $total_pendapatan = 0;
while($o = mysqli_fetch_assoc($orders)) {
    $total_pesanan++;
    $total_pendapatan += $o['total_bayar'];
}
mysqli_data_seek($orders, 0);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - Laporan | Mashchickburn</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Arial,sans-serif;background:#f5f5f5}
        .header{background:#320810;color:white;padding:20px 30px}
        .header-content{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center}
        .header a{color:white;margin-left:15px;text-decoration:none}
        .container{max-width:1200px;margin:30px auto;padding:0 20px}
        .cards{display:flex;gap:20px;margin-bottom:20px}
        .card{background:white;padding:20px;border-radius:10px;flex:1;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,.1)}
        .card h3{font-size:28px;color:#320810;margin-bottom:5px}
        .card p{color:#666}
        .filter{background:white;padding:20px;border-radius:10px;margin-bottom:20px;display:flex;gap:15px;flex-wrap:wrap;align-items:end}
        .filter label{display:block;margin-bottom:5px;color:#320810;font-weight:600}
        .filter input,.filter select{padding:10px;border:2px solid #ddd;border-radius:8px}
        .btn{padding:10px 20px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-block;margin:2px}
        .btn-primary{background:#320810;color:white}
        .btn-success{background:#4CAF50;color:white}
        .btn-warning{background:#ffc107;color:#333}
        .btn-danger{background:#f44336;color:white}
        table{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden}
        th{background:#320810;color:white;padding:15px;text-align:left}
        td{padding:12px;border-bottom:1px solid #eee}
        tr:hover{background:#f9f9f9}
        .status{padding:4px 10px;border-radius:15px;font-size:11px;font-weight:600}
        .status-Selesai{background:#4CAF50;color:white}
        .status-Dibatalkan{background:#f44336;color:white}
        .status-Sedang\ Diproses{background:#ffc107;color:#333}
        .debug{background:#fff3cd;padding:10px;border-radius:5px;margin-bottom:20px;color:#856404}
        @media print{.header,.filter,.btn,.debug{display:none}}
    </style>
</head>
<body>
<div class="header">
    <div class="header-content">
        <h1>📈 Laporan Penjualan</h1>
        <div>
            <a href="admin_order.php">📋 Pesanan</a>
            <a href="admin_produk.php">🍗 Produk</a>
            <a href="index.php">🏠 Toko</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
</div>

<div class="container">
    

    <!-- Summary Cards -->
    <div class="cards">
        <div class="card"><h3>Rp <?= number_format($total_pendapatan,0,',','.') ?></h3><p>Total Pendapatan</p></div>
        <div class="card"><h3><?= $total_pesanan ?></h3><p>Total Pesanan</p></div>
        <div class="card"><h3>Rp <?= $total_pesanan>0?number_format($total_pendapatan/$total_pesanan,0,',','.'):0 ?></h3><p>Rata-rata/Order</p></div>
    </div>

    <!-- Filter -->
    <div class="filter">
        <form method="GET" style="display:flex;gap:15px;flex-wrap:wrap">
            <div>
                <label>Tanggal Mulai</label>
                <input type="date" name="start_date" value="<?= $start ?>" required>
            </div>
            <div>
                <label>Tanggal Akhir</label>
                <input type="date" name="end_date" value="<?= $end ?>" required>
            </div>
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="">Semua</option>
                    <option value="Sedang Diproses" <?= $filter_status=='Sedang Diproses'?'selected':'' ?>>Proses</option>
                    <option value="Selesai" <?= $filter_status=='Selesai'?'selected':'' ?>>Selesai</option>
                    <option value="Dibatalkan" <?= $filter_status=='Dibatalkan'?'selected':'' ?>>Batal</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;align-items:end">
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <a href="admin_laporan.php" class="btn btn-warning">🔄 Reset</a>
                <button type="submit" name="export_excel" value="1" class="btn btn-success">📊 Excel</button>
                <button type="button" onclick="window.print()" class="btn btn-primary">🖨️ Cetak</button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <?php if($total_pesanan==0): ?>
        <div style="text-align:center;padding:60px;background:white;border-radius:10px;color:#666">
            <h2>📭 Tidak ada data</h2>
            <p>Tidak ada pesanan pada periode yang dipilih</p>
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row=mysqli_fetch_assoc($orders)): ?>
            <tr>
                <td><strong>#<?= $row['id'] ?></strong></td>
                <td>
                    <?php 
                    // Tampilkan tanggal sesuai kolom yang ada
                    if (isset($row[$date_column]) && !empty($row[$date_column])) {
                        echo date('d/m/Y', strtotime($row[$date_column]));
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td><?= htmlspecialchars($row['nama_penerima'] ?? 'N/A') ?></td>
                <td><strong>Rp <?= number_format($row['total_bayar'],0,',','.') ?></strong></td>
                <td><span class="status status-<?= str_replace(' ','\_',$row['status_pesanan'] ?? '') ?>"><?= $row['status_pesanan'] ?? '-' ?></span></td>
                <td>
                    <a href="admin_order.php?hapus=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus?')">🗑️</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot style="background:#f9f9f9;font-weight:bold">
            <tr>
                <td colspan="3" style="text-align:right">TOTAL:</td>
                <td>Rp <?= number_format($total_pendapatan,0,',','.') ?></td>
                <td colspan="2"><?= $total_pesanan ?> pesanan</td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>
</body>
</html>