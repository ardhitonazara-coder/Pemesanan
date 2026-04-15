<?php
// checkout.php - Halaman Checkout Mashchickburn
include 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Cek keranjang kosong
if (empty($_SESSION['keranjang'])) {
    echo "<script>alert('🛒 Keranjang Anda masih kosong!'); window.location='index.php';</script>";
    exit;
}

// 🔥 Hitung total bayar dari keranjang (dengan qty!)
$total_bayar = 0;
$items_detail = [];

foreach ($_SESSION['keranjang'] as $item) {
    $data = explode("|", $item);
    $harga = floatval($data[2]);
    $qty = intval($data[3]); // 🔥 Ambil qty dari session
    $subtotal = $harga * $qty;
    $total_bayar += $subtotal;
    $items_detail[] = [
        'id' => $data[0],
        'nama' => $data[1],
        'harga' => $harga,
        'qty' => $qty,
        'subtotal' => $subtotal
    ];
}

// Tambah ongkir jika dipilih
$ongkir = 0;
if (isset($_POST['metode_kirim']) && $_POST['metode_kirim'] === 'Diantar') {
    $ongkir = 3000;
    $total_bayar += $ongkir;
}

// 🔥 PROSES PESANAN
if (isset($_POST['proses_pesanan'])) {
    $user_id = intval($_SESSION['user_id']);
    $nama = trim($_POST['nama']);
    $no_hp = trim($_POST['no_hp']);
    $alamat = trim($_POST['alamat']);
    $metode_bayar = trim($_POST['metode_bayar']);
    $metode_kirim = trim($_POST['metode_kirim']);
    
    // Handle upload bukti transfer
    $bukti = '';
    if (isset($_FILES['bukti_tf']) && $_FILES['bukti_tf']['error'] === UPLOAD_ERR_OK) {
        $bukti = time() . '_' . basename($_FILES['bukti_tf']['name']);
        $tmp = $_FILES['bukti_tf']['tmp_name'];
        $folder = "uploads/";
        
        if (!file_exists($folder)) mkdir($folder, 0777, true);
        move_uploaded_file($tmp, $folder . $bukti);
    }

    // 1️⃣ Insert ke tabel orders
    $query_order = mysqli_prepare($koneksi, "INSERT INTO orders (user_id, nama_penerima, no_hp_penerima, alamat_lengkap, metode_pembayaran, metode_pengiriman, bukti_transfer, total_bayar, status_pesanan, tanggal_pesan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Sedang Diproses', NOW())");
    mysqli_stmt_bind_param($query_order, "issssssd", $user_id, $nama, $no_hp, $alamat, $metode_bayar, $metode_kirim, $bukti, $total_bayar);
    
    if (mysqli_stmt_execute($query_order)) {
        $order_id = mysqli_insert_id($koneksi);
        
        // 2️⃣ Loop setiap item di keranjang
        foreach ($_SESSION['keranjang'] as $item) {
            $data = explode("|", $item);
            $prod_id = intval($data[0]);
            $harga = floatval($data[2]);
            $qty = intval($data[3]); // 🔥 Qty dari keranjang
            $subtotal = $harga * $qty;
            
            // ✅ Insert ke order_items dengan qty yang benar
            $stmt_item = mysqli_prepare($koneksi, "INSERT INTO order_items (order_id, product_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_item, "iiidd", $order_id, $prod_id, $qty, $harga, $subtotal);
            mysqli_stmt_execute($stmt_item);
            
            // ✅ Kurangi stok di tabel products
            $stmt_stok = mysqli_prepare($koneksi, "UPDATE products SET stok = stok - ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_stok, "ii", $qty, $prod_id);
            mysqli_stmt_execute($stmt_stok);
            
            // ✅ Update status produk jika stok habis (CEK KOLOM DULU!)
            $cek_kolom_status = mysqli_query($koneksi, "SHOW COLUMNS FROM products LIKE 'status'");
            if (mysqli_num_rows($cek_kolom_status) > 0) {
                mysqli_query($koneksi, "UPDATE products SET status = 'habis' WHERE id = $prod_id AND stok <= 0");
            }
            
            // ✅ Catat log stok (opsional - cek tabel dulu)
            $cek_tabel_log = mysqli_query($koneksi, "SHOW TABLES LIKE 'stock_logs'");
            if (mysqli_num_rows($cek_tabel_log) > 0) {
                mysqli_query($koneksi, "INSERT INTO stock_logs (product_id, perubahan_jumlah, keterangan) VALUES ($prod_id, -$qty, 'Pesanan #$order_id')");
            }
        }

        // 3️⃣ Kosongkan keranjang & redirect
        unset($_SESSION['keranjang']);
        echo "<script>alert('✅ Pesanan di Mashchickburn berhasil!\\nTotal: " . rupiah($total_bayar) . "\\n\\nKami akan segera memproses pesanan Anda.'); window.location='status_pesanan.php';</script>";
        exit; // 🔥 Exit HARUS di paling akhir
    } else {
        echo "<script>alert('❌ Gagal memproses pesanan: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Mashchickburn</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); }
        .header { background: linear-gradient(135deg, #320810 0%, #4a0f18 100%); color: white; padding: 25px; margin: -30px -30px 25px -30px; border-radius: 15px 15px 0 0; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        
        label { display: block; margin: 15px 0 8px 0; color: #320810; font-weight: 600; font-size: 14px; }
        input, textarea, select { width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; transition: border-color 0.3s; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #320810; }
        
        /* Keranjang Preview */
        .cart-preview { background: #f8f9fa; border-radius: 10px; padding: 15px; margin: 20px 0; }
        .cart-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #ddd; font-size: 14px; }
        .cart-item:last-child { border-bottom: none; }
        .cart-item .nama { font-weight: 500; }
        .cart-item .qty { color: #666; }
        .cart-item .subtotal { font-weight: bold; color: #320810; }
        .cart-total { display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 2px solid #320810; font-weight: bold; font-size: 16px; }
        
        /* QRIS Section */
        .qris-section { display: none; background: linear-gradient(135deg, #fff9f9, #fff); border: 3px dashed #320810; border-radius: 12px; padding: 25px; margin: 20px 0; text-align: center; }
        .qris-section.show { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .qris-section h3 { color: #320810; margin-bottom: 15px; font-size: 18px; }
        .qris-img { max-width: 280px; width: 100%; border: 3px solid #320810; border-radius: 12px; margin: 15px auto; display: block; box-shadow: 0 5px 20px rgba(50, 8, 16, 0.2); }
        .qris-info { background: white; padding: 15px; border-radius: 8px; margin-top: 15px; font-size: 13px; color: #555; line-height: 1.6; }
        .qris-info strong { color: #320810; }
        
        .btn { background: linear-gradient(135deg, #320810 0%, #4a0f18 100%); color: white; padding: 14px 20px; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-size: 16px; font-weight: 600; margin-top: 20px; transition: transform 0.2s, box-shadow 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(50, 8, 16, 0.3); }
        
        .total { background: linear-gradient(135deg, #320810, #4a0f18); color: white; padding: 20px; border-radius: 10px; margin: 25px 0; font-size: 20px; font-weight: bold; text-align: center; box-shadow: 0 5px 15px rgba(50, 8, 16, 0.2); }
        .total .label { font-size: 14px; opacity: 0.9; margin-bottom: 5px; }
        .total .amount { font-size: 28px; }
        
        .back-link { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; transition: color 0.3s; }
        .back-link:hover { color: #320810; }
        .form-note { font-size: 12px; color: #666; margin-top: 5px; font-style: italic; }
        
        @media (max-width: 768px) { .container { padding: 20px; } .header h1 { font-size: 24px; } .qris-img { max-width: 100%; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍗 Mashchickburn</h1>
            <p>Lengkapi data untuk menyelesaikan pesanan</p>
        </div>
        
        <!-- 🔥 Preview Keranjang dengan Qty -->
        <div class="cart-preview">
            <h3 style="color:#320810; margin-bottom:10px;">📋 Ringkasan Pesanan</h3>
            <?php foreach ($items_detail as $item): ?>
            <div class="cart-item">
                <span class="nama"><?= htmlspecialchars($item['nama']) ?></span>
                <span class="qty"><?= $item['qty'] ?> × <?= rupiah($item['harga']) ?></span>
                <span class="subtotal"><?= rupiah($item['subtotal']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php if ($ongkir > 0): ?>
            <div class="cart-item">
                <span class="nama">🚚 Ongkos Kirim</span>
                <span></span>
                <span class="subtotal"><?= rupiah($ongkir) ?></span>
            </div>
            <?php endif; ?>
            <div class="cart-total">
                <span>Total Bayar</span>
                <span><?= rupiah($total_bayar) ?></span>
            </div>
        </div>
        
        <div class="total">
            <div class="label">💰 Total Pembayaran</div>
            <div class="amount"><?= rupiah($total_bayar) ?></div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="checkoutForm">
            <label><strong>Nama Penerima:</strong></label>
            <input type="text" name="nama" required placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($_SESSION['nama'] ?? '') ?>">

            <label><strong>No. HP / WhatsApp:</strong></label>
            <input type="text" name="no_hp" required placeholder="08xxxxxxxxxx" pattern="08[0-9]{8,11}">

            <label><strong>Alamat Lengkap:</strong></label>
            <textarea name="alamat" rows="3" required placeholder="Jl. Contoh No. 123, RT/RW, Kelurahan, Kecamatan"></textarea>

            <label><strong>Metode Pembayaran:</strong></label>
            <select name="metode_bayar" id="metode_bayar" required onchange="togglePayment()">
                <option value="">-- Pilih Pembayaran --</option>
                <option value="Transfer Bank">💳 Transfer Bank</option>
                <option value="QRIS">📱 QRIS (Scan & Pay)</option>
                <option value="COD">💵 COD (Bayar di Tempat)</option>
            </select>
            
            <!-- 🔥 QRIS SECTION -->
            <div id="qrisSection" class="qris-section">
                <h3>📱 Scan QRIS untuk Pembayaran</h3>
                <img src="assets/qris.png" alt="QRIS Mashchickburn" class="qris-img" onerror="this.src='https://via.placeholder.com/280x280?text=QRIS+Mashchickburn'">
                <div class="qris-info">
                    <strong>Cara Pembayaran:</strong><br>
                    1. Buka aplikasi e-wallet (GoPay, OVO, DANA, ShopeePay, dll)<br>
                    2. Pilih menu Scan QRIS<br>
                    3. Scan QR code di atas<br>
                    4. Masukkan nominal: <strong><?= rupiah($total_bayar) ?></strong><br>
                    5. Konfirmasi pembayaran<br>
                    6. Upload screenshot bukti pembayaran di bawah
                </div>
            </div>

            <label><strong>Metode Pengiriman:</strong></label>
            <select name="metode_kirim" id="metode_kirim" required onchange="updateTotal()">
                <option value="">-- Pilih Pengiriman --</option>
                <option value="Diantar">🚚 Diantar (+ Rp 3.000)</option>
                <option value="Diambil Sendiri">🏪 Diambil Sendiri (Pickup)</option>
            </select>

            <label><strong>Upload Bukti Transfer/Pembayaran:</strong></label>
            <input type="file" name="bukti_tf" id="bukti_tf" accept="image/*" required>
            <div class="form-note" id="buktiNote">Upload screenshot/foto bukti transfer</div>

            <button type="submit" name="proses_pesanan" class="btn">
                ✅ Konfirmasi Pesanan - <?= rupiah($total_bayar) ?>
            </button>
            <a href="index.php" class="back-link">← Kembali Belanja</a>
        </form>
    </div>

    <script>
        // Toggle QRIS section
        function togglePayment() {
            const metode = document.getElementById('metode_bayar').value;
            const qrisSection = document.getElementById('qrisSection');
            const buktiInput = document.getElementById('bukti_tf');
            const buktiNote = document.getElementById('buktiNote');
            
            if (metode === 'QRIS') {
                qrisSection.classList.add('show');
                buktiNote.innerHTML = '📱 Upload screenshot pembayaran QRIS';
                buktiInput.required = true;
            } else if (metode === 'COD') {
                qrisSection.classList.remove('show');
                buktiNote.innerHTML = '⚠️ Untuk COD, bukti upload tidak diperlukan (opsional)';
                buktiInput.required = false;
            } else {
                qrisSection.classList.remove('show');
                buktiNote.innerHTML = 'Upload screenshot/foto bukti transfer';
                buktiInput.required = true;
            }
        }
        
        // Update total jika ongkir berubah (opsional: bisa pakai AJAX untuk real-time)
        function updateTotal() {
            // Jika ingin update real-time, bisa kirim AJAX ke server
            // Untuk sekarang, total dihitung di server saat submit
        }
        
        // Validasi form sebelum submit
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const metode = document.getElementById('metode_bayar').value;
            const pengiriman = document.getElementById('metode_kirim').value;
            
            if (!metode) {
                alert('⚠️ Silakan pilih metode pembayaran!');
                e.preventDefault();
                return false;
            }
            
            if (!pengiriman) {
                alert('⚠️ Silakan pilih metode pengiriman!');
                e.preventDefault();
                return false;
            }
            
            if ((metode === 'QRIS' || metode === 'Transfer Bank') && document.getElementById('bukti_tf').files.length === 0) {
                alert('⚠️ Silakan upload bukti pembayaran!');
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>