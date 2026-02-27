<?php
require_once 'includes/config.php';
requireRole('customer');
require_once 'includes/header.php';

$db = getDB();
$uid = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM dokumen WHERE customer_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$dokumens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Clearance — Sistem Logistik</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Status Clearance Dokumen</h1>
            <p class="page-subtitle">Lacak status verifikasi dokumen dan pengajuan PEB/PIB Anda secara real-time</p>
        </div>
        
        <div class="filter-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="Cari Ref ID atau jenis dokumen..." style="max-width:300px;">
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-title">Daftar Pengajuan Aktif</span>
                <span style="font-size:12px;color:var(--text-muted)"><?= count($dokumens) ?> dokumen</span>
            </div>
            <div class="table-container">
                <table id="clearanceTable">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Jenis Dokumen</th>
                            <th>Tipe</th>
                            <th>Status Verifikasi Forwarder</th>
                            <th>Status PEB/PIB Bea Cukai</th>
                            <th>Status BL Final</th>
                            <th>Tanggal Upload</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dokumens)): ?>
                        <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:48px;">Belum ada pengajuan</td></tr>
                        <?php else: foreach ($dokumens as $d): ?>
                        <tr>
                            <td class="font-mono" style="font-size:12px;font-weight:600"><?= htmlspecialchars($d['ref_id']) ?></td>
                            <td><?= htmlspecialchars($d['jenis_dokumen']) ?></td>
                            <td><span class="badge badge-info"><?= $d['tipe_kiriman'] ?></span></td>
                            <td>
                                <?php
                                $v = $d['status_verifikasi_forwarder'];
                                $cls = ['Pending'=>'badge-pending','Valid'=>'badge-success','Approved'=>'badge-success','Perlu Perbaikan'=>'badge-warning','Hold'=>'badge-danger'][$v] ?? 'badge-muted';
                                echo "<span class='badge $cls'>$v</span>";
                                ?>
                            </td>
                            <td>
                                <?php
                                $p = $d['status_peb_pib'];
                                $cls = ['Approved'=>'badge-success','Belum Diajukan'=>'badge-muted','Menunggu Persetujuan'=>'badge-warning','Ditolak'=>'badge-danger','Pending Jaringan'=>'badge-warning'][$p] ?? 'badge-info';
                                echo "<span class='badge $cls'>$p</span>";
                                ?>
                            </td>
                            <td>
                                <?php
                                $b = $d['status_bl_final'];
                                $cls = ['Terbit'=>'badge-success','Belum Terbit'=>'badge-muted','Ditolak'=>'badge-danger','Diproses'=>'badge-warning'][$b] ?? 'badge-muted';
                                echo "<span class='badge $cls'>$b</span>";
                                ?>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($d['uploaded_at'])) ?></td>
                            <td>
                                <a href="customer_tracking.php?ref=<?= urlencode($d['ref_id']) ?>" class="btn btn-outline btn-sm">Lacak</a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if (!empty($dokumens)): ?>
        <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--text-muted);">
            <div>📊 <strong><?= count($dokumens) ?></strong> total dokumen</div>
            <div>⏳ <strong><?= count(array_filter($dokumens, fn($d) => $d['status_verifikasi_forwarder'] === 'Pending')) ?></strong> menunggu verifikasi</div>
            <div>✅ <strong><?= count(array_filter($dokumens, fn($d) => $d['status_bl_final'] === 'Terbit')) ?></strong> BL terbit</div>
        </div>
        <?php endif; ?>
    </main>
</div>
<script src="js/main.js"></script>
<script>
initSearch('searchInput', 'clearanceTable', [0, 1, 2]);
</script>
</body>
</html>
