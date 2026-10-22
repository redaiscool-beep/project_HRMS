<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$employee = fetchEmployeeOrRedirect($employeeId);

$educationId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $educationId !== null;

$data = [
    'education_level' => '',
    'degree_name' => '',
    'major_name' => '',
    'faculty_name' => '',
    'institute_name' => '',
    'start_year' => '',
    'graduation_year' => '',
    'gpa' => '',
    'remark' => '',
];

$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM hr_employee_education WHERE education_id = :id AND employee_id = :emp_id');
    $stmt->execute(['id' => $educationId, 'emp_id' => $employeeId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        header('Location: employee_education.php?employee_id=' . $employeeId);
        exit;
    }

    $data = $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    foreach (['education_level', 'degree_name', 'major_name', 'faculty_name', 'institute_name', 'remark'] as $field) {
        $data[$field] = trim($_POST[$field] ?? '');
    }
    $data['start_year'] = trim($_POST['start_year'] ?? '');
    $data['graduation_year'] = trim($_POST['graduation_year'] ?? '');
    $data['gpa'] = trim($_POST['gpa'] ?? '');

    if ($data['education_level'] === '') {
        $errors['education_level'] = 'กรุณาเลือกระดับการศึกษา';
    }

    if ($data['institute_name'] === '') {
        $errors['institute_name'] = 'กรุณากรอกชื่อสถาบัน';
    }

    foreach (['start_year', 'graduation_year'] as $yearField) {
        if ($data[$yearField] !== '' && !ctype_digit($data[$yearField])) {
            $errors[$yearField] = 'ต้องเป็นตัวเลข';
        }
    }

    if ($data['gpa'] !== '' && !is_numeric($data['gpa'])) {
        $errors['gpa'] = 'GPA ต้องเป็นตัวเลข';
    }

    if (empty($errors)) {
        $params = [
            'employee_id' => $employeeId,
            'education_level' => $data['education_level'],
            'degree_name' => $data['degree_name'] !== '' ? $data['degree_name'] : null,
            'major_name' => $data['major_name'] !== '' ? $data['major_name'] : null,
            'faculty_name' => $data['faculty_name'] !== '' ? $data['faculty_name'] : null,
            'institute_name' => $data['institute_name'],
            'start_year' => $data['start_year'] !== '' ? (int) $data['start_year'] : null,
            'graduation_year' => $data['graduation_year'] !== '' ? (int) $data['graduation_year'] : null,
            'gpa' => $data['gpa'] !== '' ? (float) $data['gpa'] : null,
            'remark' => $data['remark'] !== '' ? $data['remark'] : null,
        ];

        if ($isEdit) {
            $params['id'] = $educationId;
            $stmt = db()->prepare(
                'UPDATE hr_employee_education
                 SET education_level = :education_level, degree_name = :degree_name, major_name = :major_name,
                     faculty_name = :faculty_name, institute_name = :institute_name,
                     start_year = :start_year, graduation_year = :graduation_year, gpa = :gpa,
                     remark = :remark, updated_at = CURRENT_TIMESTAMP
                 WHERE education_id = :id AND employee_id = :employee_id'
            );
        } else {
            $stmt = db()->prepare(
                'INSERT INTO hr_employee_education
                    (employee_id, education_level, degree_name, major_name, faculty_name, institute_name,
                     start_year, graduation_year, gpa, remark)
                 VALUES
                    (:employee_id, :education_level, :degree_name, :major_name, :faculty_name, :institute_name,
                     :start_year, :graduation_year, :gpa, :remark)'
            );
        }

        $stmt->execute($params);

        header('Location: employee_education.php?employee_id=' . $employeeId);
        exit;
    }
}

$fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$pageTitle = $isEdit ? 'แก้ไขประวัติการศึกษา' : 'เพิ่มประวัติการศึกษา';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $fullName, 'Education', $pageTitle];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><?= e($pageTitle) ?></h1>
<p class="page-description"><?= e($employee['employee_code']) ?> — <?= e($fullName) ?></p>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend>ข้อมูลการศึกษา</legend>

        <div class="form-row">
            <div class="form-group">
                <label for="education_level">ระดับการศึกษา *</label>
                <select id="education_level" name="education_level" required>
                    <option value="">— เลือก —</option>
                    <?php foreach (['มัธยมศึกษา', 'ปวช.', 'ปวส.', 'ปริญญาตรี', 'ปริญญาโท', 'ปริญญาเอก'] as $level): ?>
                        <option value="<?= $level ?>" <?= $data['education_level'] === $level ? 'selected' : '' ?>><?= $level ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['education_level'])): ?><p class="error"><?= e($errors['education_level']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="institute_name">สถาบันการศึกษา *</label>
                <input type="text" id="institute_name" name="institute_name" value="<?= e($data['institute_name']) ?>" required>
                <?php if (isset($errors['institute_name'])): ?><p class="error"><?= e($errors['institute_name']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="degree_name">วุฒิการศึกษา</label>
                <input type="text" id="degree_name" name="degree_name" value="<?= e($data['degree_name']) ?>">
            </div>
            <div class="form-group">
                <label for="major_name">สาขาวิชา</label>
                <input type="text" id="major_name" name="major_name" value="<?= e($data['major_name']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="faculty_name">คณะ</label>
                <input type="text" id="faculty_name" name="faculty_name" value="<?= e($data['faculty_name']) ?>">
            </div>
            <div class="form-group">
                <label for="gpa">GPA</label>
                <input type="text" id="gpa" name="gpa" value="<?= e((string) $data['gpa']) ?>">
                <?php if (isset($errors['gpa'])): ?><p class="error"><?= e($errors['gpa']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_year">ปีที่เริ่มศึกษา (พ.ศ.)</label>
                <input type="text" id="start_year" name="start_year" value="<?= e((string) $data['start_year']) ?>">
                <?php if (isset($errors['start_year'])): ?><p class="error"><?= e($errors['start_year']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="graduation_year">ปีที่จบการศึกษา (พ.ศ.)</label>
                <input type="text" id="graduation_year" name="graduation_year" value="<?= e((string) $data['graduation_year']) ?>">
                <?php if (isset($errors['graduation_year'])): ?><p class="error"><?= e($errors['graduation_year']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="remark">หมายเหตุ</label>
            <textarea id="remark" name="remark"><?= e($data['remark']) ?></textarea>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="employee_education.php?employee_id=<?= $employeeId ?>" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
