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
    $where[] = '(department_code ILIKE :search OR department_name_th ILIKE :search OR department_name_en ILIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($statusFilter === 'active') {
    $where[] = 'is_active = true';
} elseif ($statusFilter === 'inactive') {
    $where[] = 'is_active = false';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM hr_department {$whereSql}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$pagination = paginationParams($totalRows);

$stmt = db()->prepare(
    "SELECT department_id, department_code, department_name_th, department_name_en, is_active
     FROM hr_department
     {$whereSql}
     ORDER BY sort_order, department_name_th
     LIMIT :limit OFFSET :offset"
);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $pagination['perPage'], PDO::PARAM_INT);
$stmt->bindValue('offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();

$departments = $stmt->fetchAll();

$pageTitle = 'จัดการแผนก';
$activeMenu = 'departments';
$breadcrumb = ['Master Data', 'Department'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1><span class="page-title-icon icon-orange"><i class="bi bi-building"></i></span>จัดการแผนก</h1>
<p>รายการแผนกทั้งหมดในองค์กร</p>

<a href="department_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มแผนกใหม่</a>

<form method="get" class="filter-bar">
    <input type="text" name="search" placeholder="ค้นหารหัส/ชื่อแผนก" value="<?= e($search) ?>">
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
            <th>ชื่อแผนก (ไทย)</th>
            <th>ชื่อแผนก (อังกฤษ)</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($departments)): ?>
        <tr>
            <td colspan="5">ไม่พบข้อมูล</td>
        </tr>
        <?php endif; ?>
        <?php foreach ($departments as $department): ?>
        <tr>
            <td><?= e($department['department_code']) ?></td>
            <td><?= e($department['department_name_th']) ?></td>
            <td><?= e($department['department_name_en']) ?></td>
            <td><?= activeBadge((bool) $department['is_active']) ?></td>
            <td>
                <a href="department_form.php?id=<?= (int) $department['department_id'] ?>"><i class="bi bi-pencil-square"></i> แก้ไข</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php renderPagination($pagination); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
