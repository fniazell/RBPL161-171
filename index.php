<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: dashboard_{$role}.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($email && $password && $role) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND role = ?");
        $stmt->bind_param("sss", $email, $email, $role);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && md5($password) === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            header("Location: dashboard_{$role}.php");
            exit;
        } else {
            $error = 'Username/email, password, atau role tidak sesuai.';
        }
    } else {
        $error = 'Semua field harus diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Logistik Ekspor/Impor</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <div class="login-logo-icon">⚓</div>
            <div>
                <div class="login-logo-text">Sistem Logistik</div>
                <div style="font-size:10px;color:#aaa;font-family:'Space Mono',monospace;">Ekspor / Impor Platform</div>
            </div>
        </div>

        <div class="login-title">LOGIN KE SISTEM LOGISTIK</div>

        <?php if ($error): ?>
        <div class="alert alert-error" data-auto-dismiss="4000">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label">Username / Email</label>
                <input type="text" name="email" class="form-control" placeholder="Masukkan username atau email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <input type="hidden" name="role" id="selectedRole" value="">
            
            <div style="margin-top:24px;">
                <div class="section-label" style="margin-bottom:12px;">Pilih Portal Login</div>
                <div class="role-grid">
                    <?php
                    $roles = [
                        ['customer', 'Login Customer'],
                        ['forwarder', 'Login Forwarder'],
                        ['pelayaran', 'Login Pelayaran'],
                        ['beacukai', 'Login Bea Cukai'],
                        ['vendor', 'Login Vendor'],
                        ['gudang', 'Login Gudang'],
                        ['jict', 'Login JICT'],
                    ];
                    foreach ($roles as $r): ?>
                    <button type="button" class="role-btn" data-role="<?= $r[0] ?>" onclick="selectRole('<?= $r[0] ?>')">
                        <?= $r[1] ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>

        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
            <div style="font-size:11px;color:var(--text-muted);font-family:'Space Mono',monospace;">
                Demo credentials: [role]@demo.com / demo123
            </div>
        </div>
    </div>
</div>

<script src="js/main.js"></script>
<script>
function selectRole(role) {
    document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
    document.querySelector(`[data-role="${role}"]`).classList.add('active');
    document.getElementById('selectedRole').value = role;
    
    setTimeout(() => {
        const email = document.querySelector('[name="email"]').value;
        const pass = document.querySelector('[name="password"]').value;
        if (email && pass) {
            document.getElementById('loginForm').submit();
        }
    }, 200);
}
</script>
</body>
</html>
