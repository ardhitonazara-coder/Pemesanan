<?php
include 'koneksi.php';
$user_id = $_SESSION['user_id'];
$query = mysqli_query($koneksi, "SELECT * FROM orders WHERE user_id='$user_id' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Status Pesanan - Mashchickburn</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #320810; color: white; padding: 20px; border-radius: 8px; }
        .nav a { color: white; text-decoration: none; margin-right: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #320810; padding: 12px; text-align: left; }
        th { background: #320810; color: white; }
        .status-menunggu { color: #320810; font-weight: bold; }
        .status-proses { color: #2196F3; font-weight: bold; }
        .status-selesai { color: #4CAF50; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍗 Mashchickburn</h1>
        <div class="nav">
            <a href="index.php">🏠 Belanja Lagi</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>

    <h2>Status Pesanan Anda</h2>
    
    <?php if (mysqli_num_rows($query) == 0): ?>
        <p style="font-weight: 900; font-size: 18px; color: #320810;">
    Belum ada pesanan. <a href="index.php" style="font-weight: 800; color: #320810; text-decoration: none; border-bottom: 5px solid #009900;">Mulai belanja</a>
</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Pengiriman</th>
                <th>Status</th>
                <th>Bukti</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($query)) : ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_pesan'])) ?></td>
                <td><?= rupiah($row['total_bayar']) ?></td>
                <td><?= $row['metode_pengiriman'] ?></td>
                <td>
                    <?php 
                    $class = '';
                    if ($row['status_pesanan'] == 'Menunggu Konfirmasi') {
                        $class = 'status-menunggu';
                        $icon = '⏳';
                    } elseif ($row['status_pesanan'] == 'Sedang Diproses') {
                        $class = 'status-proses';
                        $icon = '👨‍🍳';
                    } else {
                        $class = 'status-selesai';
                        $icon = '✅';
                    }
                    echo "<span class='$class'>$icon " . $row['status_pesanan'] . "</span>";
                    ?>
                </td>
                <td><a href="uploads/<?= $row['bukti_transfer'] ?>" target="_blank">Lihat</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</body>
</html>