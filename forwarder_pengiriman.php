<?php
require_once 'includes/config.php';
requireRole('forwarder');
require_once 'includes/header.php';

$db = getDB();

$data = $db->query("SELECT d.ref_id, d.jenis_dokumen, d.tipe_kiriman, u.nama_lengkap, u.perusahaan,
    d.status_verifikasi_forwarder, d.status_peb_pib, d.status_bl_final,
    m.no_kontainer, m.status as manifest_status, m.submitted_at,
    bl.no_bl, bl.status as bl_status
    FROM dokumen d 
    JOIN users u ON d.customer_id = u.id
    LEFT JOIN manifest m ON m.dokumen_id = d.id
    LEFT JOIN bill_of_lading bl ON bl.dokumen_id = d.id
    ORDER BY d.uploaded_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengiriman — Sistem Logistik</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Manajemen Pengiriman</h1>
            <p class="page-subtitle">Kelola dan lacak semua pengiriman yang telah diverifikasi dan diajukan</p>
        </div>
        
        <div class="filter-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="Cari Ref ID, customer..." style="max-width:300px;">
            <select id="filterStatus" class="form-select" style="max-width:200px;">
                <option value="">Semua Status BL</option>
                <option value="Belum Terbit">Belum Terbit</option>
                <option value="Diproses">Diproses</option>
                <option value="Terbit">Terbit</option>
                <option value="Ditolak">Ditolak</option>
            </select>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span class="card-title">Daftar Pengiriman</span>
                <a href="forwarder_manifest.php" class="btn btn-primary btn-sm">+ Submit Manifest Baru</a>
            </div>
            <div class="table-container">
                <table id="pengirimanTable">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Customer</th>
                            <th>Tipe</th>
                            <th>Verifikasi</th>
                            <th>PEB/PIB</th>
                            <th>Kontainer</th>
                            <th>Manifest</th>
                            <th>BL Final</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $data->fetch_assoc()): ?>
                        <tr>
                            <td class="font-mono" style="font-size:12px;font-weight:600"><?= htmlspecialchars($row['ref_id']) ?></td>
                            <td style="font-size:13px">
                                <?= htmlspecialchars($row['nama_lengkap']) ?>
                                <br><span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($row['perusahaan']) ?></span>
                            </td>
                            <td><span class="badge badge-info"><?= $row['tipe_kiriman'] ?></span></td>
                            <td>
                                <?php $v=$row['status_verifikasi_forwarder'];
                                $cls=['Approved'=>'badge-success','Valid'=>'badge-success','Pending'=>'badge-pending','Hold'=>'badge-danger','Perlu Perbaikan'=>'badge-warning'][$v]??'badge-muted';
                                echo "<span class='badge $cls'>$v</span>"; ?>
                            </td>
                            <td>
                                <?php $p=$row['status_peb_pib'];
                                $cls=['Approved'=>'badge-success','Belum Diajukan'=>'badge-muted','Menunggu Persetujuan'=>'badge-warning','Ditolak'=>'badge-danger'][$p]??'badge-info';
                                echo "<span class='badge $cls'>$p</span>"; ?>
                            </td>
                            <td style="font-size:12px">
                                <?= $row['no_kontainer'] ? htmlspecialchars($row['no_kontainer']) : '<span style="color:var(--text-muted)">-</span>' ?>
                            </td>
                            <td>
                                <?php if ($row['manifest_status']): 
                                    $ms=$row['manifest_status'];
                                    $cls=['Terkirim'=>'badge-success','Diterima'=>'badge-success','Ditolak'=>'badge-danger','Pending'=>'badge-pending'][$ms]??'badge-muted';
                                    echo "<span class='badge $cls'>$ms</span>";
                                else: echo '<span style="color:var(--text-muted);font-size:12px">Belum</span>';
                                endif; ?>
                            </td>
                            <td>
                                <?php $b=$row['status_bl_final'];
                                $cls=['Terbit'=>'badge-success','Belum Terbit'=>'badge-muted','Diproses'=>'badge-warning','Ditolak'=>'badge-danger'][$b]??'badge-muted';
                                echo "<span class='badge $cls'>$b</span>"; 
                                if ($row['no_bl']): ?>
                                <br><span style="font-size:11px;color:var(--text-muted);font-family:'Space Mono',monospace"><?= htmlspecialchars($row['no_bl']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="customer_tracking.php?ref=<?= urlencode($row['ref_id']) ?>" class="btn btn-outline btn-sm">Detail</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script src="js/main.js"></script>
<script>
initSearch('searchInput', 'pengirimanTable', [0, 1, 2]);

document.getElementById('filterStatus').addEventListener('change', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#pengirimanTable tbody tr').forEach(row => {
        const blCell = row.cells[7];
        row.style.display = (!q || (blCell && blCell.textContent.toLowerCase().includes(q))) ? '' : 'none';
    });
});
</script>
</body>
</html>
