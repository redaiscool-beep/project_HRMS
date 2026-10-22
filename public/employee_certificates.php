<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$employee = fetchEmployeeOrRedirect($employeeId);

$stmt = db()->prepare(
    'SELECT * FROM hr_employee_certificate WHERE employee_id = :id ORDER BY issue_date DESC NULLS LAST, certificate_id DESC'
);
$stmt->execute(['id' => $employeeId]);
$rows = $stmt->fetchAll();

$today = date('Y-m-d');

$fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$pageTitle = 'ใบรับรอง / Certificate';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $fullName, 'Certificate'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1>ใบรับรอง / Certificate</h1>
<?php renderEmployeeTabs($employeeId, 'certificate'); ?>
<p class="page-description"><?= e($employee['employee_code']) ?> — <?= e($fullName) ?></p>

<div class="page-toolbar">
    <a href="employee_certificate_form.php?employee_id=<?= $employeeId ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มใบรับรอง</a>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>ชื่อใบรับรอง</th>
            <th>ออกโดย</th>
            <th>วันที่ออก</th>
            <th>วันหมดอายุ</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="6">ยังไม่มีข้อมูล</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['certificate_name']) ?></td>
            <td><?= e($row['issued_by']) ?: '-' ?></td>
            <td><?= e($row['issue_date']) ?: '-' ?></td>
            <td><?= e($row['expire_date']) ?: '-' ?></td>
            <td>
                <?php if ($row['expire_date'] === null): ?>
                    <span class="badge badge-gray">ไม่มีวันหมดอายุ</span>
                <?php elseif ($row['expire_date'] < $today): ?>
                    <span class="badge badge-red">หมดอายุแล้ว</span>
                <?php elseif ($row['expire_date'] <= date('Y-m-d', strtotime('+30 days'))): ?>
                    <span class="badge badge-amber">ใกล้หมดอายุ</span>
                <?php else: ?>
                    <span class="badge badge-green">ยังไม่หมดอายุ</span>
                <?php endif; ?>
            </td>
            <td><a href="employee_certificate_form.php?employee_id=<?= $employeeId ?>&id=<?= (int) $row['certificate_id'] ?>"><i class="bi bi-pencil-square"></i> แก้ไข</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
