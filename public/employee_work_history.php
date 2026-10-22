<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$employee = fetchEmployeeOrRedirect($employeeId);

$stmt = db()->prepare(
    'SELECT * FROM hr_employee_work_history WHERE employee_id = :id ORDER BY start_date DESC NULLS LAST, work_history_id DESC'
);
$stmt->execute(['id' => $employeeId]);
$rows = $stmt->fetchAll();

$fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$pageTitle = 'ประวัติการทำงาน';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $fullName, 'Work History'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1>ประวัติการทำงาน</h1>
<?php renderEmployeeTabs($employeeId, 'work_history'); ?>
<p class="page-description"><?= e($employee['employee_code']) ?> — <?= e($fullName) ?></p>

<div class="page-toolbar">
    <a href="employee_work_history_form.php?employee_id=<?= $employeeId ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มประวัติการทำงาน</a>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>บริษัท</th>
            <th>ตำแหน่ง</th>
            <th>ช่วงเวลา</th>
            <th>เหตุผลที่ลาออก</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="5">ยังไม่มีข้อมูล</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['company_name']) ?: '-' ?></td>
            <td><?= e($row['position_name']) ?: '-' ?></td>
            <td><?= e($row['start_date']) ?: '-' ?> — <?= e($row['end_date']) ?: 'ปัจจุบัน' ?></td>
            <td><?= e($row['reason_for_leaving']) ?: '-' ?></td>
            <td><a href="employee_work_history_form.php?employee_id=<?= $employeeId ?>&id=<?= (int) $row['work_history_id'] ?>"><i class="bi bi-pencil-square"></i> แก้ไข</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
