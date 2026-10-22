<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeIdFilter = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? (int) $_GET['employee_id'] : null;
$movementTypeFilter = $_GET['movement_type'] ?? '';
$search = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$where = [];
$params = [];

if ($employeeIdFilter !== null) {
    $where[] = 'm.employee_id = :employee_id';
    $params['employee_id'] = $employeeIdFilter;
}

if ($search !== '') {
    $where[] = "(e.employee_code || ' ' || e.first_name || ' ' || e.last_name) ILIKE :search";
    $params['search'] = '%' . $search . '%';
}

if ($movementTypeFilter !== '') {
    $where[] = 'm.movement_type = :movement_type';
    $params['movement_type'] = $movementTypeFilter;
}

if ($dateFrom !== '') {
    $where[] = 'm.effective_date >= :date_from';
    $params['date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = 'm.effective_date <= :date_to';
    $params['date_to'] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM hr_employee_movement m JOIN hr_employee e ON e.employee_id = m.employee_id {$whereSql}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$pagination = paginationParams($totalRows);

$stmt = db()->prepare(
    "SELECT m.*, e.employee_code, e.first_name, e.last_name,
            od.department_name_th AS old_department_name, nd.department_name_th AS new_department_name,
            op.position_name_th AS old_position_name, np.position_name_th AS new_position_name
     FROM hr_employee_movement m
     JOIN hr_employee e ON e.employee_id = m.employee_id
     LEFT JOIN hr_department od ON od.department_id = m.old_department_id
     LEFT JOIN hr_department nd ON nd.department_id = m.new_department_id
     LEFT JOIN hr_position op ON op.position_id = m.old_position_id
     LEFT JOIN hr_position np ON np.position_id = m.new_position_id
     {$whereSql}
     ORDER BY m.effective_date DESC, m.movement_id DESC
     LIMIT :limit OFFSET :offset"
);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $pagination['perPage'], PDO::PARAM_INT);
$stmt->bindValue('offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();

$movements = $stmt->fetchAll();

$filteredEmployee = null;
if ($employeeIdFilter !== null) {
    $empStmt = db()->prepare('SELECT employee_code, first_name, last_name FROM hr_employee WHERE employee_id = :id');
    $empStmt->execute(['id' => $employeeIdFilter]);
    $filteredEmployee = $empStmt->fetch();
}

$pageTitle = 'ประวัติการเปลี่ยนแปลง (Movement)';
$activeMenu = 'movements';
$breadcrumb = $filteredEmployee
    ? ['Employee', 'Employee List', trim($filteredEmployee['first_name'] . ' ' . $filteredEmployee['last_name']), 'Movement']
    : ['Employee', 'Movement'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1><span class="page-title-icon icon-green"><i class="bi bi-arrow-left-right"></i></span>ประวัติการเปลี่ยนแปลง (Movement)</h1>

<?php if ($employeeIdFilter): ?>
    <?php renderEmployeeTabs($employeeIdFilter, 'movement'); ?>
<?php endif; ?>

<p class="page-description">
    <?php if ($filteredEmployee): ?>
        แสดงเฉพาะของ <?= e($filteredEmployee['employee_code']) ?> — <?= e($filteredEmployee['first_name'] . ' ' . $filteredEmployee['last_name']) ?>
        &middot; <a href="employee_movements.php">ดูทั้งหมด</a>
    <?php else: ?>
        ประวัติการโยกย้าย เลื่อนตำแหน่ง เปลี่ยนสถานะ ของพนักงานทุกคน
    <?php endif; ?>
</p>

<div class="page-toolbar">
    <a href="employee_movement_form.php<?= $employeeIdFilter ? '?employee_id=' . $employeeIdFilter : '' ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> บันทึกการเปลี่ยนแปลง</a>
</div>

<form method="get" class="filter-bar">
    <?php if ($employeeIdFilter): ?>
        <input type="hidden" name="employee_id" value="<?= $employeeIdFilter ?>">
    <?php else: ?>
        <input type="text" name="search" placeholder="ค้นหารหัส / ชื่อพนักงาน" value="<?= e($search) ?>">
    <?php endif; ?>
    <select name="movement_type">
        <option value="">ทุกประเภท</option>
        <?php foreach (['HIRE', 'PASS_PROBATION', 'TRANSFER', 'POSITION_CHANGE', 'PROMOTION', 'STATUS_CHANGE', 'RESIGN'] as $type): ?>
            <option value="<?= $type ?>" <?= $movementTypeFilter === $type ? 'selected' : '' ?>><?= movementTypeLabel($type) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
    <input type="date" name="date_to" value="<?= e($dateTo) ?>">
    <button type="submit"><i class="bi bi-search"></i> ค้นหา</button>
</form>

<table class="data-table">
    <thead>
        <tr>
            <th>วันที่มีผล</th>
            <th>พนักงาน</th>
            <th>ประเภท</th>
            <th>รายละเอียดการเปลี่ยนแปลง</th>
            <th>หมายเหตุ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($movements)): ?>
        <tr>
            <td colspan="5">ไม่พบข้อมูล</td>
        </tr>
        <?php endif; ?>
        <?php foreach ($movements as $m): ?>
        <tr>
            <td><?= e($m['effective_date']) ?></td>
            <td>
                <a href="employee_movements.php?employee_id=<?= (int) $m['employee_id'] ?>">
                    <?= e($m['employee_code']) ?> — <?= e($m['first_name'] . ' ' . $m['last_name']) ?>
                </a>
            </td>
            <td><span class="badge badge-blue"><?= e(movementTypeLabel($m['movement_type'])) ?></span></td>
            <td>
                <?php if ($m['old_department_name'] || $m['new_department_name']): ?>
                    แผนก: <?= e($m['old_department_name'] ?? '-') ?> → <?= e($m['new_department_name'] ?? '-') ?><br>
                <?php endif; ?>
                <?php if ($m['old_position_name'] || $m['new_position_name']): ?>
                    ตำแหน่ง: <?= e($m['old_position_name'] ?? '-') ?> → <?= e($m['new_position_name'] ?? '-') ?><br>
                <?php endif; ?>
                <?php if ($m['old_status'] || $m['new_status']): ?>
                    สถานะ: <?= e($m['old_status'] ?? '-') ?> → <?= e($m['new_status'] ?? '-') ?>
                <?php endif; ?>
            </td>
            <td><?= e($m['remark']) ?: '-' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php renderPagination($pagination); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
