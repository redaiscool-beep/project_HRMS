<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$positionId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $positionId !== null;

$data = [
    'position_code' => '',
    'position_name_th' => '',
    'position_name_en' => '',
    'position_level' => '',
    'sort_order' => '0',
    'is_active' => true,
    'remark' => '',
];

$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM hr_position WHERE position_id = :id');
    $stmt->execute(['id' => $positionId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        header('Location: positions.php');
        exit;
    }

    $data = $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $data['position_code'] = trim($_POST['position_code'] ?? '');
    $data['position_name_th'] = trim($_POST['position_name_th'] ?? '');
    $data['position_name_en'] = trim($_POST['position_name_en'] ?? '');
    $data['position_level'] = trim($_POST['position_level'] ?? '');
    $data['sort_order'] = trim($_POST['sort_order'] ?? '0');
    $data['is_active'] = isset($_POST['is_active']);
    $data['remark'] = trim($_POST['remark'] ?? '');

    if ($data['position_code'] === '') {
        $errors['position_code'] = 'กรุณากรอกรหัสตำแหน่ง';
    }

    if ($data['position_name_th'] === '') {
        $errors['position_name_th'] = 'กรุณากรอกชื่อตำแหน่ง (ไทย)';
    }

    if ($data['position_level'] !== '' && !ctype_digit($data['position_level'])) {
        $errors['position_level'] = 'ระดับตำแหน่งต้องเป็นตัวเลข';
    }

    if ($data['sort_order'] !== '' && !ctype_digit($data['sort_order'])) {
        $errors['sort_order'] = 'ลำดับต้องเป็นตัวเลข';
    }

    if ($data['position_code'] !== '') {
        $dupStmt = db()->prepare(
            'SELECT position_id FROM hr_position
             WHERE position_code = :code AND position_id != :id'
        );
        $dupStmt->execute([
            'code' => $data['position_code'],
            'id' => $positionId ?? 0,
        ]);

        if ($dupStmt->fetch()) {
            $errors['position_code'] = 'รหัสตำแหน่งนี้มีอยู่แล้ว';
        }
    }

    if (empty($errors)) {
        $params = [
            'code' => $data['position_code'],
            'name_th' => $data['position_name_th'],
            'name_en' => $data['position_name_en'] !== '' ? $data['position_name_en'] : null,
            'level' => $data['position_level'] !== '' ? (int) $data['position_level'] : null,
            'sort_order' => (int) $data['sort_order'],
            'is_active' => $data['is_active'] ? 'true' : 'false',
            'remark' => $data['remark'] !== '' ? $data['remark'] : null,
        ];

        if ($isEdit) {
            $params['id'] = $positionId;
            $stmt = db()->prepare(
                'UPDATE hr_position
                 SET position_code = :code,
                     position_name_th = :name_th,
                     position_name_en = :name_en,
                     position_level = :level,
                     sort_order = :sort_order,
                     is_active = :is_active,
                     remark = :remark,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE position_id = :id'
            );
        } else {
            $stmt = db()->prepare(
                'INSERT INTO hr_position
                    (position_code, position_name_th, position_name_en, position_level, sort_order, is_active, remark)
                 VALUES (:code, :name_th, :name_en, :level, :sort_order, :is_active, :remark)'
            );
        }

        $stmt->execute($params);

        header('Location: positions.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'แก้ไขตำแหน่ง' : 'เพิ่มตำแหน่งใหม่';
$activeMenu = 'positions';
$breadcrumb = ['Master Data', 'Position', $pageTitle];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><span class="page-title-icon icon-purple"><i class="bi bi-briefcase"></i></span><?= e($pageTitle) ?></h1>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend>ข้อมูลตำแหน่ง</legend>

        <div class="form-group">
            <label for="position_code">รหัสตำแหน่ง *</label>
            <input type="text" id="position_code" name="position_code"
                   value="<?= e($data['position_code']) ?>" required>
            <?php if (isset($errors['position_code'])): ?>
                <p class="error"><?= e($errors['position_code']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="position_name_th">ชื่อตำแหน่ง (ไทย) *</label>
            <input type="text" id="position_name_th" name="position_name_th"
                   value="<?= e($data['position_name_th']) ?>" required>
            <?php if (isset($errors['position_name_th'])): ?>
                <p class="error"><?= e($errors['position_name_th']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="position_name_en">ชื่อตำแหน่ง (อังกฤษ)</label>
            <input type="text" id="position_name_en" name="position_name_en"
                   value="<?= e($data['position_name_en']) ?>">
        </div>

        <div class="form-group">
            <label for="position_level">ระดับตำแหน่ง</label>
            <input type="text" id="position_level" name="position_level"
                   value="<?= e((string) $data['position_level']) ?>">
            <?php if (isset($errors['position_level'])): ?>
                <p class="error"><?= e($errors['position_level']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="sort_order">ลำดับการแสดงผล</label>
            <input type="text" id="sort_order" name="sort_order" value="<?= e((string) $data['sort_order']) ?>">
            <?php if (isset($errors['sort_order'])): ?>
                <p class="error"><?= e($errors['sort_order']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?= $data['is_active'] ? 'checked' : '' ?>>
                เปิดใช้งาน
            </label>
        </div>

        <div class="form-group">
            <label for="remark">หมายเหตุ</label>
            <textarea id="remark" name="remark"><?= e($data['remark']) ?></textarea>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="positions.php" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
