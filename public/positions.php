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
    $where[] = '(position_code ILIKE :search OR position_name_th ILIKE :search OR position_name_en ILIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($statusFilter === 'active') {
    $where[] = 'is_active = true';
} elseif ($statusFilter === 'inactive') {
    $where[] = 'is_active = false';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM hr_position {$whereSql}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$pagination = paginationParams($totalRows);

$stmt = db()->prepare(
    "SELECT position_id, position_code, position_name_th, position_name_en, position_level, is_active
     FROM hr_position
     {$whereSql}
     ORDER BY sort_order, position_name_th
     LIMIT :limit OFFSET :offset"
);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $pagination['perPage'], PDO::PARAM_INT);
$stmt->bindValue('offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();

$positions = $stmt->fetchAll();

$pageTitle = 'จัดการตำแหน่ง';
$activeMenu = 'positions';
$breadcrumb = ['Master Data', 'Position'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1><span class="page-title-icon icon-purple"><i class="bi bi-briefcase"></i></span>จัดการตำแหน่ง</h1>
<p>รายการตำแหน่งงานทั้งหมด</p>

<a href="position_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มตำแหน่งใหม่</a>

<form method="get" class="filter-bar">
    <input type="text" name="search" placeholder="ค้นหารหัส/ชื่อตำแหน่ง" value="<?= e($search) ?>">
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
            <th>ชื่อตำแหน่ง (ไทย)</th>
            <th>ชื่อตำแหน่ง (อังกฤษ)</th>
            <th>ระดับ</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($positions)): ?>
        <tr>
            <td colspan="6">ไม่พบข้อมูล</td>
        </tr>
        <?php endif; ?>
        <?php foreach ($positions as $position): ?>
        <tr>
            <td><?= e($position['position_code']) ?></td>
            <td><?= e($position['position_name_th']) ?></td>
            <td><?= e($position['position_name_en']) ?></td>
            <td><?= e((string) ($position['position_level'] ?? '')) ?></td>
            <td><?= activeBadge((bool) $position['is_active']) ?></td>
            <td>
                <a href="position_form.php?id=<?= (int) $position['position_id'] ?>"><i class="bi bi-pencil-square"></i> แก้ไข</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php renderPagination($pagination); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
