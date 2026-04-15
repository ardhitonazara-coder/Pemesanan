<?php
include 'koneksi.php';

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];
    
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");
    
    if (mysqli_num_rows($query) == 1) {
        $data = mysqli_fetch_assoc($query);
        
        // Verifikasi password
        if (password_verify($password, $data['password'])) {
            $_SESSION['user_id'] = $data['id'];
            $_SESSION['nama'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];
            $_SESSION['email'] = $data['email'];
            
            // Redirect berdasarkan role
            if ($data['role'] == 'admin') {
                echo "<script>alert('✅ Selamat datang, Admin Mashchickburn!'); window.location='admin_order.php';</script>";
            } else {
                echo "<script>alert('✅ Selamat datang di Mashchickburn!'); window.location='index.php';</script>";
            }
        } else {
            echo "<script>alert('❌ Password salah!');</script>";
        }
    } else {
        echo "<script>alert('❌ Email tidak terdaftar!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Mashchickburn</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #320810 0%, #6b1220 50%, #ff6b35 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #320810, #ff6b35, #320810);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 50%;
            box-shadow: 0 5px 20px rgba(50, 8, 16, 0.2);
        }
        
        .logo h1 {
            color: #320810;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #320810;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper span {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #320810;
            box-shadow: 0 0 0 4px rgba(50, 8, 16, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #320810 0%, #4a0f18 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 5px 15px rgba(50, 8, 16, 0.3);
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #4a0f18 0%, #6b1220 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(50, 8, 16, 0.4);
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 14px;
        }
        
        .register-link a {
            color: #320810;
            text-decoration: none;
            font-weight: bold;
        }
        
        .register-link a:hover {
            text-decoration: underline;
            color: #ff6b35;
        }
        
        .features {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        .feature {
            text-align: center;
            color: #666;
            font-size: 13px;
        }
        
        .feature span {
            display: block;
            font-size: 24px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="assets/chaos.png" alt="Mashchickburn Logo">
            <h1>Mashchickburn</h1>
            <p>Silakan login untuk melanjutkan pesanan Anda</p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>📧 Email Address</label>
                <div class="input-wrapper">
                    <span>📧</span>
                    <input type="email" name="email" required placeholder="contoh@email.com">
                </div>
            </div>
            
            <div class="form-group">
                <label>🔒 Password</label>
                <div class="input-wrapper">
                    <span>🔒</span>
                    <input type="password" name="password" required placeholder="Masukkan password Anda">
                </div>
            </div>
            
            <button type="submit" name="login" class="btn-login">
                🔐 Login ke Akun Anda
            </button>
        </form>
        
        <div class="features">
            <div class="feature">
                <span>🍗</span>
                Menu Lezat
            </div>
            <div class="feature">
                <span>⚡</span>
                Pesan Cepat
            </div>
            <div class="feature">
                <span>🚚</span>
                Antar Sampai Rumah
            </div>
        </div>
        
        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar Sekarang</a>
        </div>
    </div>
</body>
</html>