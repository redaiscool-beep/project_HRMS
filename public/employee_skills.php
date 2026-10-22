<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$employee = fetchEmployeeOrRedirect($employeeId);

$stmt = db()->prepare(
    'SELECT * FROM hr_employee_skill WHERE employee_id = :id ORDER BY skill_id DESC'
);
$stmt->execute(['id' => $employeeId]);
$rows = $stmt->fetchAll();

$fullName = trim($employee['first_name'] . ' ' . $employee['last_name']);
$pageTitle = 'ทักษะ / Skill';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $fullName, 'Skill'];

require_once __DIR__ . '/../includes/header.php';

$levelBadge = [
    'BEGINNER' => 'badge-gray',
    'INTERMEDIATE' => 'badge-blue',
    'ADVANCED' => 'badge-amber',
    'EXPERT' => 'badge-green',
];
?>

<h1>ทักษะ / Skill</h1>
<?php renderEmployeeTabs($employeeId, 'skill'); ?>
<p class="page-description"><?= e($employee['employee_code']) ?> — <?= e($fullName) ?></p>

<div class="page-toolbar">
    <a href="employee_skill_form.php?employee_id=<?= $employeeId ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มทักษะ</a>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>ทักษะ</th>
            <th>ระดับ</th>
            <th>ประสบการณ์ (ปี)</th>
            <th>หมายเหตุ</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="5">ยังไม่มีข้อมูล</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['skill_name']) ?></td>
            <td>
                <?php if ($row['skill_level']): ?>
                    <span class="badge <?= $levelBadge[$row['skill_level']] ?? 'badge-gray' ?>"><?= e($row['skill_level']) ?></span>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td><?= e((string) ($row['experience_year'] ?? '-')) ?></td>
            <td><?= e($row['remark']) ?: '-' ?></td>
            <td><a href="employee_skill_form.php?employee_id=<?= $employeeId ?>&id=<?= (int) $row['skill_id'] ?>"><i class="bi bi-pencil-square"></i> แก้ไข</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
