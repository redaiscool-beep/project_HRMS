<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$employee = fetchEmployeeOrRedirect($employeeId);

$workHistoryId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $workHistoryId !== null;

$data = [
    'company_name' => '',
    'position_name' => '',
    'start_date' => '',
    'end_date' => '',
    'salary' => '',
    'job_description' => '',
    'reason_for_leaving' => '',
    'remark' => '',
];

$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM hr_employee_work_history WHERE work_history_id = :id AND employee_id = :emp_id');
    $stmt->execute(['id' => $workHistoryId, 'emp_id' => $employeeId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        header('Location: employee_work_history.php?employee_id=' . $employeeId);
        exit;
    }

    $data = $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    foreach (['company_name', 'position_name', 'start_date', 'end_date', 'salary', 'job_description', 'reason_for_leaving', 'remark'] as $field) {
        $data[$field] = trim($_POST[$field] ?? '');
    }

    if ($data['company_name'] === '') {
        $errors['company_name'] = 'กรุณากรอกชื่อบริษัท';
    }

    if (!isValidDateOrEmpty($data['start_date'])) {
        $errors['start_date'] = 'รูปแบบวันที่ไม่ถูกต้อง';
    }

    if (!isValidDateOrEmpty($data['end_date'])) {
        $errors['end_date'] = 'รูปแบบวันที่ไม่ถูกต้อง';
    }

    if ($data['salary'] !== '' && !is_numeric($data['salary'])) {
        $errors['salary'] = 'เงินเดือนต้องเป็นตัวเลข';
    }

    if (empty($errors)) {
        $params = [
            'employee_id' => $employeeId,
            'company_name' => $data['company_name'],
            'position_name' => $data['position_name'] !== '' ? $data['position_name'] : null,
            'start_date' => $data['start_date'] !== '' ? $data['start_date'] : null,
            'end_date' => $data['end_date'] !== '' ? $data['end_date'] : null,
            'salary' => $data['salary'] !== '' ? (float) $data['salary'] : null,
            'job_description' => $data['job_description'] !== '' ? $data['job_description'] : null,
            'reason_for_leaving' => $data['reason_for_leaving'] !== '' ? $data['reason_for_leaving'] : null,
            'remark' => $data['remark'] !== '' ? $data['remark'] : null,
        ];

        if ($isEdit) {
            $params['id'] = $workHistoryId;
            $stmt = db()->prepare(
                'UPDATE hr_employee_work_history
                 SET company_name = :company_name, position_name = :position_name,
                     start_date = :start_date, end_date = :end_date, salary = :salary,
                     job_description = :job_description, reason_for_leaving = :reason_for_leaving,
                     remark = :remark, updated_at = CURRENT_TIMESTAMP
                 WHERE work_history_id = :id AND employee_id = :employee_id'
            );
        } else {
            $stmt = db()->prepare(
                'INSERT INTO hr_employee_work_history
                    (employee_id, company_name, position_name, start_date, end_date, salary, job_description, reason_for_leaving, remark)
                 VALUES
                    (:employee_id, :company_name, :position_name, :start_date, :end_date, :salary, :job_description, :reason_for_leaving, :remark)'
            );
        }

        $stmt->execute($params);

        header('Location: employee_work_history.php?employee_id=' . $employeeId);
        exit;
    }
}

$fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$pageTitle = $isEdit ? 'แก้ไขประวัติการทำงาน' : 'เพิ่มประวัติการทำงาน';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $fullName, 'Work History', $pageTitle];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><?= e($pageTitle) ?></h1>
<p class="page-description"><?= e($employee['employee_code']) ?> — <?= e($fullName) ?></p>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend>ข้อมูลการทำงาน</legend>

        <div class="form-row">
            <div class="form-group">
                <label for="company_name">ชื่อบริษัท *</label>
                <input type="text" id="company_name" name="company_name" value="<?= e($data['company_name']) ?>" required>
                <?php if (isset($errors['company_name'])): ?><p class="error"><?= e($errors['company_name']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="position_name">ตำแหน่ง</label>
                <input type="text" id="position_name" name="position_name" value="<?= e($data['position_name']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date">วันที่เริ่มงาน</label>
                <input type="date" id="start_date" name="start_date" value="<?= e($data['start_date']) ?>">
                <?php if (isset($errors['start_date'])): ?><p class="error"><?= e($errors['start_date']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="end_date">วันที่สิ้นสุด (เว้นว่างถ้ายังทำงานอยู่)</label>
                <input type="date" id="end_date" name="end_date" value="<?= e($data['end_date']) ?>">
                <?php if (isset($errors['end_date'])): ?><p class="error"><?= e($errors['end_date']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="salary">เงินเดือน</label>
            <input type="text" id="salary" name="salary" value="<?= e((string) $data['salary']) ?>">
            <?php if (isset($errors['salary'])): ?><p class="error"><?= e($errors['salary']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="job_description">ลักษณะงาน</label>
            <textarea id="job_description" name="job_description"><?= e($data['job_description']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="reason_for_leaving">เหตุผลที่ลาออก</label>
            <textarea id="reason_for_leaving" name="reason_for_leaving"><?= e($data['reason_for_leaving']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="remark">หมายเหตุ</label>
            <textarea id="remark" name="remark"><?= e($data['remark']) ?></textarea>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="employee_work_history.php?employee_id=<?= $employeeId ?>" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
