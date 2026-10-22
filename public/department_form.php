<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$departmentId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $departmentId !== null;

$data = [
    'department_code' => '',
    'department_name_th' => '',
    'department_name_en' => '',
    'parent_department_id' => '',
    'sort_order' => '0',
    'is_active' => true,
    'remark' => '',
];

$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM hr_department WHERE department_id = :id');
    $stmt->execute(['id' => $departmentId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        header('Location: departments.php');
        exit;
    }

    $data = $existing;
}

$departmentOptions = db()->query(
    'SELECT department_id, department_name_th FROM hr_department ORDER BY department_name_th'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $data['department_code'] = trim($_POST['department_code'] ?? '');
    $data['department_name_th'] = trim($_POST['department_name_th'] ?? '');
    $data['department_name_en'] = trim($_POST['department_name_en'] ?? '');
    $data['parent_department_id'] = $_POST['parent_department_id'] ?? '';
    $data['sort_order'] = trim($_POST['sort_order'] ?? '0');
    $data['is_active'] = isset($_POST['is_active']);
    $data['remark'] = trim($_POST['remark'] ?? '');

    if ($data['department_code'] === '') {
        $errors['department_code'] = 'กรุณากรอกรหัสแผนก';
    }

    if ($data['department_name_th'] === '') {
        $errors['department_name_th'] = 'กรุณากรอกชื่อแผนก (ไทย)';
    }

    if ($data['sort_order'] !== '' && !ctype_digit($data['sort_order'])) {
        $errors['sort_order'] = 'ลำดับต้องเป็นตัวเลข';
    }

    if ($data['department_code'] !== '') {
        $dupStmt = db()->prepare(
            'SELECT department_id FROM hr_department
             WHERE department_code = :code AND department_id != :id'
        );
        $dupStmt->execute([
            'code' => $data['department_code'],
            'id' => $departmentId ?? 0,
        ]);

        if ($dupStmt->fetch()) {
            $errors['department_code'] = 'รหัสแผนกนี้มีอยู่แล้ว';
        }
    }

    $parentId = $data['parent_department_id'] !== '' ? (int) $data['parent_department_id'] : null;

    if ($parentId !== null && $isEdit && $parentId === $departmentId) {
        $errors['parent_department_id'] = 'แผนกไม่สามารถเป็น parent ของตัวเองได้';
    }

    if (empty($errors)) {
        $params = [
            'code' => $data['department_code'],
            'name_th' => $data['department_name_th'],
            'name_en' => $data['department_name_en'] !== '' ? $data['department_name_en'] : null,
            'parent_id' => $parentId,
            'sort_order' => (int) $data['sort_order'],
            'is_active' => $data['is_active'] ? 'true' : 'false',
            'remark' => $data['remark'] !== '' ? $data['remark'] : null,
        ];

        if ($isEdit) {
            $params['id'] = $departmentId;
            $stmt = db()->prepare(
                'UPDATE hr_department
                 SET department_code = :code,
                     department_name_th = :name_th,
                     department_name_en = :name_en,
                     parent_department_id = :parent_id,
                     sort_order = :sort_order,
                     is_active = :is_active,
                     remark = :remark,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE department_id = :id'
            );
        } else {
            $stmt = db()->prepare(
                'INSERT INTO hr_department
                    (department_code, department_name_th, department_name_en, parent_department_id, sort_order, is_active, remark)
                 VALUES (:code, :name_th, :name_en, :parent_id, :sort_order, :is_active, :remark)'
            );
        }

        $stmt->execute($params);

        header('Location: departments.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'แก้ไขแผนก' : 'เพิ่มแผนกใหม่';
$activeMenu = 'departments';
$breadcrumb = ['Master Data', 'Department', $pageTitle];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><span class="page-title-icon icon-orange"><i class="bi bi-building"></i></span><?= e($pageTitle) ?></h1>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend>ข้อมูลแผนก</legend>

        <div class="form-group">
            <label for="department_code">รหัสแผนก *</label>
            <input type="text" id="department_code" name="department_code"
                   value="<?= e($data['department_code']) ?>" required>
            <?php if (isset($errors['department_code'])): ?>
                <p class="error"><?= e($errors['department_code']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="department_name_th">ชื่อแผนก (ไทย) *</label>
            <input type="text" id="department_name_th" name="department_name_th"
                   value="<?= e($data['department_name_th']) ?>" required>
            <?php if (isset($errors['department_name_th'])): ?>
                <p class="error"><?= e($errors['department_name_th']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="department_name_en">ชื่อแผนก (อังกฤษ)</label>
            <input type="text" id="department_name_en" name="department_name_en"
                   value="<?= e($data['department_name_en']) ?>">
        </div>

        <div class="form-group">
            <label for="parent_department_id">แผนกแม่ (Parent)</label>
            <select id="parent_department_id" name="parent_department_id">
                <option value="">— ไม่มี —</option>
                <?php foreach ($departmentOptions as $option): ?>
                    <?php if ($isEdit && (int) $option['department_id'] === $departmentId) {
                        continue;
                    } ?>
                    <option value="<?= (int) $option['department_id'] ?>"
                        <?= (string) $option['department_id'] === (string) $data['parent_department_id'] ? 'selected' : '' ?>>
                        <?= e($option['department_name_th']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['parent_department_id'])): ?>
                <p class="error"><?= e($errors['parent_department_id']) ?></p>
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
        <a href="departments.php" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
