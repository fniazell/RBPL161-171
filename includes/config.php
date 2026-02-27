<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'logistik_db');
define('BASE_URL', 'http://localhost/logistik');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Koneksi database gagal: ' . $conn->connect_error]));
        }
    }
    return $conn;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function generateRefId($type = 'EXP') {
    $year = date('Y');
    $db = getDB();
    $result = $db->query("SELECT COUNT(*) as cnt FROM dokumen WHERE ref_id LIKE '{$type}-{$year}-%'");
    $row = $result->fetch_assoc();
    $num = str_pad($row['cnt'] + 1, 3, '0', STR_PAD_LEFT);
    return "{$type}-{$year}-{$num}";
}

function logTracking($dokumen_id, $status_label, $keterangan = '', $actor_id = null, $actor_role = 'system') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO tracking_log (dokumen_id, status_label, keterangan, actor_id, actor_role) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issss", $dokumen_id, $status_label, $keterangan, $actor_id, $actor_role);
    $stmt->execute();
}
?>
