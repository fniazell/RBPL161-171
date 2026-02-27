<?php
require_once 'includes/config.php';
requireRole('forwarder');
require_once 'includes/header.php';

$db = getDB();
$uid = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dokId = (int)$_POST['dokumen_id'];
    $noKontainer = trim($_POST['no_kontainer'] ?? '');
    $segel = trim($_POST['segel_kontainer'] ?? '');
    $jenis = trim($_POST['jenis_kontainer'] ?? '');
    $file = $_FILES['file_manifest'] ?? null;
    
    if (!$dokId || !$noKontainer) {
        $error = 'Pilih referensi pengiriman dan isi data kontainer.';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Data manifest tidak lengkap/format salah. Sistem menolak.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx','csv','xml'])) {
            $error = 'Data manifest tidak valid. Gunakan format Excel, CSV, atau XML.';
        } else {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            $fname = 'manifest_' . time() . '.' . $ext;
            $fpath = UPLOAD_DIR . $fname;
            
            if (move_uploaded_file($file['tmp_name'], $fpath)) {
                $stmt = $db->prepare("INSERT INTO manifest (dokumen_id, forwarder_id, nama_file_manifest, path_file_manifest, no_kontainer, segel_kontainer, jenis_kontainer, status) VALUES (?,?,?,?,?,?,?,'Terkirim')");
                $stmt->bind_param("iisssss", $dokId, $uid, $file['name'], $fname, $noKontainer, $segel, $jenis);
                $stmt->execute();
                
                logTracking($dokId, 'Manifest Disubmit ke Pelayaran', "Kontainer: $noKontainer", $uid, 'forwarder');
                
                // Also create pemeriksaan_fisik record
                $stmt2 = $db->prepare("SELECT ref_id FROM dokumen WHERE id = ?");
                $stmt2->bind_param("i", $dokId);
                $stmt2->execute();
                $dok = $stmt2->get_result()->fetch_assoc();
                
                $mId = $db->insert_id;
                $stmt3 = $db->prepare("INSERT INTO pemeriksaan_fisik (dokumen_id, manifest_id, no_kontainer, ref_id) VALUES (?,?,?,?)");
                $stmt3->bind_param("iiss", $dokId, $mId, $noKontainer, $dok['ref_id']);
                $stmt3->execute();
                
                $success = "Manifest berhasil disubmit ke Pelayaran. Kontainer: <strong>$noKontainer</strong>";
            } else {
                $error = 'Koneksi ke Pelayaran gagal. Status Pending, akan coba kirim ulang.';
            }
        }
    }
}

// Get valid docs for selection
$validDocs = $db->query("SELECT d.id, d.ref_id, u.nama_lengkap, u.perusahaan, d.status_verifikasi_forwarder 
    FROM dokumen d JOIN users u ON d.customer_id = u.id 
    ORDER BY d.uploaded_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Manifest — Sistem Logistik</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Submit Manifest & Data Kontainer</h1>
            <p class="page-subtitle">Unggah data manifest dan kontainer sebelum pengiriman ke pihak Pelayaran</p>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success" data-auto-dismiss="5000">✓ <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error" data-auto-dismiss="6000">✕ <?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            
            <div class="card">
                <div class="card-header"><span class="card-title">1. Pilih Referensi Pengiriman</span></div>
                <div class="card-body">
                    <div class="search-bar">
                        <input type="text" id="searchRef" class="search-input" placeholder="Cari Ref ID atau Nama Customer...">
                    </div>
                    <div class="table-container">
                        <table id="refTable">
                            <thead>
                                <tr><th style="width:40px">Pilih</th><th>Ref ID</th><th>Nama Customer</th><th>Status Verifikasi</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $validDocs->fetch_assoc()):
                                    $canSelect = in_array($row['status_verifikasi_forwarder'], ['Valid','Approved']);
                                ?>
                                <tr style="<?= !$canSelect ? 'opacity:0.5' : '' ?>">
                                    <td>
                                        <?php if ($canSelect): ?>
                                        <input type="radio" name="dokumen_id" value="<?= $row['id'] ?>" required style="accent-color:var(--accent)">
                                        <?php else: ?>
                                        <input type="radio" disabled>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-mono" style="font-size:12px;font-weight:600"><?= htmlspecialchars($row['ref_id']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_lengkap']) ?><br><span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($row['perusahaan']) ?></span></td>
                                    <td>
                                        <?php $v=$row['status_verifikasi_forwarder'];
                                        $cls=['Valid'=>'badge-success','Approved'=>'badge-success','Pending'=>'badge-pending','Perlu Perbaikan'=>'badge-warning','Hold'=>'badge-danger'][$v]??'badge-muted';
                                        echo "<span class='badge $cls'>$v</span>"; ?>
                                        <?php if (!$canSelect): ?>
                                        <span style="font-size:11px;color:var(--text-muted);margin-left:8px;">Belum Valid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <p style="font-size:12px;color:var(--text-muted);margin-top:8px;">Hanya yang berstatus Valid/Disetujui yang dapat dipilih.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><span class="card-title">2. Unggah File Manifest (Excel/CSV/XML)</span></div>
                <div class="card-body">
                    <div class="upload-zone" id="manifestZone">
                        <div class="upload-icon">📊</div>
                        <div class="upload-text"><strong>Pilih File</strong> atau Seret File Manifest</div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Format: .xlsx, .csv, .xml</div>
                    </div>
                    <input type="file" name="file_manifest" id="manifestInput" accept=".xlsx,.csv,.xml" style="display:none">
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><span class="card-title">3. Data Kontainer</span></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label class="form-label">Nomor Kontainer</label>
                            <input type="text" name="no_kontainer" class="form-control" placeholder="Contoh: TCLU 123784" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Segel Kontainer</label>
                            <input type="text" name="segel_kontainer" class="form-control" placeholder="Contoh: SG-99887">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jenis Kontainer</label>
                            <select name="jenis_kontainer" class="form-select">
                                <option value="">-- Pilih --</option>
                                <option>20 feet Dry</option>
                                <option>40 feet Dry</option>
                                <option>40 feet High Cube</option>
                                <option>20 feet Reefer</option>
                                <option>40 feet Reefer</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg btn-block">🚢 Submit Manifest ke Pelayaran</button>
        </form>
    </main>
</div>
<script src="js/main.js"></script>
<script>
initUploadZone('manifestZone', 'manifestInput', null);
initSearch('searchRef', 'refTable', [1, 2]);
</script>
</body>
</html>
