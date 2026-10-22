<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? (int) $_GET['employee_id'] : null;
$trainingId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $trainingId !== null;

$data = [
    'employee_id' => $employeeId ?? '',
    'course_name' => '',
    'training_provider' => '',
    'training_type' => '',
    'start_date' => '',
    'end_date' => '',
    'duration' => '',
    'duration_unit' => 'HOUR',
    'certificate_received' => false,
    'remark' => '',
];

$errors = [];
$employee = null;

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM hr_employee_training WHERE training_id = :id');
    $stmt->execute(['id' => $trainingId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        header('Location: employee_training.php');
        exit;
    }

    $data = $existing;
    $employeeId = (int) $existing['employee_id'];
}

if ($employeeId) {
    $employee = fetchEmployeeOrRedirect($employeeId);
}

$employeeOptions = $employee ? [] : db()->query(
    "SELECT employee_id, employee_code, first_name, last_name FROM hr_employee ORDER BY employee_code"
)->fetchAll();

$durationUnits = ['HOUR', 'DAY', 'MONTH'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    if (!$employee) {
        $data['employee_id'] = trim($_POST['employee_id'] ?? '');
    }
    $data['course_name'] = trim($_POST['course_name'] ?? '');
    $data['training_provider'] = trim($_POST['training_provider'] ?? '');
    $data['training_type'] = trim($_POST['training_type'] ?? '');
    $data['start_date'] = trim($_POST['start_date'] ?? '');
    $data['end_date'] = trim($_POST['end_date'] ?? '');
    $data['duration'] = trim($_POST['duration'] ?? '');
    $data['duration_unit'] = trim($_POST['duration_unit'] ?? 'HOUR');
    $data['certificate_received'] = isset($_POST['certificate_received']);
    $data['remark'] = trim($_POST['remark'] ?? '');

    $targetEmployeeId = $employee ? $employeeId : (int) $data['employee_id'];

    if (!$targetEmployeeId) {
        $errors['employee_id'] = 'กรุณาเลือกพนักงาน';
    }

    if ($data['course_name'] === '') {
        $errors['course_name'] = 'กรุณากรอกชื่อหลักสูตร';
    }

    if (!isValidDateOrEmpty($data['start_date'])) {
        $errors['start_date'] = 'รูปแบบวันที่ไม่ถูกต้อง';
    }

    if (!isValidDateOrEmpty($data['end_date'])) {
        $errors['end_date'] = 'รูปแบบวันที่ไม่ถูกต้อง';
    }

    if ($data['duration'] !== '' && !is_numeric($data['duration'])) {
        $errors['duration'] = 'ระยะเวลาต้องเป็นตัวเลข';
    }

    if ($data['duration_unit'] !== '' && !in_array($data['duration_unit'], $durationUnits, true)) {
        $errors['duration_unit'] = 'หน่วยระยะเวลาไม่ถูกต้อง';
    }

    if (empty($errors)) {
        $params = [
            'employee_id' => $targetEmployeeId,
            'course_name' => $data['course_name'],
            'training_provider' => $data['training_provider'] !== '' ? $data['training_provider'] : null,
            'training_type' => $data['training_type'] !== '' ? $data['training_type'] : null,
            'start_date' => $data['start_date'] !== '' ? $data['start_date'] : null,
            'end_date' => $data['end_date'] !== '' ? $data['end_date'] : null,
            'duration' => $data['duration'] !== '' ? (float) $data['duration'] : null,
            'duration_unit' => $data['duration_unit'] !== '' ? $data['duration_unit'] : null,
            'certificate_received' => $data['certificate_received'] ? 'true' : 'false',
            'remark' => $data['remark'] !== '' ? $data['remark'] : null,
        ];

        if ($isEdit) {
            $params['id'] = $trainingId;
            $stmt = db()->prepare(
                'UPDATE hr_employee_training
                 SET course_name = :course_name, training_provider = :training_provider,
                     training_type = :training_type, start_date = :start_date, end_date = :end_date,
                     duration = :duration, duration_unit = :duration_unit,
                     certificate_received = :certificate_received, remark = :remark,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE training_id = :id'
            );
        } else {
            $stmt = db()->prepare(
                'INSERT INTO hr_employee_training
                    (employee_id, course_name, training_provider, training_type, start_date, end_date,
                     duration, duration_unit, certificate_received, remark)
                 VALUES
                    (:employee_id, :course_name, :training_provider, :training_type, :start_date, :end_date,
                     :duration, :duration_unit, :certificate_received, :remark)'
            );
        }

        $stmt->execute($params);

        header('Location: employee_training.php?employee_id=' . $targetEmployeeId);
        exit;
    }
}

