<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$MOVEMENT_TYPES = ['PASS_PROBATION', 'TRANSFER', 'POSITION_CHANGE', 'PROMOTION', 'STATUS_CHANGE', 'RESIGN'];

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : null;

$errors = [];
$movementType = $_POST['movement_type'] ?? '';
$effectiveDate = trim($_POST['effective_date'] ?? date('Y-m-d'));
$newDepartmentId = $_POST['new_department_id'] ?? '';
$newPositionId = $_POST['new_position_id'] ?? '';
$newStatus = $_POST['new_status'] ?? '';
$remark = trim($_POST['remark'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
}

if (!$employeeId) {
    header('Location: employees.php');
    exit;
}

$empStmt = db()->prepare(
    'SELECT e.*, d.department_name_th, p.position_name_th
     FROM hr_employee e
     LEFT JOIN hr_department d ON d.department_id = e.department_id
     LEFT JOIN hr_position p ON p.position_id = e.position_id
     WHERE e.employee_id = :id'
);
$empStmt->execute(['id' => $employeeId]);
$employee = $empStmt->fetch();

if (!$employee) {
    header('Location: employees.php');
    exit;
}

$departments = db()->query('SELECT department_id, department_name_th FROM hr_department WHERE is_active = true ORDER BY department_name_th')->fetchAll();
$positions = db()->query('SELECT position_id, position_name_th FROM hr_position WHERE is_active = true ORDER BY position_name_th')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    if (!in_array($movementType, $MOVEMENT_TYPES, true)) {
        $errors['movement_type'] = 'กรุณาเลือกประเภทการเปลี่ยนแปลง';
    }

    if ($effectiveDate === '') {
        $errors['effective_date'] = 'กรุณากรอกวันที่มีผล';
    }

    $updateFields = [];
    $movementColumns = [
        'old_department_id' => null, 'new_department_id' => null,
        'old_position_id' => null, 'new_position_id' => null,
        'old_status' => null, 'new_status' => null,
    ];

    if ($movementType === 'TRANSFER') {
        if ($newDepartmentId === '') {
            $errors['new_department_id'] = 'กรุณาเลือกแผนกใหม่';
        } elseif ((int) $newDepartmentId === (int) $employee['department_id']) {
            $errors['new_department_id'] = 'แผนกใหม่ต้องต่างจากแผนกปัจจุบัน';
        } else {
            $movementColumns['old_department_id'] = (int) $employee['department_id'];
            $movementColumns['new_department_id'] = (int) $newDepartmentId;
            $updateFields['department_id'] = (int) $newDepartmentId;
        }
    } elseif (in_array($movementType, ['POSITION_CHANGE', 'PROMOTION'], true)) {
        if ($newPositionId === '') {
            $errors['new_position_id'] = 'กรุณาเลือกตำแหน่งใหม่';
        } elseif ((int) $newPositionId === (int) $employee['position_id']) {
            $errors['new_position_id'] = 'ตำแหน่งใหม่ต้องต่างจากตำแหน่งปัจจุบัน';
        } else {
            $movementColumns['old_position_id'] = (int) $employee['position_id'];
            $movementColumns['new_position_id'] = (int) $newPositionId;
            $updateFields['position_id'] = (int) $newPositionId;
        }
    } elseif ($movementType === 'STATUS_CHANGE') {
        if (!in_array($newStatus, ['ACTIVE', 'PROBATION', 'INACTIVE'], true)) {
            $errors['new_status'] = 'กรุณาเลือกสถานะใหม่';
        } elseif ($newStatus === $employee['employment_status']) {
            $errors['new_status'] = 'สถานะใหม่ต้องต่างจากสถานะปัจจุบัน';
        } else {
            $movementColumns['old_status'] = $employee['employment_status'];
            $movementColumns['new_status'] = $newStatus;
            $updateFields['employment_status'] = $newStatus;
        }
    } elseif ($movementType === 'PASS_PROBATION') {
        if ($employee['employment_status'] !== 'PROBATION') {
            $errors['movement_type'] = 'พนักงานคนนี้ไม่ได้อยู่ในสถานะทดลองงาน';
        } else {
            $movementColumns['old_status'] = 'PROBATION';
            $movementColumns['new_status'] = 'ACTIVE';
            $updateFields['employment_status'] = 'ACTIVE';
        }
    } elseif ($movementType === 'RESIGN') {
        if ($employee['employment_status'] === 'RESIGNED') {
            $errors['movement_type'] = 'พนักงานคนนี้ลาออกไปแล้ว';
        } else {
            $movementColumns['old_status'] = $employee['employment_status'];
            $movementColumns['new_status'] = 'RESIGNED';
            $updateFields['employment_status'] = 'RESIGNED';
            $updateFields['resign_date'] = $effectiveDate;
        }
    }

    if (empty($errors)) {
        db()->beginTransaction();

        try {
            $movementStmt = db()->prepare(
                'INSERT INTO hr_employee_movement
                    (employee_id, movement_type, effective_date,
                     old_department_id, new_department_id,
                     old_position_id, new_position_id,
                     old_status, new_status,
                     remark, created_by)
                 VALUES
                    (:employee_id, :movement_type, :effective_date,
                     :old_department_id, :new_department_id,
                     :old_position_id, :new_position_id,
                     :old_status, :new_status,
                     :remark, :created_by)'
            );
            $movementStmt->execute([
                'employee_id' => $employeeId,
                'movement_type' => $movementType,
                'effective_date' => $effectiveDate,
                'old_department_id' => $movementColumns['old_department_id'],
                'new_department_id' => $movementColumns['new_department_id'],
                'old_position_id' => $movementColumns['old_position_id'],
                'new_position_id' => $movementColumns['new_position_id'],
                'old_status' => $movementColumns['old_status'],
                'new_status' => $movementColumns['new_status'],
                'remark' => $remark !== '' ? $remark : null,
                'created_by' => currentUserId(),
            ]);

            if (!empty($updateFields)) {
                $setParts = [];
                $updateParams = ['id' => $employeeId];
                foreach ($updateFields as $column => $value) {
                    $setParts[] = "{$column} = :{$column}";
                    $updateParams[$column] = $value;
                }
                $setParts[] = 'updated_at = CURRENT_TIMESTAMP';

                $updateSql = 'UPDATE hr_employee SET ' . implode(', ', $setParts) . ' WHERE employee_id = :id';
                db()->prepare($updateSql)->execute($updateParams);
            }

            db()->commit();
        } catch (Throwable $exception) {
            db()->rollBack();
            throw $exception;
        }

        header('Location: employee_detail.php?id=' . $employeeId);
        exit;
    }
}

