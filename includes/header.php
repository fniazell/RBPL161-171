<?php

$current_user = getCurrentUser();
$role = $_SESSION['role'] ?? '';

$navMenus = [
    'customer' => [
        ['href' => 'dashboard_customer.php', 'label' => 'Dashboard'],
        ['href' => 'customer_upload.php', 'label' => 'Upload Dokumen'],
        ['href' => 'customer_clearance.php', 'label' => 'Status Clearance'],
        ['href' => 'customer_tracking.php', 'label' => 'Tracking Status'],
    ],
    'forwarder' => [
        ['href' => 'dashboard_forwarder.php', 'label' => 'Dashboard'],
        ['href' => 'forwarder_verifikasi.php', 'label' => 'Verifikasi Dokumen'],
        ['href' => 'forwarder_manifest.php', 'label' => 'Submit Manifest'],
        ['href' => 'forwarder_pengiriman.php', 'label' => 'Manajemen Pengiriman'],
    ],
    'pelayaran' => [
        ['href' => 'dashboard_pelayaran.php', 'label' => 'Dashboard'],
        ['href' => 'pelayaran_bl.php', 'label' => 'Terbitkan Bill of Lading'],
        ['href' => 'pelayaran_manifest.php', 'label' => 'Daftar Manifest'],
    ],
    'beacukai' => [
        ['href' => 'dashboard_beacukai.php', 'label' => 'Dashboard'],
        ['href' => 'beacukai_approval.php', 'label' => 'Approval / Hold'],
    ],
    'gudang' => [
        ['href' => 'dashboard_gudang.php', 'label' => 'Dashboard'],
        ['href' => 'gudang_pemeriksaan.php', 'label' => 'Pemeriksaan Fisik/Muat'],
    ],
    'jict' => [
        ['href' => 'dashboard_jict.php', 'label' => 'Dashboard'],
        ['href' => 'jict_pemeriksaan.php', 'label' => 'Pemeriksaan Fisik/Muat'],
    ],
    'vendor' => [
        ['href' => 'dashboard_vendor.php', 'label' => 'Dashboard'],
        ['href' => 'vendor_manifest.php', 'label' => 'Submit Manifest & Kontainer'],
    ],
];

$menus = $navMenus[$role] ?? [];
$portalTitle = [
    'customer' => 'PORTAL CUSTOMER',
    'forwarder' => 'PORTAL FORWARDER',
    'pelayaran' => 'PORTAL PELAYARAN',
    'beacukai' => 'PORTAL BEACUKAI',
    'gudang' => 'PORTAL GUDANG',
    'jict' => 'PORTAL JICT',
    'vendor' => 'PORTAL VENDOR',
][$role] ?? 'PORTAL';

$currentFile = basename($_SERVER['PHP_SELF']);
?>
