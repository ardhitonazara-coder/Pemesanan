<?php
include 'koneksi.php';

if (isset($_POST['register'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi_password'];
    $no_hp = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    
    // Validasi
    if ($password !== $konfirmasi) {
        echo "<script>alert('❌ Password dan konfirmasi password tidak sama!');</script>";
    } else {
        // Cek email sudah ada atau belum
        $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");
        if (mysqli_num_rows($cek) > 0) {
            echo "<script>alert('❌ Email sudah terdaftar!');</script>";
        } else {
            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (nama_lengkap, email, password, no_hp, alamat, role) 
                      VALUES ('$nama', '$email', '$hashed', '$no_hp', '$alamat', 'user')";
            
            if (mysqli_query($koneksi, $query)) {
                echo "<script>alert('✅ Registrasi berhasil! Silakan login.'); window.location='login.php';</script>";
            } else {
                echo "<script>alert('❌ Gagal registrasi: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar - Mashchickburn</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #ff6b00 0%, #ff8c00 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo h1 {
            color: #ff6b00;
            font-size: 28px;
        }
        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #ff6b00;
        }
        .btn-register {
            width: 100%;
            padding: 14px;
            background: #ff6b00;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-register:hover {
            background: #e65c00;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        .login-link a {
            color: #ff6b00;
            text-decoration: none;
            font-weight: bold;
        }
        .icon {
            text-align: center;
            font-size: 40px;
            margin-bottom: 10px;
        }
        .row {
            display: flex;
            gap: 10px;
        }
        .row .form-group {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="icon">🍗</div>
        <div class="logo">
            <h1>Mashchickburn</h1>
            <p>Buat akun untuk memesan</p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>👤 Nama Lengkap</label>
                <input type="text" name="nama" required placeholder="Masukkan nama lengkap">
            </div>
            
            <div class="form-group">
                <label>📧 Email</label>
                <input type="email" name="email" required placeholder="contoh@email.com">
            </div>
            
            <div class="row">
                <div class="form-group">
                    <label>📱 No. HP</label>
                    <input type="text" name="no_hp" required placeholder="08xxxxxxxxxx">
                </div>
            </div>
            
            <div class="form-group">
                <label>🏠 Alamat</label>
                <textarea name="alamat" required placeholder="Alamat lengkap untuk pengiriman"></textarea>
            </div>
            
            <div class="form-group">
                <label>🔒 Password</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter">
            </div>
            
            <div class="form-group">
                <label>🔒 Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" required placeholder="Ulangi password">
            </div>
            
            <button type="submit" name="register" class="btn-register">
                ✅ Daftar Sekarang
            </button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</body>
</html>