<?php
require_once 'includes/config.php';
requireRole('forwarder');
require_once 'includes/header.php';

$db = getDB();
$uid = $_SESSION['user_id'];
$refFilter = $_GET['ref'] ?? '';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_verifikasi') {
    $dokId = (int)$_POST['dokumen_id'];
    $status = $_POST['status_verifikasi'];
    $catatan = trim($_POST['catatan'] ?? '');
    
    $allowed = ['Valid', 'Perlu Perbaikan', 'Approved', 'Hold'];
    if (!in_array($status, $allowed)) {
        $error = 'Status tidak valid.';
    } else {
        $stmt = $db->prepare("UPDATE dokumen SET status_verifikasi_forwarder = ?, catatan_forwarder = ?, forwarder_id = ?, status_sistem = 'Terverifikasi' WHERE id = ?");
        $stmt->bind_param("ssii", $status, $catatan, $uid, $dokId);
        $stmt->execute();
        
        logTracking($dokId, "Verifikasi Forwarder: $status", $catatan ?: "Forwarder memberikan status $status", $uid, 'forwarder');
        $success = "Status verifikasi berhasil disimpan: <strong>$status</strong>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajukan_bea_cukai') {
    $dokId = (int)$_POST['dokumen_id'];

    $apiSuccess = (rand(1,10) > 2); 
    
    if ($apiSuccess) {
        $stmt = $db->prepare("UPDATE dokumen SET status_peb_pib = 'Menunggu Persetujuan' WHERE id = ?");
        $stmt->bind_param("i", $dokId);
        $stmt->execute();
        logTracking($dokId, 'Pengajuan PEB/PIB Dikirim ke Bea Cukai', 'Forwarder mengajukan dokumen ke sistem bea cukai', $uid, 'forwarder');
        $success = 'Pengajuan PEB/PIB berhasil dikirim ke Bea Cukai.';
    } else {
        $error = 'Koneksi ke Bea Cukai gagal. Status Pending, akan coba kirim ulang.';
        $stmt = $db->prepare("UPDATE dokumen SET status_peb_pib = 'Pending Jaringan' WHERE id = ?");
        $stmt->bind_param("i", $dokId);
        $stmt->execute();
    }
}

$r = $db->query("SELECT COUNT(*) as c FROM dokumen WHERE status_verifikasi_forwarder = 'Pending'"); $totalMenunggu = $r->fetch_assoc()['c'];
$r = $db->query("SELECT COUNT(*) as c FROM dokumen WHERE status_verifikasi_forwarder IN ('Valid','Approved','Terverifikasi')"); $terverifikasi = $r->fetch_assoc()['c'];
$r = $db->query("SELECT COUNT(*) as c FROM dokumen WHERE status_verifikasi_forwarder = 'Perlu Perbaikan'"); $perbaikan = $r->fetch_assoc()['c'];
$r = $db->query("SELECT COUNT(*) as c FROM dokumen WHERE status_sistem = 'Gagal'"); $gagalAuto = $r->fetch_assoc()['c'];