$fullName = trim(($employee['prefix'] ?? '') . $employee['first_name'] . ' ' . $employee['last_name']);

$pageTitle = 'บันทึกการเปลี่ยนแปลง';
$activeMenu = 'movements';
$breadcrumb = ['Employee', 'Employee List', $fullName, 'Movement'];

require_once __DIR__ . '/../includes/header.php';
$csrfToken = csrfToken();
?>

<h1><span class="page-title-icon icon-green"><i class="bi bi-arrow-left-right"></i></span>บันทึกการเปลี่ยนแปลงสถานะพนักงาน</h1>
<p class="page-description">
    พนักงาน: <strong><?= e($employee['employee_code']) ?> — <?= e($fullName) ?></strong>
    &middot; แผนกปัจจุบัน: <?= e($employee['department_name_th'] ?? '-') ?>
    &middot; ตำแหน่งปัจจุบัน: <?= e($employee['position_name_th'] ?? '-') ?>
    &middot; สถานะ: <?= employmentStatusBadge($employee['employment_status']) ?>
</p>

<form method="post" id="movement-form">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="employee_id" value="<?= $employeeId ?>">

    <fieldset>
        <legend>รายละเอียดการเปลี่ยนแปลง</legend>

        <div class="form-row">
            <div class="form-group">
                <label for="movement_type">ประเภทการเปลี่ยนแปลง *</label>
                <select id="movement_type" name="movement_type" required onchange="toggleMovementFields()">
                    <option value="">— เลือก —</option>
                    <?php foreach ($MOVEMENT_TYPES as $type): ?>
                        <option value="<?= $type ?>" <?= $movementType === $type ? 'selected' : '' ?>><?= movementTypeLabel($type) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['movement_type'])): ?><p class="error"><?= e($errors['movement_type']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="effective_date">วันที่มีผล *</label>
                <input type="date" id="effective_date" name="effective_date" value="<?= e($effectiveDate) ?>" required>
                <?php if (isset($errors['effective_date'])): ?><p class="error"><?= e($errors['effective_date']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="form-group" data-field-for="TRANSFER">
            <label for="new_department_id">แผนกใหม่</label>
            <select id="new_department_id" name="new_department_id">
                <option value="">— เลือก —</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= (int) $dept['department_id'] ?>" <?= (string) $dept['department_id'] === (string) $newDepartmentId ? 'selected' : '' ?>>
                        <?= e($dept['department_name_th']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['new_department_id'])): ?><p class="error"><?= e($errors['new_department_id']) ?></p><?php endif; ?>
        </div>

        <div class="form-group" data-field-for="POSITION_CHANGE,PROMOTION">
            <label for="new_position_id">ตำแหน่งใหม่</label>
            <select id="new_position_id" name="new_position_id">
                <option value="">— เลือก —</option>
                <?php foreach ($positions as $pos): ?>
                    <option value="<?= (int) $pos['position_id'] ?>" <?= (string) $pos['position_id'] === (string) $newPositionId ? 'selected' : '' ?>>
                        <?= e($pos['position_name_th']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['new_position_id'])): ?><p class="error"><?= e($errors['new_position_id']) ?></p><?php endif; ?>
        </div>

        <div class="form-group" data-field-for="STATUS_CHANGE">
            <label for="new_status">สถานะใหม่</label>
            <select id="new_status" name="new_status">
                <option value="">— เลือก —</option>
                <?php foreach (['ACTIVE', 'PROBATION', 'INACTIVE'] as $status): ?>
                    <option value="<?= $status ?>" <?= $newStatus === $status ? 'selected' : '' ?>><?= $status ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['new_status'])): ?><p class="error"><?= e($errors['new_status']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="remark">หมายเหตุ</label>
            <textarea id="remark" name="remark"><?= e($remark) ?></textarea>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="employee_detail.php?id=<?= $employeeId ?>" class="btn"><i class="bi bi-x-lg"></i> ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
    </div>
</form>

<script>
function toggleMovementFields() {
    var selected = document.getElementById('movement_type').value;
    var groups = document.querySelectorAll('[data-field-for]');
    groups.forEach(function (group) {
        var types = group.getAttribute('data-field-for').split(',');
        group.style.display = types.indexOf(selected) !== -1 ? '' : 'none';
    });
}
toggleMovementFields();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
