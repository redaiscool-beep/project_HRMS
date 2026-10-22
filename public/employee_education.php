<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$employee = fetchEmployeeOrRedirect($employeeId);

$stmt = db()->prepare(
    'SELECT * FROM hr_employee_education WHERE employee_id = :id ORDER BY graduation_year DESC NULLS LAST, education_id DESC'
);
$stmt->execute(['id' => $employeeId]);
$rows = $stmt->fetchAll();

$fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$pageTitle = 'ประวัติการศึกษา';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $fullName, 'Education'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1>ประวัติการศึกษา</h1>
<?php renderEmployeeTabs($employeeId, 'education'); ?>
<p class="page-description"><?= e($employee['employee_code']) ?> — <?= e($fullName) ?></p>

<div class="page-toolbar">
    <a href="employee_education_form.php?employee_id=<?= $employeeId ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มประวัติการศึกษา</a>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>ระดับการศึกษา</th>
            <th>วุฒิ/สาขา</th>
            <th>สถาบัน</th>
            <th>ปีที่จบ</th>
            <th>GPA</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="6">ยังไม่มีข้อมูล</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['education_level']) ?: '-' ?></td>
            <td><?= e(trim(($row['degree_name'] ?? '') . ' ' . ($row['major_name'] ?? ''))) ?: '-' ?></td>
            <td><?= e($row['institute_name']) ?: '-' ?></td>
            <td><?= e((string) ($row['graduation_year'] ?? '-')) ?></td>
            <td><?= e((string) ($row['gpa'] ?? '-')) ?></td>
            <td><a href="employee_education_form.php?employee_id=<?= $employeeId ?>&id=<?= (int) $row['education_id'] ?>"><i class="bi bi-pencil-square"></i> แก้ไข</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
