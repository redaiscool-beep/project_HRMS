<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $employeeId !== null;

$data = [
    'employee_code' => '',
    'prefix' => '',
    'first_name' => '',
    'last_name' => '',
    'first_name_en' => '',
    'last_name_en' => '',
    'gender' => '',
    'birth_date' => '',
    'national_id' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'department_id' => '',
    'position_id' => '',
    'employee_type_id' => '',
    'hire_date' => '',
    'probation_end_date' => '',
    'employment_status' => 'PROBATION',
    'remark' => '',
];

$errors = [];
$currentDepartmentName = null;
$currentPositionName = null;

if ($isEdit) {
    $stmt = db()->prepare(
        'SELECT e.*, d.department_name_th, p.position_name_th
         FROM hr_employee e
         LEFT JOIN hr_department d ON d.department_id = e.department_id
         LEFT JOIN hr_position p ON p.position_id = e.position_id
         WHERE e.employee_id = :id'
    );
    $stmt->execute(['id' => $employeeId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        header('Location: employees.php');
        exit;
    }

    $currentDepartmentName = $existing['department_name_th'];
    $currentPositionName = $existing['position_name_th'];
    $data = array_merge($data, $existing);
}

$departments = db()->query('SELECT department_id, department_name_th FROM hr_department ORDER BY department_name_th')->fetchAll();
$positions = db()->query('SELECT position_id, position_name_th FROM hr_position ORDER BY position_name_th')->fetchAll();
$employeeTypes = db()->query('SELECT employee_type_id, employee_type_name FROM hr_employee_type ORDER BY employee_type_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $data['employee_code'] = trim($_POST['employee_code'] ?? '');
    $data['prefix'] = trim($_POST['prefix'] ?? '');
    $data['first_name'] = trim($_POST['first_name'] ?? '');
    $data['last_name'] = trim($_POST['last_name'] ?? '');
    $data['first_name_en'] = trim($_POST['first_name_en'] ?? '');
    $data['last_name_en'] = trim($_POST['last_name_en'] ?? '');
    $data['gender'] = trim($_POST['gender'] ?? '');
    $data['birth_date'] = trim($_POST['birth_date'] ?? '');
    $data['national_id'] = trim($_POST['national_id'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['phone'] = trim($_POST['phone'] ?? '');
    $data['address'] = trim($_POST['address'] ?? '');
    $data['employee_type_id'] = trim($_POST['employee_type_id'] ?? '');
    $data['probation_end_date'] = trim($_POST['probation_end_date'] ?? '');
    $data['remark'] = trim($_POST['remark'] ?? '');

    if (!$isEdit) {
        $data['department_id'] = trim($_POST['department_id'] ?? '');
        $data['position_id'] = trim($_POST['position_id'] ?? '');
        $data['hire_date'] = trim($_POST['hire_date'] ?? '');
        $data['employment_status'] = trim($_POST['employment_status'] ?? 'PROBATION');
    }

    if ($data['employee_code'] === '') {
        $errors['employee_code'] = 'กรุณากรอกรหัสพนักงาน';
    }

    if ($data['first_name'] === '') {
        $errors['first_name'] = 'กรุณากรอกชื่อ';
    }

    if ($data['last_name'] === '') {
        $errors['last_name'] = 'กรุณากรอกนามสกุล';
    }

    if ($data['employee_type_id'] === '') {
        $errors['employee_type_id'] = 'กรุณาเลือกประเภทพนักงาน';
    }

    if (!$isEdit) {
        if ($data['department_id'] === '') {
            $errors['department_id'] = 'กรุณาเลือกแผนก';
        }
        if ($data['position_id'] === '') {
            $errors['position_id'] = 'กรุณาเลือกตำแหน่ง';
        }
        if ($data['hire_date'] === '' || !isValidDateOrEmpty($data['hire_date'])) {
            $errors['hire_date'] = 'กรุณากรอกวันที่เริ่มงานให้ถูกต้อง (YYYY-MM-DD)';
        }
    }

    foreach (['birth_date', 'probation_end_date'] as $dateField) {
        if (!isValidDateOrEmpty($data[$dateField])) {
            $errors[$dateField] = 'รูปแบบวันที่ไม่ถูกต้อง (YYYY-MM-DD)';
        }
    }

    if ($data['employee_code'] !== '') {
        $dupStmt = db()->prepare(
            'SELECT employee_id FROM hr_employee WHERE employee_code = :code AND employee_id != :id'
        );
        $dupStmt->execute([
            'code' => $data['employee_code'],
            'id' => $employeeId ?? 0,
        ]);

        if ($dupStmt->fetch()) {
            $errors['employee_code'] = 'รหัสพนักงานนี้มีอยู่แล้ว';
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE hr_employee
                 SET employee_code = :code,
                     prefix = :prefix,
                     first_name = :first_name,
                     last_name = :last_name,
                     first_name_en = :first_name_en,
                     last_name_en = :last_name_en,
                     gender = :gender,
                     birth_date = :birth_date,
                     national_id = :national_id,
                     email = :email,
                     phone = :phone,
                     address = :address,
                     employee_type_id = :employee_type_id,
                     probation_end_date = :probation_end_date,
                     remark = :remark,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE employee_id = :id'
            );
            $stmt->execute([
                'code' => $data['employee_code'],
                'prefix' => $data['prefix'] !== '' ? $data['prefix'] : null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'first_name_en' => $data['first_name_en'] !== '' ? $data['first_name_en'] : null,
                'last_name_en' => $data['last_name_en'] !== '' ? $data['last_name_en'] : null,
                'gender' => $data['gender'] !== '' ? $data['gender'] : null,
                'birth_date' => $data['birth_date'] !== '' ? $data['birth_date'] : null,
                'national_id' => $data['national_id'] !== '' ? $data['national_id'] : null,
                'email' => $data['email'] !== '' ? $data['email'] : null,
                'phone' => $data['phone'] !== '' ? $data['phone'] : null,
                'address' => $data['address'] !== '' ? $data['address'] : null,
                'employee_type_id' => (int) $data['employee_type_id'],
                'probation_end_date' => $data['probation_end_date'] !== '' ? $data['probation_end_date'] : null,
                'remark' => $data['remark'] !== '' ? $data['remark'] : null,
                'id' => $employeeId,
            ]);

            header('Location: employee_detail.php?id=' . $employeeId);
            exit;
        }

        db()->beginTransaction();

        try {
            $insertStmt = db()->prepare(
                'INSERT INTO hr_employee
                    (employee_code, prefix, first_name, last_name, first_name_en, last_name_en,
                     gender, birth_date, national_id, email, phone, address,
                     department_id, position_id, employee_type_id, hire_date, probation_end_date,
                     employment_status, remark)
                 VALUES
                    (:code, :prefix, :first_name, :last_name, :first_name_en, :last_name_en,
                     :gender, :birth_date, :national_id, :email, :phone, :address,
                     :department_id, :position_id, :employee_type_id, :hire_date, :probation_end_date,
                     :employment_status, :remark)
                 RETURNING employee_id'
            );
            $insertStmt->execute([
                'code' => $data['employee_code'],
                'prefix' => $data['prefix'] !== '' ? $data['prefix'] : null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'first_name_en' => $data['first_name_en'] !== '' ? $data['first_name_en'] : null,
                'last_name_en' => $data['last_name_en'] !== '' ? $data['last_name_en'] : null,
                'gender' => $data['gender'] !== '' ? $data['gender'] : null,
                'birth_date' => $data['birth_date'] !== '' ? $data['birth_date'] : null,
                'national_id' => $data['national_id'] !== '' ? $data['national_id'] : null,
                'email' => $data['email'] !== '' ? $data['email'] : null,
                'phone' => $data['phone'] !== '' ? $data['phone'] : null,
                'address' => $data['address'] !== '' ? $data['address'] : null,
                'department_id' => (int) $data['department_id'],
                'position_id' => (int) $data['position_id'],
                'employee_type_id' => (int) $data['employee_type_id'],
                'hire_date' => $data['hire_date'],
                'probation_end_date' => $data['probation_end_date'] !== '' ? $data['probation_end_date'] : null,
                'employment_status' => $data['employment_status'],
                'remark' => $data['remark'] !== '' ? $data['remark'] : null,
            ]);

            $newEmployeeId = (int) $insertStmt->fetchColumn();

            $movementStmt = db()->prepare(
                "INSERT INTO hr_employee_movement
                    (employee_id, movement_type, effective_date, new_department_id, new_position_id,
                     new_employee_type_id, new_status, remark, created_by)
                 VALUES
                    (:employee_id, 'HIRE', :effective_date, :department_id, :position_id,
                     :employee_type_id, :status, 'บรรจุพนักงานใหม่', :created_by)"
            );
            $movementStmt->execute([
                'employee_id' => $newEmployeeId,
                'effective_date' => $data['hire_date'],
                'department_id' => (int) $data['department_id'],
                'position_id' => (int) $data['position_id'],
                'employee_type_id' => (int) $data['employee_type_id'],
                'status' => $data['employment_status'],
                'created_by' => currentUserId(),
            ]);

            db()->commit();
        } catch (Throwable $exception) {
            db()->rollBack();
            throw $exception;
        }

        header('Location: employee_detail.php?id=' . $newEmployeeId);
        exit;
    }
}

$pageTitle = $isEdit ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $pageTitle];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><span class="page-title-icon icon-blue"><i class="bi bi-people"></i></span><?= e($pageTitle) ?></h1>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend>ข้อมูลทั่วไป</legend>

        <div class="form-row">
            <div class="form-group">
                <label for="employee_code">รหัสพนักงาน *</label>
                <input type="text" id="employee_code" name="employee_code" value="<?= e($data['employee_code']) ?>" required>
                <?php if (isset($errors['employee_code'])): ?><p class="error"><?= e($errors['employee_code']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="prefix">คำนำหน้า</label>
                <input type="text" id="prefix" name="prefix" value="<?= e($data['prefix']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">ชื่อ *</label>
                <input type="text" id="first_name" name="first_name" value="<?= e($data['first_name']) ?>" required>
                <?php if (isset($errors['first_name'])): ?><p class="error"><?= e($errors['first_name']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="last_name">นามสกุล *</label>
                <input type="text" id="last_name" name="last_name" value="<?= e($data['last_name']) ?>" required>
                <?php if (isset($errors['last_name'])): ?><p class="error"><?= e($errors['last_name']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name_en">First Name (EN)</label>
                <input type="text" id="first_name_en" name="first_name_en" value="<?= e($data['first_name_en']) ?>">
            </div>
            <div class="form-group">
                <label for="last_name_en">Last Name (EN)</label>
                <input type="text" id="last_name_en" name="last_name_en" value="<?= e($data['last_name_en']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="gender">เพศ</label>
                <select id="gender" name="gender">
                    <option value="">— ไม่ระบุ —</option>
                    <option value="ชาย" <?= $data['gender'] === 'ชาย' ? 'selected' : '' ?>>ชาย</option>
                    <option value="หญิง" <?= $data['gender'] === 'หญิง' ? 'selected' : '' ?>>หญิง</option>
                    <option value="อื่นๆ" <?= $data['gender'] === 'อื่นๆ' ? 'selected' : '' ?>>อื่นๆ</option>
                </select>
            </div>
            <div class="form-group">
                <label for="birth_date">วันเกิด</label>
                <input type="date" id="birth_date" name="birth_date" value="<?= e($data['birth_date']) ?>">
                <?php if (isset($errors['birth_date'])): ?><p class="error"><?= e($errors['birth_date']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="national_id">เลขบัตรประชาชน</label>
                <input type="text" id="national_id" name="national_id" value="<?= e($data['national_id']) ?>">
            </div>
            <div class="form-group">
                <label for="employee_type_id">ประเภทพนักงาน *</label>
                <select id="employee_type_id" name="employee_type_id" required>
                    <option value="">— เลือก —</option>
                    <?php foreach ($employeeTypes as $type): ?>
                        <option value="<?= (int) $type['employee_type_id'] ?>" <?= (string) $type['employee_type_id'] === (string) $data['employee_type_id'] ? 'selected' : '' ?>>
                            <?= e($type['employee_type_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['employee_type_id'])): ?><p class="error"><?= e($errors['employee_type_id']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" value="<?= e($data['email']) ?>">
            </div>
            <div class="form-group">
                <label for="phone">เบอร์โทร</label>
                <input type="text" id="phone" name="phone" value="<?= e($data['phone']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="address">ที่อยู่</label>
            <textarea id="address" name="address"><?= e($data['address']) ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>ข้อมูลการจ้างงาน</legend>

        <?php if ($isEdit): ?>
            <div class="alert alert-success" style="background:var(--blue-50); color: var(--blue-800);">
                แผนก / ตำแหน่ง / สถานะการจ้างงาน เปลี่ยนแปลงได้ผ่านหน้า
                <a href="employee_movement_form.php?employee_id=<?= $employeeId ?>">บันทึกการเปลี่ยนแปลง (Movement)</a>
                เท่านั้น เพื่อรักษาประวัติย้อนหลัง
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>แผนกปัจจุบัน</label>
                    <input type="text" value="<?= e($currentDepartmentName ?? '-') ?>" disabled>
                </div>
                <div class="form-group">
                    <label>ตำแหน่งปัจจุบัน</label>
                    <input type="text" value="<?= e($currentPositionName ?? '-') ?>" disabled>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>สถานะปัจจุบัน</label>
                    <div><?= employmentStatusBadge($data['employment_status']) ?></div>
                </div>
                <div class="form-group">
                    <label>วันที่เริ่มงาน</label>
                    <input type="text" value="<?= e($data['hire_date']) ?>" disabled>
                </div>
            </div>
        <?php else: ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="department_id">แผนก *</label>
                    <select id="department_id" name="department_id" required>
                        <option value="">— เลือก —</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int) $dept['department_id'] ?>" <?= (string) $dept['department_id'] === (string) $data['department_id'] ? 'selected' : '' ?>>
                                <?= e($dept['department_name_th']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['department_id'])): ?><p class="error"><?= e($errors['department_id']) ?></p><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="position_id">ตำแหน่ง *</label>
                    <select id="position_id" name="position_id" required>
                        <option value="">— เลือก —</option>
                        <?php foreach ($positions as $pos): ?>
                            <option value="<?= (int) $pos['position_id'] ?>" <?= (string) $pos['position_id'] === (string) $data['position_id'] ? 'selected' : '' ?>>
                                <?= e($pos['position_name_th']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['position_id'])): ?><p class="error"><?= e($errors['position_id']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="hire_date">วันที่เริ่มงาน *</label>
                    <input type="date" id="hire_date" name="hire_date" value="<?= e($data['hire_date']) ?>" required>
                    <?php if (isset($errors['hire_date'])): ?><p class="error"><?= e($errors['hire_date']) ?></p><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="employment_status">สถานะเริ่มต้น *</label>
                    <select id="employment_status" name="employment_status" required>
                        <?php foreach (['PROBATION', 'ACTIVE'] as $status): ?>
                            <option value="<?= $status ?>" <?= $data['employment_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="probation_end_date">วันสิ้นสุดทดลองงาน</label>
            <input type="date" id="probation_end_date" name="probation_end_date" value="<?= e($data['probation_end_date']) ?>">
            <?php if (isset($errors['probation_end_date'])): ?><p class="error"><?= e($errors['probation_end_date']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="remark">หมายเหตุ</label>
            <textarea id="remark" name="remark"><?= e($data['remark']) ?></textarea>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="<?= $isEdit ? 'employee_detail.php?id=' . $employeeId : 'employees.php' ?>" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
