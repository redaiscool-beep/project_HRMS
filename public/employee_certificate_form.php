<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$employee = fetchEmployeeOrRedirect($employeeId);

$certificateId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $certificateId !== null;

$data = [
    'certificate_name' => '',
    'certificate_no' => '',
    'issued_by' => '',
    'issue_date' => '',
    'expire_date' => '',
    'remark' => '',
];

$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM hr_employee_certificate WHERE certificate_id = :id AND employee_id = :emp_id');
    $stmt->execute(['id' => $certificateId, 'emp_id' => $employeeId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        header('Location: employee_certificates.php?employee_id=' . $employeeId);
        exit;
    }

    $data = $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    foreach (['certificate_name', 'certificate_no', 'issued_by', 'issue_date', 'expire_date', 'remark'] as $field) {
        $data[$field] = trim($_POST[$field] ?? '');
    }

    if ($data['certificate_name'] === '') {
        $errors['certificate_name'] = 'กรุณากรอกชื่อใบรับรอง';
    }

    if (!isValidDateOrEmpty($data['issue_date'])) {
        $errors['issue_date'] = 'รูปแบบวันที่ไม่ถูกต้อง';
    }

    if (!isValidDateOrEmpty($data['expire_date'])) {
        $errors['expire_date'] = 'รูปแบบวันที่ไม่ถูกต้อง';
    }

    if (empty($errors)) {
        $params = [
            'employee_id' => $employeeId,
            'certificate_name' => $data['certificate_name'],
            'certificate_no' => $data['certificate_no'] !== '' ? $data['certificate_no'] : null,
            'issued_by' => $data['issued_by'] !== '' ? $data['issued_by'] : null,
            'issue_date' => $data['issue_date'] !== '' ? $data['issue_date'] : null,
            'expire_date' => $data['expire_date'] !== '' ? $data['expire_date'] : null,
            'remark' => $data['remark'] !== '' ? $data['remark'] : null,
        ];

        if ($isEdit) {
            $params['id'] = $certificateId;
            $stmt = db()->prepare(
                'UPDATE hr_employee_certificate
                 SET certificate_name = :certificate_name, certificate_no = :certificate_no,
                     issued_by = :issued_by, issue_date = :issue_date, expire_date = :expire_date,
                     remark = :remark, updated_at = CURRENT_TIMESTAMP
                 WHERE certificate_id = :id AND employee_id = :employee_id'
            );
        } else {
            $stmt = db()->prepare(
                'INSERT INTO hr_employee_certificate
                    (employee_id, certificate_name, certificate_no, issued_by, issue_date, expire_date, remark)
                 VALUES
                    (:employee_id, :certificate_name, :certificate_no, :issued_by, :issue_date, :expire_date, :remark)'
            );
        }

        $stmt->execute($params);

        header('Location: employee_certificates.php?employee_id=' . $employeeId);
        exit;
    }
}

$fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$pageTitle = $isEdit ? 'แก้ไขใบรับรอง' : 'เพิ่มใบรับรอง';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $fullName, 'Certificate', $pageTitle];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><?= e($pageTitle) ?></h1>
<p class="page-description"><?= e($employee['employee_code']) ?> — <?= e($fullName) ?></p>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend>ข้อมูลใบรับรอง</legend>

        <div class="form-group">
            <label for="certificate_name">ชื่อใบรับรอง *</label>
            <input type="text" id="certificate_name" name="certificate_name" value="<?= e($data['certificate_name']) ?>" required>
            <?php if (isset($errors['certificate_name'])): ?><p class="error"><?= e($errors['certificate_name']) ?></p><?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="certificate_no">เลขที่ใบรับรอง</label>
                <input type="text" id="certificate_no" name="certificate_no" value="<?= e($data['certificate_no']) ?>">
            </div>
            <div class="form-group">
                <label for="issued_by">ออกโดย</label>
                <input type="text" id="issued_by" name="issued_by" value="<?= e($data['issued_by']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="issue_date">วันที่ออก</label>
                <input type="date" id="issue_date" name="issue_date" value="<?= e($data['issue_date']) ?>">
                <?php if (isset($errors['issue_date'])): ?><p class="error"><?= e($errors['issue_date']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="expire_date">วันหมดอายุ (เว้นว่างถ้าไม่มี)</label>
                <input type="date" id="expire_date" name="expire_date" value="<?= e($data['expire_date']) ?>">
                <?php if (isset($errors['expire_date'])): ?><p class="error"><?= e($errors['expire_date']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="remark">หมายเหตุ</label>
            <textarea id="remark" name="remark"><?= e($data['remark']) ?></textarea>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="employee_certificates.php?employee_id=<?= $employeeId ?>" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="button" class="btn" data-clear-form><i class="bi bi-eraser"></i> ล้าง</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
