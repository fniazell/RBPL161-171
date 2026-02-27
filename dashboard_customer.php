<?php
require_once 'includes/config.php';
requireRole('customer');
require_once 'includes/header.php';

$db = getDB();
$uid = $_SESSION['user_id'];

$r = $db->query("SELECT COUNT(*) as c FROM dokumen WHERE customer_id = $uid"); 
$totalDok = $r->fetch_assoc()['c'];

$r = $db->query("SELECT COUNT(*) as c FROM dokumen WHERE customer_id = $uid AND status_verifikasi_forwarder = 'Pending'");
$menunggu = $r->fetch_assoc()['c'];

$r = $db->query("SELECT COUNT(*) as c FROM dokumen WHERE customer_id = $uid AND status_bl_final = 'Terbit'");
$blFinal = $r->fetch_assoc()['c'];

$stmt = $db->prepare("SELECT d.ref_id, d.jenis_dokumen, d.tipe_kiriman, d.status_verifikasi_forwarder, d.status_peb_pib, d.status_bl_final, d.uploaded_at 
    FROM dokumen d WHERE d.customer_id = ? ORDER BY d.uploaded_at DESC LIMIT 10");
$stmt->bind_param("i", $uid);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Customer — Sistem Logistik</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Dashboard Customer</h1>
            <p class="page-subtitle">Selamat datang, <?= htmlspecialchars($current_user['nama_lengkap']) ?> — <?= date('d F Y') ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Dokumen</div>
                <div class="stat-value"><?= $totalDok ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Menunggu Verifikasi</div>
                <div class="stat-value" style="color:var(--warning)"><?= $menunggu ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Bill of Lading Final</div>
                <div class="stat-value" style="color:var(--success)"><?= $blFinal ?></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-title">Aktivitas Terakhir</span>
                <a href="customer_clearance.php" class="btn btn-outline btn-sm">Lihat Semua</a>
            </div>
            <div class="table-container">
                <table id="recentTable">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Jenis Dokumen</th>
                            <th>Tipe</th>
                            <th>Verifikasi</th>
                            <th>PEB/PIB</th>
                            <th>BL Final</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent)): ?>
                        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:32px;">Belum ada dokumen yang diunggah</td></tr>
                        <?php else: foreach ($recent as $row): ?>
                        <tr>
                            <td class="font-mono" style="font-size:12px;"><?= htmlspecialchars($row['ref_id']) ?></td>
                            <td><?= htmlspecialchars($row['jenis_dokumen']) ?></td>
                            <td><span class="badge badge-info"><?= $row['tipe_kiriman'] ?></span></td>
                            <td>
                                <?php
                                $v = $row['status_verifikasi_forwarder'];
                                $cls = ['Pending'=>'badge-pending','Valid'=>'badge-success','Approved'=>'badge-success','Perlu Perbaikan'=>'badge-warning','Hold'=>'badge-danger'][$v] ?? 'badge-muted';
                                echo "<span class='badge $cls'>$v</span>";
                                ?>
                            </td>
                            <td>
                                <?php
                                $p = $row['status_peb_pib'];
                                $cls = ['Approved'=>'badge-success','Belum Diajukan'=>'badge-muted','Menunggu Persetujuan'=>'badge-warning','Ditolak'=>'badge-danger'][$p] ?? 'badge-info';
                                echo "<span class='badge $cls'>$p</span>";
                                ?>
                            </td>
                            <td>
                                <?php
                                $b = $row['status_bl_final'];
                                $cls = ['Terbit'=>'badge-success','Belum Terbit'=>'badge-muted','Ditolak'=>'badge-danger','Diproses'=>'badge-warning'][$b] ?? 'badge-muted';
                                echo "<span class='badge $cls'>$b</span>";
                                ?>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($row['uploaded_at'])) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="quick-actions">
            <a href="customer_upload.php" class="btn btn-primary">+ Upload Dokumen Baru</a>
            <a href="customer_tracking.php" class="btn btn-secondary">🔍 Lacak Pengiriman</a>
        </div>
    </main>
</div>
<script src="js/main.js"></script>
</body>
</html>
