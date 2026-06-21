<?php
// api/register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');
$tahun_aktif = date('Y');

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = mysqli_connect("localhost", "root", "", "tourify");
    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi kecocokan password
    if ($password !== $confirm_password) {
        $error_msg = "Konfirmasi kata sandi tidak cocok!";
    } else {
        // Cek apakah username/email sudah terdaftar
        $cek_user = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
        if (mysqli_num_rows($cek_user) > 0) {
            $error_msg = "Username atau Email sudah digunakan!";
        } else {
            // Enkripsi password demi keamanan akun (Menggunakan PASSWORD_DEFAULT)
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Query input disesuaikan dengan struktur tabel baru
            $query_input = "INSERT INTO users (nama_lengkap, username, email, password) VALUES ('$nama_lengkap', '$username', '$email', '$password_hashed')";
            
            if (mysqli_query($conn, $query_input)) {
                $success_msg = "Akun Tourify berhasil dibuat! Mengalihkan ke halaman masuk...";
                
                // Mengalihkan secara otomatis ke login.php dalam waktu 2 detik
                header("refresh:2;url=login.php");
            } else {
                $error_msg = "Gagal mendaftar, coba lagi nanti. ID Error: " . mysqli_error($conn);
            }
        }
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru | Tourify</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --bg-light: #fff7ed;        
            --white: #ffffff;
            --primary: #ff7a00;         
            --text-dark: #0f172a;       
            --text-muted: #64748b;        
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h3, .brand-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .register-container {
            max-width: 480px;
            width: 100%;
            margin: auto;
            padding: 20px;
        }

        .register-card {
            background: var(--white);
            border: 1px solid rgba(255, 122, 0, 0.08);
            border-radius: 28px;
            box-shadow: 0 20px 40px rgba(255, 122, 0, 0.04);
            padding: 40px 35px;
        }

        .nav-brand-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-dark) !important;
        }
        .brand-title {
            font-size: 1.75rem;
            line-height: 1;
            letter-spacing: -0.8px;
        }
        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #ff7a00, #ff923a, #fdba74);
            color: var(--white);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 18px rgba(255, 122, 0, 0.35);
            font-size: 1.1rem;
        }

        .form-control {
            border: 1px solid #e2e8f0;
            padding: 11px 16px;
            border-radius: 12px;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.15);
        }

        .input-group-text {
            background: transparent;
            border-color: #e2e8f0;
            border-radius: 12px;
            color: var(--text-muted);
        }

        .btn-orange-vivid {
            background: linear-gradient(135deg, var(--primary), #ffa200);
            border: none; color: white; font-weight: 700;
            padding: 14px; box-shadow: 0 8px 24px rgba(255, 122, 0, 0.25);
            border-radius: 100px;
        }
        .btn-orange-vivid:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(255, 122, 0, 0.4);
            color: white;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="text-center mb-4">
        <a class="nav-brand-box" href="../index.php">
            <div class="logo-icon"><i class="fas fa-globe-asia"></i></div>
            <span class="brand-title">Tour<span style="color: var(--primary);">ify</span></span>
        </a>
    </div>

    <div class="register-card">
        <div class="text-center mb-4">
            <h3 class="text-dark mb-1">Mulai Petualanganmu!</h3>
            <p class="text-muted small">Buat akun untuk kemudahan penjelajahan di Tourify.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger border-0 small rounded-3 mb-4" role="alert" style="background-color: #fef2f2; color: #dc2626;">
                <?= $error_msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success border-0 small rounded-3 mb-4" role="alert" style="background-color: #f0fdf4; color: #16a34a;">
                <?= $success_msg; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Nama Lengkap</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Buat username unik" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Alamat Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="contoh@domain.com" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Buat kata sandi aman" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">Konfirmasi Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi kata sandi" required>
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-orange-vivid">Daftar Akun</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="small text-muted mb-0">Sudah punya akun Tourify? <br>
                <a href="login.php" class="fw-bold text-decoration-none" style="color: var(--primary);">Masuk ke Akun</a>
            </p>
        </div>
    </div>
    
    <div class="text-center mt-4 opacity-50 small">
        <p>&copy; <?= $tahun_aktif; ?> Tourify. Hak Cipta Dilindungi.</p>
    </div>
</div>
</body>
</html>