$pageTitle = $isEdit ? 'แก้ไขการอบรม' : 'เพิ่มการอบรม';
$activeMenu = 'trainings';
$breadcrumb = $employee
    ? ['Employee', 'Employee List', trim($employee['first_name'] . ' ' . $employee['last_name']), 'Training', $pageTitle]
    : ['Employee', 'Training', $pageTitle];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><span class="page-title-icon icon-indigo"><i class="bi bi-mortarboard"></i></span><?= e($pageTitle) ?></h1>
<?php if ($employee): ?>
    <p class="page-description"><?= e($employee['employee_code']) ?> — <?= e(trim($employee['first_name'] . ' ' . $employee['last_name'])) ?></p>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend>ข้อมูลการอบรม</legend>

        <?php if ($employee): ?>
            <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
        <?php else: ?>
            <div class="form-group">
                <label for="employee_id">พนักงาน *</label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">— เลือกพนักงาน —</option>
                    <?php foreach ($employeeOptions as $opt): ?>
                        <option value="<?= (int) $opt['employee_id'] ?>" <?= (string) $opt['employee_id'] === (string) $data['employee_id'] ? 'selected' : '' ?>>
                            <?= e($opt['employee_code']) ?> — <?= e($opt['first_name'] . ' ' . $opt['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['employee_id'])): ?><p class="error"><?= e($errors['employee_id']) ?></p><?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="course_name">ชื่อหลักสูตร *</label>
            <input type="text" id="course_name" name="course_name" value="<?= e($data['course_name']) ?>" required>
            <?php if (isset($errors['course_name'])): ?><p class="error"><?= e($errors['course_name']) ?></p><?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="training_provider">ผู้จัดอบรม</label>
                <input type="text" id="training_provider" name="training_provider" value="<?= e($data['training_provider']) ?>">
            </div>
            <div class="form-group">
                <label for="training_type">ประเภทการอบรม</label>
                <input type="text" id="training_type" name="training_type" value="<?= e($data['training_type']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date">วันที่เริ่ม</label>
                <input type="date" id="start_date" name="start_date" value="<?= e($data['start_date']) ?>">
                <?php if (isset($errors['start_date'])): ?><p class="error"><?= e($errors['start_date']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="end_date">วันที่สิ้นสุด</label>
                <input type="date" id="end_date" name="end_date" value="<?= e($data['end_date']) ?>">
                <?php if (isset($errors['end_date'])): ?><p class="error"><?= e($errors['end_date']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="duration">ระยะเวลา</label>
                <input type="text" id="duration" name="duration" value="<?= e((string) $data['duration']) ?>">
                <?php if (isset($errors['duration'])): ?><p class="error"><?= e($errors['duration']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="duration_unit">หน่วย</label>
                <select id="duration_unit" name="duration_unit">
                    <?php foreach ($durationUnits as $unit): ?>
                        <option value="<?= $unit ?>" <?= $data['duration_unit'] === $unit ? 'selected' : '' ?>><?= $unit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="certificate_received" <?= $data['certificate_received'] ? 'checked' : '' ?>>
                ได้รับใบรับรอง
            </label>
        </div>

        <div class="form-group">
            <label for="remark">หมายเหตุ</label>
            <textarea id="remark" name="remark"><?= e($data['remark']) ?></textarea>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="employee_training.php<?= $employeeId ? '?employee_id=' . $employeeId : '' ?>" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
