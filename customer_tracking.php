<?php
require_once 'includes/config.php';
requireRole('customer');
require_once 'includes/header.php';

$db = getDB();
$uid = $_SESSION['user_id'];
$refFilter = $_GET['ref'] ?? '';

$stmt = $db->prepare("SELECT ref_id, jenis_dokumen FROM dokumen WHERE customer_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$myDocs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$trackingData = null;
$selectedDoc = null;

if ($refFilter) {
    $stmt = $db->prepare("SELECT d.*, u.nama_lengkap as forwarder_name FROM dokumen d 
        LEFT JOIN users u ON d.forwarder_id = u.id 
        WHERE d.ref_id = ? AND d.customer_id = ?");
    $stmt->bind_param("si", $refFilter, $uid);
    $stmt->execute();
    $selectedDoc = $stmt->get_result()->fetch_assoc();
    
    if ($selectedDoc) {
        $stmt2 = $db->prepare("SELECT t.*, u.nama_lengkap as actor_name FROM tracking_log t 
            LEFT JOIN users u ON t.actor_id = u.id 
            WHERE t.dokumen_id = ? ORDER BY t.created_at ASC");
        $stmt2->bind_param("i", $selectedDoc['id']);
        $stmt2->execute();
        $trackingData = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$stages = [
    ['label' => 'Dokumen Diunggah', 'key' => 'upload'],
    ['label' => 'Verifikasi Forwarder', 'key' => 'forwarder'],
    ['label' => 'Pengajuan Bea Cukai (PEB/PIB)', 'key' => 'beacukai'],
    ['label' => 'Persetujuan Bea Cukai', 'key' => 'bc_approved'],
    ['label' => 'Pemeriksaan Fisik', 'key' => 'fisik'],
    ['label' => 'Bill of Lading Terbit', 'key' => 'bl'],
    ['label' => 'Selesai', 'key' => 'done'],
];

function getStageStatus($doc) {
    if (!$doc) return 0;
    if ($doc['status_bl_final'] === 'Terbit') return 7;
    if ($doc['status_peb_pib'] === 'Approved') return 5;
    if ($doc['status_peb_pib'] === 'Menunggu Persetujuan') return 4;
    if ($doc['status_peb_pib'] === 'Belum Diajukan' && in_array($doc['status_verifikasi_forwarder'], ['Valid','Approved'])) return 3;
    if (in_array($doc['status_verifikasi_forwarder'], ['Valid','Approved','Hold','Perlu Perbaikan'])) return 2;
    return 1;
}
$currentStage = getStageStatus($selectedDoc);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Status — Sistem Logistik</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Detail Tracking</h1>
            <p class="page-subtitle">Timeline proses logistik pengiriman Anda</p>
        </div>
        
        <div class="card" style="max-width:500px;margin-bottom:24px;">
            <div class="card-body">
                <div class="section-label" style="margin-bottom:8px;">Pilih Dokumen</div>
                <form method="GET">
                    <div style="display:flex;gap:8px;">
                        <select name="ref" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Ref ID --</option>
                            <?php foreach ($myDocs as $doc): ?>
                            <option value="<?= htmlspecialchars($doc['ref_id']) ?>" <?= $refFilter === $doc['ref_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['ref_id']) ?> — <?= htmlspecialchars($doc['jenis_dokumen']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($selectedDoc): ?>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
            <div class="card">
                <div class="card-header"><span class="card-title">Informasi Dokumen</span></div>
                <div class="card-body" style="padding:16px 24px;">
                    <table style="width:100%;border:none;">
                        <tr><td style="padding:6px 0;color:var(--text-muted);font-size:12px;border:none;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Ref ID</td>
                            <td style="padding:6px 0;border:none;font-family:'Space Mono',monospace;font-size:13px;font-weight:700"><?= htmlspecialchars($selectedDoc['ref_id']) ?></td></tr>
                        <tr><td style="padding:6px 0;color:var(--text-muted);font-size:12px;border:none;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Jenis Dok.</td>
                            <td style="padding:6px 0;border:none;font-size:13px"><?= htmlspecialchars($selectedDoc['jenis_dokumen']) ?></td></tr>
                        <tr><td style="padding:6px 0;color:var(--text-muted);font-size:12px;border:none;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Tipe</td>
                            <td style="padding:6px 0;border:none"><span class="badge badge-info"><?= $selectedDoc['tipe_kiriman'] ?></span></td></tr>
                        <tr><td style="padding:6px 0;color:var(--text-muted);font-size:12px;border:none;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">BL Final</td>
                            <td style="padding:6px 0;border:none">
                                <?php $b = $selectedDoc['status_bl_final'];
                                $cls = ['Terbit'=>'badge-success','Belum Terbit'=>'badge-muted'][$b] ?? 'badge-warning';
                                echo "<span class='badge $cls'>$b</span>"; ?>
                            </td></tr>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><span class="card-title">Progress Pengiriman</span></div>
                <div class="card-body" style="padding:16px 24px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <div style="font-family:'Space Mono',monospace;font-size:28px;font-weight:700;color:var(--accent)"><?= $currentStage ?></div>
                        <div style="font-size:12px;color:var(--text-muted)">/7 tahap<br>selesai</div>
                    </div>
                    <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden;">
                        <div style="height:100%;width:<?= ($currentStage/7*100) ?>%;background:<?= $currentStage >= 7 ? 'var(--success)' : 'var(--accent)' ?>;transition:width 1s;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
            <div class="card">
                <div class="card-header"><span class="card-title">Tahapan Proses</span></div>
                <div class="card-body">
                    <?php foreach ($stages as $i => $stage): 
                        $stageNum = $i + 1;
                        $isDone = $stageNum <= $currentStage;
                        $isActive = $stageNum === $currentStage;
                    ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);">
                        <div style="width:28px;height:28px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;
                            background:<?= $isDone ? 'var(--success)' : 'var(--border)' ?>;
                            color:<?= $isDone ? 'white' : 'var(--text-muted)' ?>;
                            <?= $isActive ? 'box-shadow:0 0 0 4px rgba(233,69,96,0.2);background:var(--accent);' : '' ?>">
                            <?= $isDone ? '✓' : $stageNum ?>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:<?= $isActive ? '700' : '400' ?>;color:<?= $isDone ? 'var(--text)' : 'var(--text-muted)' ?>">
                                <?= $stage['label'] ?>
                            </div>
                            <?php if ($isActive): ?>
                            <div style="font-size:11px;color:var(--accent);font-weight:600">← Sedang diproses</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><span class="card-title">Log Aktivitas</span></div>
                <div class="card-body">
                    <?php if ($trackingData): ?>
                    <div class="timeline">
                        <?php foreach (array_reverse($trackingData) as $idx => $log): ?>
                        <div class="timeline-item <?= $idx === 0 ? 'active' : 'done' ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></div>
                            <div class="timeline-label"><?= htmlspecialchars($log['status_label']) ?></div>
                            <?php if ($log['keterangan']): ?>
                            <div class="timeline-desc"><?= htmlspecialchars($log['keterangan']) ?></div>
                            <?php endif; ?>
                            <?php if ($log['actor_name']): ?>
                            <div style="font-size:11px;color:var(--accent);margin-top:2px;">oleh: <?= htmlspecialchars($log['actor_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="color:var(--text-muted);font-size:13px;">Belum ada log aktivitas</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php elseif ($refFilter): ?>
        <div class="alert alert-error">Dokumen tidak ditemukan atau bukan milik Anda.</div>
        <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align:center;padding:60px;color:var(--text-muted);">
                <div style="font-size:48px;margin-bottom:16px;">🔍</div>
                <div style="font-size:16px;font-weight:600;margin-bottom:8px;">Pilih dokumen untuk melihat tracking</div>
                <div style="font-size:13px;">Gunakan dropdown di atas untuk memilih Ref ID pengiriman</div>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
<script src="js/main.js"></script>
</body>
</html>
