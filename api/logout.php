<?php
if (session_status() === PHP_SESSION_NONE) {
    // Fix session untuk Vercel serverless
ini_set('session.save_path', '/tmp');
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
session_start();
}
// Hapus semua session
session_unset();
session_destroy();
// Redirect ke login
header("Location: /login.php");
exit();
?>