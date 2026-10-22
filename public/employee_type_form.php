<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeTypeId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $employeeTypeId !== null;

$data = [
    'employee_type_code' => '',
    'employee_type_name' => '',
    'is_active' => true,
    'remark' => '',
];

$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM hr_employee_type WHERE employee_type_id = :id');
    $stmt->execute(['id' => $employeeTypeId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        header('Location: employee_types.php');
        exit;
    }

    $data = $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $data['employee_type_code'] = trim($_POST['employee_type_code'] ?? '');
    $data['employee_type_name'] = trim($_POST['employee_type_name'] ?? '');
    $data['is_active'] = isset($_POST['is_active']);
    $data['remark'] = trim($_POST['remark'] ?? '');

    if ($data['employee_type_code'] === '') {
        $errors['employee_type_code'] = 'กรุณากรอกรหัสประเภทพนักงาน';
    }

    if ($data['employee_type_name'] === '') {
        $errors['employee_type_name'] = 'กรุณากรอกชื่อประเภทพนักงาน';
    }

    if ($data['employee_type_code'] !== '') {
        $dupStmt = db()->prepare(
            'SELECT employee_type_id FROM hr_employee_type
             WHERE employee_type_code = :code AND employee_type_id != :id'
        );
        $dupStmt->execute([
            'code' => $data['employee_type_code'],
            'id' => $employeeTypeId ?? 0,
        ]);

        if ($dupStmt->fetch()) {
            $errors['employee_type_code'] = 'รหัสนี้มีอยู่แล้ว';
        }
    }

    if (empty($errors)) {
        $params = [
            'code' => $data['employee_type_code'],
            'name' => $data['employee_type_name'],
            'is_active' => $data['is_active'] ? 'true' : 'false',
            'remark' => $data['remark'] !== '' ? $data['remark'] : null,
        ];

        if ($isEdit) {
            $params['id'] = $employeeTypeId;
            $stmt = db()->prepare(
                'UPDATE hr_employee_type
                 SET employee_type_code = :code,
                     employee_type_name = :name,
                     is_active = :is_active,
                     remark = :remark,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE employee_type_id = :id'
            );
        } else {
            $stmt = db()->prepare(
                'INSERT INTO hr_employee_type (employee_type_code, employee_type_name, is_active, remark)
                 VALUES (:code, :name, :is_active, :remark)'
            );
        }

        $stmt->execute($params);

        header('Location: employee_types.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'แก้ไขประเภทพนักงาน' : 'เพิ่มประเภทพนักงาน';
$activeMenu = 'employee_types';
$breadcrumb = ['Master Data', 'Employee Type', $pageTitle];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><span class="page-title-icon icon-pink"><i class="bi bi-tags"></i></span><?= e($pageTitle) ?></h1>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend>ข้อมูลประเภทพนักงาน</legend>

        <div class="form-group">
            <label for="employee_type_code">รหัสประเภทพนักงาน *</label>
            <input type="text" id="employee_type_code" name="employee_type_code"
                   value="<?= e($data['employee_type_code']) ?>" required>
            <?php if (isset($errors['employee_type_code'])): ?>
                <p class="error"><?= e($errors['employee_type_code']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="employee_type_name">ชื่อประเภทพนักงาน *</label>
            <input type="text" id="employee_type_name" name="employee_type_name"
                   value="<?= e($data['employee_type_name']) ?>" required>
            <?php if (isset($errors['employee_type_name'])): ?>
                <p class="error"><?= e($errors['employee_type_name']) ?></p>
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
        <a href="employee_types.php" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
