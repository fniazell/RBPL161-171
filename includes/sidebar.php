<?php
// sidebar.php
// Requires $portalTitle, $menus, $currentFile, $current_user to be set
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-title"><?= $portalTitle ?></div>
        <div class="sidebar-brand-sub">⚓ Sistem Logistik</div>
    </div>
    
    <nav class="sidebar-nav">
        <?php foreach ($menus as $menu): ?>
        <a href="<?= $menu['href'] ?>" class="nav-item <?= $currentFile === $menu['href'] ? 'active' : '' ?>">
            <?= htmlspecialchars($menu['label']) ?>
        </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <strong><?= htmlspecialchars($current_user['nama_lengkap'] ?? 'User') ?></strong>
            <?= htmlspecialchars($current_user['perusahaan'] ?? '') ?>
        </div>
        <a href="logout.php" class="logout-btn">→ Logout</a>
    </div>
</aside>
