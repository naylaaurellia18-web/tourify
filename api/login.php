<?php
// login.php
include __DIR__ . '/session_db.php';
date_default_timezone_set('Asia/Jakarta');
$tahun_aktif = date('Y');

// JIKA SUDAH LOGIN SEBAGAI ADMIN: Alihkan otomatis ke panel admin
if (!empty($_SESSION['admin_id'])) {
    header("Location: /api/admin.php");
    exit;
}

// JIKA USER SUDAH LOGIN: Alihkan otomatis ke dashboard
if ((isset($_SESSION['user']) || isset($_SESSION['username'])) && isset($_SESSION['login_user'])) {
    header("Location: /api/dashboard.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // SINKRONISASI DATABASE: Pastikan nama database Anda benar ("tourify")
    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    mysqli_real_connect($conn, "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
        "3DA4d4bPMVCSuDy.root", "mRSgOTH6qk79AieJ", "tourify-db", 4000, NULL, MYSQLI_CLIENT_SSL);
    mysqli_set_charset($conn, "utf8mb4");

    $username_or_email = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);

    // --- 1. Cek dulu apakah username ini terdaftar sebagai ADMIN ---
    // (Admin login pakai username saja, tidak lewat email, dan tidak butuh tabel users)
    $query_admin  = "SELECT * FROM admin WHERE username = '$username_or_email' LIMIT 1";
    $result_admin = mysqli_query($conn, $query_admin);

    if ($result_admin && mysqli_num_rows($result_admin) > 0) {
        $admin = mysqli_fetch_assoc($result_admin);

        if (password_verify($password, $admin['password'])) {
            // Set session khusus admin
            $_SESSION['admin_id']        = (int)$admin['id_admin'];
            $_SESSION['admin_username']  = $admin['username'];
            $_SESSION['admin_nama']      = $admin['nama_lengkap'];
            $_SESSION['admin_role']      = $admin['role']; // 'super' atau 'destinasi'
            $_SESSION['admin_destinasi'] = $admin['id_destinasi'] ? (int)$admin['id_destinasi'] : null;

            mysqli_close($conn);
            header("Location: /api/admin.php");
            exit;
        } else {
            $error_msg = "Kata sandi yang kamu masukkan salah!";
            mysqli_close($conn);
        }
    } else {
        // --- 2. Bukan admin, lanjut cek sebagai USER biasa (alur asli) ---
        // Cari user berdasarkan username atau email
        $query = "SELECT * FROM users WHERE username = '$username_or_email' OR email = '$username_or_email' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);

            // Cek kecocokan password dengan hash di database
            if (password_verify($password, $row['password'])) {
                // SET SESSION UNTUK DAHSBOARD
                $_SESSION['id_user']      = $row['id'];
                $_SESSION['username']     = $row['username']; 
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'] ?? $row['nama'] ?? $row['username'];
                $_SESSION['login_user']   = true; // Wajib diset TRUE agar lolos validasi dashboard.php

                // Redirect ke dashboard.php di folder utama
                header("Location: /api/dashboard.php");
                exit;
            } else {
                $error_msg = "Kata sandi yang kamu masukkan salah!";
            }
        } else {
            $error_msg = "Username atau Email tidak terdaftar dalam sistem.";
        }
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Tourify | Akses Layanan Wisata</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --bg-light: #f8fafc;        
            --white: #ffffff;
            --primary: #f37021;         
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

        .login-container {
            max-width: 450px;
            width: 100%;
            margin: auto;
            padding: 20px;
        }

        .login-card {
            background: var(--white);
            border: 1px solid rgba(243, 112, 33, 0.08);
            border-radius: 28px;
            box-shadow: 0 20px 40px rgba(243, 112, 33, 0.04);
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
            background: linear-gradient(135deg, #f37021, #ff8c42);
            color: var(--white);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 18px rgba(243, 112, 33, 0.25);
            font-size: 1.1rem;
        }

        .form-control {
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(243, 112, 33, 0.15);
        }

        .input-group-text {
            background: transparent;
            border-color: #e2e8f0;
            border-radius: 12px;
            color: var(--text-muted);
        }

        .btn-orange-vivid {
            background: var(--primary);
            border: none; color: white; font-weight: 700;
            padding: 14px; box-shadow: 0 8px 24px rgba(243, 112, 33, 0.25);
            border-radius: 100px;
        }
        .btn-orange-vivid:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(243, 112, 33, 0.4);
            color: white;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="text-center mb-4">
        <a class="nav-brand-box" href="/">
            <div class="logo-icon"><i class="bi bi-compass-fill"></i></div>
            <span class="brand-title">Tour<span style="color: var(--primary);">ify</span></span>
        </a>
    </div>

    <div class="login-card">
        <div class="text-center mb-4">
            <h3 class="text-dark mb-1">Selamat Datang Kembali!</h3>
            <p class="text-muted small">Yuk, masuk untuk melanjutkan penjelajahanmu di Tourify.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger border-0 small rounded-3 mb-4" role="alert" style="background-color: #fef2f2; color: #dc2626;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="/api/login.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Username atau Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username/email" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">Kata Sandi</label>
                <div class="input-group" id="show_hide_password">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan kata sandi" required>
                    <span class="input-group-text" id="togglePassword" style="cursor: pointer;"><i class="bi bi-eye-slash"></i></span>
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-orange-vivid">Masuk Sekarang</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="small text-muted mb-0">Belum punya akun Tourify? <br>
                <a href="/api/register.php" class="fw-bold text-decoration-none" style="color: var(--primary);">Daftar Akun Baru</a>
            </p>
        </div>
    </div>
    
    <div class="text-center mt-4 opacity-50 small">
        <p>&copy; <?= $tahun_aktif; ?> Tourify. Hak Cipta Dilindungi.</p>
    </div>
</div>

<script>
// Fungsi toggle tampil/sembunyikan password
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.querySelector('#show_hide_password input');
    const icon = this.querySelector('i');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    }
});
</script>
</body>
</html>