$stmt = $db->prepare("SELECT d.*, u.nama_lengkap, u.perusahaan FROM dokumen d JOIN users u ON d.customer_id = u.id ORDER BY d.uploaded_at DESC");
$stmt->execute();
$dokumens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$selectedDoc = null;
if ($refFilter) {
    foreach ($dokumens as $d) {
        if ($d['ref_id'] === $refFilter) { $selectedDoc = $d; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen — Sistem Logistik</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Verifikasi Dokumen Customer</h1>
            <p class="page-subtitle">Daftar dokumen yang siap diperiksa kelengkapan dan keabsahannya</p>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success" data-auto-dismiss="5000">✓ <?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error" data-auto-dismiss="5000">✕ <?= $error ?></div>
        <?php endif; ?>
        
        <div class="filter-bar">
            <div class="filter-badge">Total Menunggu: <span><?= $totalMenunggu ?></span></div>
            <div class="filter-badge">Terverifikasi: <span><?= $terverifikasi ?></span></div>
            <div class="filter-badge">Perlu Perbaikan: <span><?= $perbaikan ?></span></div>
            <div class="filter-badge">Gagal Auto-Check: <span><?= $gagalAuto ?></span></div>
        </div>
        
        <?php if (!$selectedDoc): ?>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Daftar Dokumen</span>
                <input type="text" id="searchDoc" class="search-input" placeholder="Cari..." style="max-width:220px;">
            </div>
            <div class="table-container">
                <table id="docTable">
                    <thead>
                        <tr><th>Ref ID</th><th>Nama Customer</th><th>Tipe Kiriman</th><th>Status Sistem</th><th>Verifikasi</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dokumens as $d): ?>
                        <tr>
                            <td class="font-mono" style="font-size:12px;font-weight:600"><?= htmlspecialchars($d['ref_id']) ?></td>
                            <td><?= htmlspecialchars($d['nama_lengkap']) ?><br><span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($d['perusahaan']) ?></span></td>
                            <td><span class="badge badge-info"><?= $d['tipe_kiriman'] ?></span></td>
                            <td>
                                <?php $s=$d['status_sistem'];
                                $cls=['Auto-Check Valid'=>'badge-success','Auto-Check Pending'=>'badge-warning','Dok. Hilang'=>'badge-danger','Gagal'=>'badge-danger','Terverifikasi'=>'badge-success'][$s]??'badge-muted';
                                echo "<span class='badge $cls'>$s</span>"; ?>
                            </td>
                            <td>
                                <?php $v=$d['status_verifikasi_forwarder'];
                                $cls=['Pending'=>'badge-pending','Valid'=>'badge-success','Approved'=>'badge-success','Perlu Perbaikan'=>'badge-warning','Hold'=>'badge-danger'][$v]??'badge-muted';
                                echo "<span class='badge $cls'>$v</span>"; ?>
                            </td>
                            <td>
                                <a href="?ref=<?= urlencode($d['ref_id']) ?>" class="btn btn-outline btn-sm">Periksa</a>
                                <?php if (in_array($d['status_verifikasi_forwarder'], ['Valid','Approved']) && $d['status_peb_pib'] === 'Belum Diajukan'): ?>
                                <a href="?ref=<?= urlencode($d['ref_id']) ?>" class="btn btn-primary btn-sm">Verifikasi & Ajukan</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php else:  ?>

        <div style="margin-bottom:16px;">
            <a href="forwarder_verifikasi.php" class="btn btn-outline btn-sm">← Kembali ke Daftar</a>
            <span style="margin-left:12px;font-family:'Space Mono',monospace;font-size:12px;color:var(--text-muted)">
                Customer: <?= htmlspecialchars($selectedDoc['nama_lengkap']) ?> — <?= htmlspecialchars($selectedDoc['perusahaan']) ?>
            </span>
        </div>
        
        <div class="split-view">
            <div>
                <div class="doc-preview">
                    <div class="doc-preview-title">Pratinjau Dokumen</div>
                    
                    <div style="margin-bottom:12px;">
                        <span class="badge badge-info"><?= htmlspecialchars($selectedDoc['jenis_dokumen']) ?></span>
                        <span style="margin-left:8px;font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($selectedDoc['nama_file']) ?></span>
                    </div>
                    
                    <div class="doc-preview-frame">
                        <?php 
                        $fp = UPLOAD_DIR . $selectedDoc['path_file'];
                        $ext = strtolower(pathinfo($selectedDoc['nama_file'], PATHINFO_EXTENSION));
                        if ($ext === 'pdf' && file_exists($fp)):
                        ?>
                        <iframe src="uploads/<?= htmlspecialchars($selectedDoc['path_file']) ?>" style="width:100%;height:380px;border:none;"></iframe>
                        <?php else: ?>
                        <div style="text-align:center;padding:40px;">
                            <div style="font-size:48px;margin-bottom:12px;">📄</div>
                            <div style="font-size:13px;color:var(--text-muted)">Viewer PDF Dokumen</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:4px"><?= htmlspecialchars($selectedDoc['nama_file']) ?></div>
                            <?php if (file_exists($fp)): ?>
                            <a href="uploads/<?= htmlspecialchars($selectedDoc['path_file']) ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top:12px;">Buka File</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="decision-panel">
                <div class="card">
                    <div class="card-header"><span class="card-title">Check Kelengkapan Sistem</span></div>
                    <div class="card-body">
                        <?php
                        $checks = [
                            ['Invoice', rand(0,1)],
                            ['Packing List', rand(0,1)],
                            ['Bill of Lading', rand(0,10) > 7 ? -1 : rand(0,1)],
                        ];
                        foreach ($checks as [$name, $status]):
                            $icon = $status === 1 ? '✅' : ($status === -1 ? '⚠️' : '❌');
                            $label = $status === 1 ? 'Lengkap' : ($status === -1 ? 'Belum Ada/Duplikasi' : 'Tidak Ditemukan');
                        ?>
                        <div class="check-item">
                            <span class="check-icon"><?= $icon ?></span>
                            <span><?= $name ?>: <strong><?= $label ?></strong></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><span class="card-title">Keputusan Verifikasi Manual</span></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="simpan_verifikasi">
                            <input type="hidden" name="dokumen_id" value="<?= $selectedDoc['id'] ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Pilih Status</label>
                                <select name="status_verifikasi" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    <?php foreach (['Valid','Perlu Perbaikan','Approved','Hold'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $selectedDoc['status_verifikasi_forwarder'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Catatan untuk Customer (jika perlu perbaikan)</label>
                                <textarea name="catatan" class="form-textarea" placeholder="Tuliskan catatan atau instruksi perbaikan..."><?= htmlspecialchars($selectedDoc['catatan_forwarder'] ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">💾 Simpan Status Verifikasi</button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header"><span class="card-title">Pengajuan Bea Cukai</span></div>
                    <div class="card-body">
                        <?php $canSubmit = in_array($selectedDoc['status_verifikasi_forwarder'], ['Valid','Approved']) && $selectedDoc['status_peb_pib'] === 'Belum Diajukan'; ?>
                        
                        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                            Dokumen siap diajukan setelah status internal Valid.
                        </p>
                        
                        <div style="margin-bottom:12px;font-size:12px;">
                            Status PEB/PIB: 
                            <?php $p=$selectedDoc['status_peb_pib'];
                            $cls=['Belum Diajukan'=>'badge-muted','Menunggu Persetujuan'=>'badge-warning','Approved'=>'badge-success','Ditolak'=>'badge-danger','Pending Jaringan'=>'badge-warning'][$p]??'badge-muted';
                            echo "<span class='badge $cls'>$p</span>"; ?>
                        </div>
                        
                        <?php if ($canSubmit): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="ajukan_bea_cukai">
                            <input type="hidden" name="dokumen_id" value="<?= $selectedDoc['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-block">📤 Ajukan PEB/PIB Bea Cukai</button>
                        </form>
                        <?php elseif ($selectedDoc['status_peb_pib'] === 'Pending Jaringan'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="ajukan_bea_cukai">
                            <input type="hidden" name="dokumen_id" value="<?= $selectedDoc['id'] ?>">
                            <div class="alert alert-warning" style="margin-bottom:12px;">⚠ Koneksi sebelumnya gagal.</div>
                            <button type="submit" class="btn btn-warning btn-block">🔄 Coba Kirim Ulang</button>
                        </form>
                        <?php elseif (!$canSubmit && $selectedDoc['status_peb_pib'] === 'Belum Diajukan'): ?>
                        <div class="alert alert-warning">Selesaikan verifikasi dokumen terlebih dahulu.</div>
                        <?php else: ?>
                        <div class="alert alert-success">✓ Sudah diajukan ke Bea Cukai</div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
<script src="js/main.js"></script>
<script>
initSearch('searchDoc', 'docTable', [0, 1, 2]);
</script>
</body>
</html>
