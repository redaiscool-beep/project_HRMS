<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (currentUserId() !== null) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare(
        'SELECT id, username, password_hash
         FROM train_users
         WHERE username = :username
         LIMIT 1'
    );

    $stmt->execute([
        'username' => $username
    ]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];

        if (isset($_POST['remember'])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                time() + 60 * 60 * 24 * 30,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        header('Location: index.php');
        exit;
    }

    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}

$csrfToken = csrfToken();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | HRMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="login-page">

    <div class="login-hero">
        <div class="login-hero__blob login-hero__blob--1"></div>
        <div class="login-hero__blob login-hero__blob--2"></div>

        <svg class="login-hero__illustration" viewBox="0 0 400 320" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <line x1="90" y1="230" x2="210" y2="130" stroke="rgba(255,255,255,0.28)" stroke-width="2"/>
            <line x1="210" y1="130" x2="330" y2="210" stroke="rgba(255,255,255,0.28)" stroke-width="2"/>
            <line x1="210" y1="130" x2="160" y2="60" stroke="rgba(255,255,255,0.28)" stroke-width="2"/>
            <line x1="210" y1="130" x2="290" y2="55" stroke="rgba(255,255,255,0.28)" stroke-width="2"/>
            <line x1="90" y1="230" x2="40" y2="150" stroke="rgba(255,255,255,0.2)" stroke-width="2"/>
            <line x1="330" y1="210" x2="365" y2="130" stroke="rgba(255,255,255,0.2)" stroke-width="2"/>
            <circle cx="210" cy="130" r="30" fill="rgba(255,255,255,0.95)"/>
            <circle cx="90" cy="230" r="20" fill="rgba(255,255,255,0.55)"/>
            <circle cx="330" cy="210" r="20" fill="rgba(255,255,255,0.55)"/>
            <circle cx="160" cy="60" r="15" fill="rgba(255,255,255,0.4)"/>
            <circle cx="290" cy="55" r="15" fill="rgba(255,255,255,0.4)"/>
            <circle cx="40" cy="150" r="11" fill="rgba(255,255,255,0.3)"/>
            <circle cx="365" cy="130" r="11" fill="rgba(255,255,255,0.3)"/>
        </svg>

        <div class="login-hero__content">
            <div class="login-hero__brand">HR<span>MS</span></div>
            <p class="login-hero__tagline">ระบบบริหารทรัพยากรบุคคล</p>
            <p class="login-hero__subtitle">บริหารคน พัฒนาองค์กร<br>สู่อนาคตที่ดีกว่า</p>

            <div class="login-hero__features">
                <div class="login-hero__feature">
                    <div class="login-hero__feature-icon"><i class="bi bi-diagram-3"></i></div>
                    <span>เชื่อมโยงข้อมูล<br>อย่างเป็นระบบ</span>
                </div>
                <div class="login-hero__feature">
                    <div class="login-hero__feature-icon"><i class="bi bi-shield-check"></i></div>
                    <span>ปลอดภัย<br>เชื่อถือได้</span>
                </div>
                <div class="login-hero__feature">
                    <div class="login-hero__feature-icon"><i class="bi bi-wifi"></i></div>
                    <span>รองรับการทำงาน<br>ทุกที่ ทุกเวลา</span>
                </div>
            </div>
        </div>

        <div class="login-hero__signature">People Make<br>Tomorrow</div>
    </div>

    <div class="login-card-wrap">
        <div class="login-card">
            <div class="login-card__icon"><i class="bi bi-people-fill"></i></div>
            <h1>เข้าสู่ระบบ HRMS</h1>
            <p class="login-card__subtitle">กรุณากรอกข้อมูลเพื่อเข้าใช้งานระบบ</p>

            <?php if ($error !== ''): ?>
                <p class="alert alert-error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="login-input">
                    <span class="login-input__icon"><i class="bi bi-person"></i></span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="ชื่อผู้ใช้"
                        required
                    >
                </div>

                <div class="login-input">
                    <span class="login-input__icon"><i class="bi bi-lock"></i></span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="รหัสผ่าน"
                        required
                    >
                    <button type="button" class="login-input__toggle" onclick="togglePasswordVisibility()" aria-label="แสดง/ซ่อนรหัสผ่าน">
                        <i class="bi bi-eye" id="passwordToggleIcon"></i>
                    </button>
                </div>

                <div class="login-card__row">
                    <label class="login-checkbox">
                        <input type="checkbox" name="remember" value="1">
                        จดจำการเข้าสู่ระบบ
                    </label>
                    <a href="#" class="login-forgot" onclick="return false;">ลืมรหัสผ่าน?</a>
                </div>

                <button type="submit" class="login-submit">
                    เข้าสู่ระบบ <i class="bi bi-arrow-right"></i>
                </button>

            </form>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    var input = document.getElementById('password');
    var icon = document.getElementById('passwordToggleIcon');
    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>

</body>
</html>
