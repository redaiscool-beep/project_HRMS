<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$search = trim($_GET['search'] ?? '');
$departmentFilter = $_GET['department_id'] ?? '';
$positionFilter = $_GET['position_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(e.employee_code ILIKE :search OR e.first_name ILIKE :search OR e.last_name ILIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($departmentFilter !== '') {
    $where[] = 'e.department_id = :department_id';
    $params['department_id'] = (int) $departmentFilter;
}

if ($positionFilter !== '') {
    $where[] = 'e.position_id = :position_id';
    $params['position_id'] = (int) $positionFilter;
}

if ($statusFilter !== '') {
    $where[] = 'e.employment_status = :status';
    $params['status'] = $statusFilter;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM hr_employee e {$whereSql}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$pagination = paginationParams($totalRows);

$stmt = db()->prepare(
    "SELECT e.employee_id, e.employee_code, e.prefix, e.first_name, e.last_name,
            e.hire_date, e.employment_status,
            d.department_name_th, p.position_name_th
     FROM hr_employee e
     LEFT JOIN hr_department d ON d.department_id = e.department_id
     LEFT JOIN hr_position p ON p.position_id = e.position_id
     {$whereSql}
     ORDER BY e.employee_code
     LIMIT :limit OFFSET :offset"
);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $pagination['perPage'], PDO::PARAM_INT);
$stmt->bindValue('offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();

$employees = $stmt->fetchAll();

$departments = db()->query('SELECT department_id, department_name_th FROM hr_department WHERE is_active = true ORDER BY department_name_th')->fetchAll();
$positions = db()->query('SELECT position_id, position_name_th FROM hr_position WHERE is_active = true ORDER BY position_name_th')->fetchAll();

$pageTitle = 'จัดการพนักงาน';
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1><span class="page-title-icon icon-blue"><i class="bi bi-people"></i></span>จัดการพนักงาน</h1>
<p class="page-description">รายการพนักงานทั้งหมดในองค์กร</p>

<div class="page-toolbar">
    <a href="employee_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มพนักงานใหม่</a>
</div>

<form method="get" class="filter-bar">
    <input type="text" name="search" placeholder="ค้นหารหัส/ชื่อพนักงาน" value="<?= e($search) ?>">

    <select name="department_id">
        <option value="">ทุกแผนก</option>
        <?php foreach ($departments as $dept): ?>
            <option value="<?= (int) $dept['department_id'] ?>" <?= (string) $dept['department_id'] === (string) $departmentFilter ? 'selected' : '' ?>>
                <?= e($dept['department_name_th']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="position_id">
        <option value="">ทุกตำแหน่ง</option>
        <?php foreach ($positions as $pos): ?>
            <option value="<?= (int) $pos['position_id'] ?>" <?= (string) $pos['position_id'] === (string) $positionFilter ? 'selected' : '' ?>>
                <?= e($pos['position_name_th']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="status">
        <option value="">ทุกสถานะ</option>
        <?php foreach (['ACTIVE', 'PROBATION', 'RESIGNED', 'INACTIVE'] as $status): ?>
            <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $status ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit"><i class="bi bi-search"></i> ค้นหา</button>
</form>

<table class="data-table">
    <thead>
        <tr>
            <th>รหัสพนักงาน</th>
            <th>ชื่อ-นามสกุล</th>
            <th>แผนก</th>
            <th>ตำแหน่ง</th>
            <th>วันที่เริ่มงาน</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($employees)): ?>
        <tr>
            <td colspan="7">ไม่พบข้อมูล</td>
        </tr>
        <?php endif; ?>
        <?php foreach ($employees as $employee): ?>
        <tr>
            <td><?= e($employee['employee_code']) ?></td>
            <td><?= e(trim(($employee['prefix'] ?? '') . $employee['first_name'] . ' ' . $employee['last_name'])) ?></td>
            <td><?= e($employee['department_name_th'] ?? '-') ?></td>
            <td><?= e($employee['position_name_th'] ?? '-') ?></td>
            <td><?= e($employee['hire_date']) ?></td>
            <td><?= employmentStatusBadge($employee['employment_status']) ?></td>
            <td>
                <a href="employee_detail.php?id=<?= (int) $employee['employee_id'] ?>">ดูข้อมูล</a>
                &middot;
                <a href="employee_form.php?id=<?= (int) $employee['employee_id'] ?>"><i class="bi bi-pencil-square"></i> แก้ไข</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php renderPagination($pagination); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
