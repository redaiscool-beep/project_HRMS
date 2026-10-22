<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(employee_type_code ILIKE :search OR employee_type_name ILIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($statusFilter === 'active') {
    $where[] = 'is_active = true';
} elseif ($statusFilter === 'inactive') {
    $where[] = 'is_active = false';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM hr_employee_type {$whereSql}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$pagination = paginationParams($totalRows);

$stmt = db()->prepare(
    "SELECT employee_type_id, employee_type_code, employee_type_name, is_active
     FROM hr_employee_type
     {$whereSql}
     ORDER BY employee_type_name
     LIMIT :limit OFFSET :offset"
);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $pagination['perPage'], PDO::PARAM_INT);
$stmt->bindValue('offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();

$employeeTypes = $stmt->fetchAll();

$pageTitle = 'จัดการประเภทพนักงาน';
$activeMenu = 'employee_types';
$breadcrumb = ['Master Data', 'Employee Type'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1><span class="page-title-icon icon-pink"><i class="bi bi-tags"></i></span>จัดการประเภทพนักงาน</h1>
<p>รายการประเภทพนักงานทั้งหมด</p>

<a href="employee_type_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มประเภทพนักงาน</a>

<form method="get" class="filter-bar">
    <input type="text" name="search" placeholder="ค้นหารหัส/ชื่อประเภท" value="<?= e($search) ?>">
    <select name="status">
        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>ใช้งาน</option>
        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>ปิดใช้งาน</option>
    </select>
    <button type="submit"><i class="bi bi-search"></i> ค้นหา</button>
</form>

<table class="data-table">
    <thead>
        <tr>
            <th>รหัส</th>
            <th>ชื่อประเภทพนักงาน</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($employeeTypes)): ?>
        <tr>
            <td colspan="4">ไม่พบข้อมูล</td>
        </tr>
        <?php endif; ?>
        <?php foreach ($employeeTypes as $employeeType): ?>
        <tr>
            <td><?= e($employeeType['employee_type_code']) ?></td>
            <td><?= e($employeeType['employee_type_name']) ?></td>
            <td><?= activeBadge((bool) $employeeType['is_active']) ?></td>
            <td>
                <a href="employee_type_form.php?id=<?= (int) $employeeType['employee_type_id'] ?>"><i class="bi bi-pencil-square"></i> แก้ไข</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php renderPagination($pagination); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
