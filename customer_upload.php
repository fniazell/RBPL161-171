<?php
require_once 'includes/config.php';
requireRole('customer');
require_once 'includes/header.php';

$db = getDB();
$uid = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = trim($_POST['jenis_dokumen'] ?? '');
    $tipe = $_POST['tipe_kiriman'] ?? 'Ekspor (PEB)';
    $file = $_FILES['file_dokumen'] ?? null;
    
    if (!$jenis) {
        $error = 'Jenis dokumen harus diisi.';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File tidak valid atau tidak diunggah.';
    } else {
        $allowedExt = ['pdf','docx','xlsx','csv','xml'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExt)) {
            $error = 'Format tidak valid atau file rusak. Gunakan PDF, DOCX, XLSX, CSV, atau XML.';
        } elseif ($file['size'] > UPLOAD_MAX_SIZE) {
            $error = 'Ukuran file melebihi batas 10MB.';
        } else {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            
            $prefix = ($tipe === 'Impor (PIB)') ? 'IMP' : 'EXP';
            $refId = generateRefId($prefix);
            $newName = $refId . '_' . time() . '.' . $ext;
            $uploadPath = UPLOAD_DIR . $newName;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $stmt = $db->prepare("INSERT INTO dokumen (ref_id, customer_id, jenis_dokumen, nama_file, path_file, tipe_kiriman) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("sissss", $refId, $uid, $jenis, $file['name'], $newName, $tipe);
                $stmt->execute();
                $dokId = $db->insert_id;
                
                logTracking($dokId, 'Dokumen Diunggah', "Customer mengunggah: {$jenis}", $uid, 'customer');
                logTracking($dokId, 'Menunggu Verifikasi Forwarder', 'Dokumen dalam antrian verifikasi', null, 'system');
                
                $success = "Upload berhasil! Dokumen siap diverifikasi. Ref ID: <strong>{$refId}</strong>";
            } else {
                $error = 'Gagal menyimpan file. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Dokumen — Sistem Logistik</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Upload Dokumen Ekspor/Impor</h1>
            <p class="page-subtitle">Unggah dokumen yang diperlukan untuk proses ekspor atau impor</p>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success" data-auto-dismiss="5000">✓ <?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error" data-auto-dismiss="5000">✕ <?= $error ?></div>
        <?php endif; ?>
        
        <div class="card" style="max-width:640px;">
            <div class="card-header">
                <span class="card-title">Form Upload Dokumen</span>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="form-group">
                        <label class="form-label">Tipe Kiriman</label>
                        <select name="tipe_kiriman" class="form-select">
                            <option value="Ekspor (PEB)">Ekspor (PEB)</option>
                            <option value="Impor (PIB)">Impor (PIB)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Jenis Dokumen</label>
                        <input type="text" name="jenis_dokumen" class="form-control" 
                               placeholder="Contoh: Invoice, Packing List, Bill of Lading, dst."
                               value="<?= htmlspecialchars($_POST['jenis_dokumen'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Pilih File <span style="color:var(--text-muted);text-transform:none;font-weight:400">(Maks. 10MB — PDF, DOCX, XLSX, CSV, XML)</span></label>
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-icon">📁</div>
                            <div class="upload-text">
                                <strong>Klik untuk memilih file</strong> atau seret ke sini
                            </div>
                        </div>
                        <input type="file" name="file_dokumen" id="fileInput" 
                               accept=".pdf,.docx,.xlsx,.csv,.xml" style="display:none">
                        <div id="fileInfo" style="margin-top:8px;font-size:12px;color:var(--text-muted);"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
                        ↑ Upload Dokumen
                    </button>
                </form>
            </div>
        </div>
        
        <div style="margin-top:24px;max-width:640px;">
            <div class="section-label">Panduan Upload</div>
            <div class="card">
                <div class="card-body" style="padding:16px 24px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px;color:var(--text-muted);">
                        <div>📄 <strong style="color:var(--text)">Dokumen Ekspor:</strong><br>Commercial Invoice, Packing List, Certificate of Origin, Bill of Lading</div>
                        <div>📦 <strong style="color:var(--text)">Dokumen Impor:</strong><br>PIB, Invoice, Packing List, Airway Bill / BL</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="js/main.js"></script>
<script>
initUploadZone('uploadZone', 'fileInput', function(file) {
    document.getElementById('fileInfo').textContent = `File dipilih: ${file.name} (${(file.size/1024/1024).toFixed(2)} MB)`;
});

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('fileInput');
    if (!fileInput.files.length) {
        e.preventDefault();
        showToast('Pilih file terlebih dahulu!', 'error');
    }
});
</script>
</body>
</html>
