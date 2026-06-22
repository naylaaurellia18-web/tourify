<?php
// ============================================================
//  SESSION HANDLER BERBASIS DATABASE — Tourify
//  Solusi untuk Vercel serverless: setiap function container
//  punya /tmp terpisah, sehingga file session tidak bisa
//  di-share antar request. Dengan menyimpan session ke TiDB,
//  semua container membaca/menulis ke tempat yang sama.
//
//  CARA PAKAI: include __DIR__ . '/session_db.php';
//  Taruh SEBELUM session_start() di setiap file PHP.
//  Jangan panggil session_start() lagi setelah ini karena
//  file ini sudah memanggil session_start() di dalam.
// ============================================================

// Pengaturan session cookie agar bekerja di Vercel HTTPS
ini_set('session.save_path',      '/tmp');
ini_set('session.cookie_secure',  '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_path',    '/');
ini_set('session.cookie_lifetime', '86400');   // 24 jam
ini_set('session.gc_maxlifetime',  '86400');

// ---- Custom session handler yang baca/tulis ke TiDB ----
class TiDBSessionHandler implements SessionHandlerInterface {
    private $conn;
    private $table = 'php_sessions';

    public function open($savePath, $sessionName): bool {
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        $ok = mysqli_real_connect(
            $this->conn,
            "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
            "3DA4d4bPMVCSuDy.root",
            "mRSgOTH6qk79AieJ",
            "tourify-db",
            4000,
            NULL,
            MYSQLI_CLIENT_SSL
        );
        if (!$ok) return false;
        mysqli_set_charset($this->conn, 'utf8mb4');

        // Pastikan tabel sessions ada (buat sekali, aman dijalankan berulang)
        mysqli_query($this->conn, "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id`      VARCHAR(128) NOT NULL PRIMARY KEY,
            `data`    LONGTEXT     NOT NULL,
            `expires` BIGINT       NOT NULL,
            INDEX idx_expires (`expires`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        return true;
    }

    public function close(): bool {
        if ($this->conn) mysqli_close($this->conn);
        return true;
    }

    public function read($id): string|false {
        if (!$this->conn) return '';
        $id = mysqli_real_escape_string($this->conn, $id);
        $now = time();
        $r = mysqli_query($this->conn, "SELECT data FROM `{$this->table}` WHERE id='$id' AND expires > $now LIMIT 1");
        if ($r && mysqli_num_rows($r) > 0) {
            $row = mysqli_fetch_assoc($r);
            return $row['data'];
        }
        return '';
    }

    public function write($id, $data): bool {
        if (!$this->conn) return false;
        $id      = mysqli_real_escape_string($this->conn, $id);
        $data    = mysqli_real_escape_string($this->conn, $data);
        $expires = time() + (int)ini_get('session.gc_maxlifetime');
        $r = mysqli_query($this->conn,
            "REPLACE INTO `{$this->table}` (id, data, expires) VALUES ('$id', '$data', $expires)"
        );
        return $r !== false;
    }

    public function destroy($id): bool {
        if (!$this->conn) return false;
        $id = mysqli_real_escape_string($this->conn, $id);
        mysqli_query($this->conn, "DELETE FROM `{$this->table}` WHERE id='$id'");
        return true;
    }

    public function gc($maxlifetime): int|false {
        if (!$this->conn) return false;
        $now = time();
        mysqli_query($this->conn, "DELETE FROM `{$this->table}` WHERE expires < $now");
        return mysqli_affected_rows($this->conn);
    }
}

// Daftarkan handler dan mulai session
$handler = new TiDBSessionHandler();
session_set_save_handler($handler, true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}