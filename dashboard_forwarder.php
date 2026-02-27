<?php
require_once 'includes/config.php';
requireRole('forwarder');
require_once 'includes/header.php';

$db = getDB();
$uid = $_SESSION['user_id'];

$r = $db->query("SELECT COUNT(*) as c FROM dokumen WHERE status_verifikasi_forwarder = 'Pending'");
$dokMenunggu = $r->fetch_assoc()['c'];

$r = $db->query("SELECT COUNT(*) as c FROM dokumen WHERE status_peb_pib IN ('Menunggu Persetujuan','Pending Jaringan')");
$pebPending = $r->fetch_assoc()['c'];

$r = $db->query("SELECT COUNT(*) as c FROM manifest WHERE status = 'Terkirim'");
$manifestSiap = $r->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Forwarder — Sistem Logistik</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Dashboard Forwarder</h1>
            <p class="page-subtitle">Kelola verifikasi dokumen dan pengiriman — <?= date('d F Y') ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Dokumen Menunggu Verifikasi</div>
                <div class="stat-value" style="color:var(--warning)"><?= $dokMenunggu ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pengajuan PEB/PIB Pending</div>
                <div class="stat-value" style="color:var(--accent)"><?= $pebPending ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Manifest Siap Kirim</div>
                <div class="stat-value" style="color:var(--success)"><?= $manifestSiap ?></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><span class="card-title">Aksi Cepat</span></div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="forwarder_verifikasi.php" class="btn btn-primary">📋 Verifikasi Dokumen Baru</a>
                    <a href="forwarder_manifest.php" class="btn btn-secondary">📦 Submit Manifest</a>
                    <a href="forwarder_pengiriman.php" class="btn btn-outline">🚢 Manajemen Pengiriman</a>
                </div>
            </div>
        </div>
        
        <?php
        $recent = $db->query("SELECT d.ref_id, u.nama_lengkap, u.perusahaan, d.tipe_kiriman, d.status_sistem, d.status_verifikasi_forwarder, d.uploaded_at 
            FROM dokumen d JOIN users u ON d.customer_id = u.id 
            ORDER BY d.uploaded_at DESC LIMIT 8");
        ?>
        <div class="card">
            <div class="card-header"><span class="card-title">Dokumen Terbaru</span></div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Ref ID</th><th>Customer</th><th>Tipe</th><th>Status Sistem</th><th>Verifikasi</th><th>Tanggal</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $recent->fetch_assoc()): ?>
                        <tr>
                            <td class="font-mono" style="font-size:12px"><?= htmlspecialchars($row['ref_id']) ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap']) ?><br><span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($row['perusahaan']) ?></span></td>
                            <td><span class="badge badge-info"><?= $row['tipe_kiriman'] ?></span></td>
                            <td>
                                <?php $s=$row['status_sistem'];
                                $cls=['Auto-Check Valid'=>'badge-success','Auto-Check Pending'=>'badge-warning','Dok. Hilang'=>'badge-danger','Gagal'=>'badge-danger','Terverifikasi'=>'badge-success'][$s]??'badge-muted';
                                echo "<span class='badge $cls'>$s</span>"; ?>
                            </td>
                            <td>
                                <?php $v=$row['status_verifikasi_forwarder'];
                                $cls=['Pending'=>'badge-pending','Valid'=>'badge-success','Approved'=>'badge-success','Perlu Perbaikan'=>'badge-warning','Hold'=>'badge-danger'][$v]??'badge-muted';
                                echo "<span class='badge $cls'>$v</span>"; ?>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($row['uploaded_at'])) ?></td>
                            <td><a href="forwarder_verifikasi.php?ref=<?= urlencode($row['ref_id']) ?>" class="btn btn-outline btn-sm">Periksa</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script src="js/main.js"></script>
</body>
</html>
