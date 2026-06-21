<?php
// admin_logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus hanya data session admin (tidak mengganggu session user biasa jika ada)
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_nama']);
unset($_SESSION['admin_role']);
unset($_SESSION['admin_destinasi']);

header("Location: admin_login.php");
exit();
?>