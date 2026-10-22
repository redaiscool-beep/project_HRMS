<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$employee = fetchEmployeeOrRedirect($employeeId);

$skillId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $skillId !== null;

$data = [
    'skill_name' => '',
    'skill_level' => '',
    'experience_year' => '',
    'remark' => '',
];

$errors = [];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM hr_employee_skill WHERE skill_id = :id AND employee_id = :emp_id');
    $stmt->execute(['id' => $skillId, 'emp_id' => $employeeId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        header('Location: employee_skills.php?employee_id=' . $employeeId);
        exit;
    }

    $data = $existing;
}

$skillLevels = ['BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $data['skill_name'] = trim($_POST['skill_name'] ?? '');
    $data['skill_level'] = trim($_POST['skill_level'] ?? '');
    $data['experience_year'] = trim($_POST['experience_year'] ?? '');
    $data['remark'] = trim($_POST['remark'] ?? '');

    if ($data['skill_name'] === '') {
        $errors['skill_name'] = 'กรุณากรอกชื่อทักษะ';
    }

    if ($data['skill_level'] !== '' && !in_array($data['skill_level'], $skillLevels, true)) {
        $errors['skill_level'] = 'ระดับทักษะไม่ถูกต้อง';
    }

    if ($data['experience_year'] !== '' && !ctype_digit($data['experience_year'])) {
        $errors['experience_year'] = 'ต้องเป็นตัวเลข';
    }

    if (empty($errors)) {
        $params = [
            'employee_id' => $employeeId,
            'skill_name' => $data['skill_name'],
            'skill_level' => $data['skill_level'] !== '' ? $data['skill_level'] : null,
            'experience_year' => $data['experience_year'] !== '' ? (int) $data['experience_year'] : null,
            'remark' => $data['remark'] !== '' ? $data['remark'] : null,
        ];

        if ($isEdit) {
            $params['id'] = $skillId;
            $stmt = db()->prepare(
                'UPDATE hr_employee_skill
                 SET skill_name = :skill_name, skill_level = :skill_level,
                     experience_year = :experience_year, remark = :remark, updated_at = CURRENT_TIMESTAMP
                 WHERE skill_id = :id AND employee_id = :employee_id'
            );
        } else {
            $stmt = db()->prepare(
                'INSERT INTO hr_employee_skill (employee_id, skill_name, skill_level, experience_year, remark)
                 VALUES (:employee_id, :skill_name, :skill_level, :experience_year, :remark)'
            );
        }

        $stmt->execute($params);

        header('Location: employee_skills.php?employee_id=' . $employeeId);
        exit;
    }
}

$fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$pageTitle = $isEdit ? 'แก้ไขทักษะ' : 'เพิ่มทักษะ';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $fullName, 'Skill', $pageTitle];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><?= e($pageTitle) ?></h1>
<p class="page-description"><?= e($employee['employee_code']) ?> — <?= e($fullName) ?></p>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend>ข้อมูลทักษะ</legend>

        <div class="form-row">
            <div class="form-group">
                <label for="skill_name">ชื่อทักษะ *</label>
                <input type="text" id="skill_name" name="skill_name" value="<?= e($data['skill_name']) ?>" required>
                <?php if (isset($errors['skill_name'])): ?><p class="error"><?= e($errors['skill_name']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="skill_level">ระดับ</label>
                <select id="skill_level" name="skill_level">
                    <option value="">— ไม่ระบุ —</option>
                    <?php foreach ($skillLevels as $level): ?>
                        <option value="<?= $level ?>" <?= $data['skill_level'] === $level ? 'selected' : '' ?>><?= $level ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['skill_level'])): ?><p class="error"><?= e($errors['skill_level']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="experience_year">ประสบการณ์ (ปี)</label>
            <input type="text" id="experience_year" name="experience_year" value="<?= e((string) $data['experience_year']) ?>">
            <?php if (isset($errors['experience_year'])): ?><p class="error"><?= e($errors['experience_year']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="remark">หมายเหตุ</label>
            <textarea id="remark" name="remark"><?= e($data['remark']) ?></textarea>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="employee_skills.php?employee_id=<?= $employeeId ?>" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
