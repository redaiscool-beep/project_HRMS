<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeIdFilter = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? (int) $_GET['employee_id'] : null;
$departmentFilter = $_GET['department_id'] ?? '';
$search = trim($_GET['search'] ?? '');
$employeeSearch = trim($_GET['employee_search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$where = [];
$params = [];

if ($employeeIdFilter !== null) {
    $where[] = 't.employee_id = :employee_id';
    $params['employee_id'] = $employeeIdFilter;
}

if ($departmentFilter !== '') {
    $where[] = 'e.department_id = :department_id';
    $params['department_id'] = (int) $departmentFilter;
}

if ($search !== '') {
    $where[] = 't.course_name ILIKE :search';
    $params['search'] = '%' . $search . '%';
}

if ($employeeSearch !== '') {
    $where[] = "(e.employee_code || ' ' || e.first_name || ' ' || e.last_name) ILIKE :employee_search";
    $params['employee_search'] = '%' . $employeeSearch . '%';
}

if ($dateFrom !== '') {
    $where[] = 't.start_date >= :date_from';
    $params['date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = 't.start_date <= :date_to';
    $params['date_to'] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare(
    "SELECT COUNT(*) FROM hr_employee_training t
     JOIN hr_employee e ON e.employee_id = t.employee_id
     {$whereSql}"
);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$pagination = paginationParams($totalRows);

$stmt = db()->prepare(
    "SELECT t.*, e.employee_code, e.first_name, e.last_name, d.department_name_th
     FROM hr_employee_training t
     JOIN hr_employee e ON e.employee_id = t.employee_id
     LEFT JOIN hr_department d ON d.department_id = e.department_id
     {$whereSql}
     ORDER BY t.start_date DESC NULLS LAST, t.training_id DESC
     LIMIT :limit OFFSET :offset"
);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $pagination['perPage'], PDO::PARAM_INT);
$stmt->bindValue('offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();

$trainings = $stmt->fetchAll();
$departments = db()->query('SELECT department_id, department_name_th FROM hr_department ORDER BY department_name_th')->fetchAll();

$filteredEmployee = null;
if ($employeeIdFilter !== null) {
    $filteredEmployee = fetchEmployeeOrRedirect($employeeIdFilter);
}

$pageTitle = 'การอบรม (Training)';
$activeMenu = 'trainings';
$breadcrumb = $filteredEmployee
    ? ['Employee', 'Employee List', trim($filteredEmployee['first_name'] . ' ' . $filteredEmployee['last_name']), 'Training']
    : ['Employee', 'Training'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1><span class="page-title-icon icon-indigo"><i class="bi bi-mortarboard"></i></span>การอบรม (Training)</h1>

<?php if ($employeeIdFilter): ?>
    <?php renderEmployeeTabs($employeeIdFilter, 'training'); ?>
<?php endif; ?>

<p class="page-description">
    <?php if ($filteredEmployee): ?>
        แสดงเฉพาะของ <?= e($filteredEmployee['employee_code']) ?> — <?= e($filteredEmployee['first_name'] . ' ' . $filteredEmployee['last_name']) ?>
        &middot; <a href="employee_training.php">ดูทั้งหมด</a>
    <?php else: ?>
        ประวัติการอบรมของพนักงานทุกคน
    <?php endif; ?>
</p>

<div class="page-toolbar">
    <a href="employee_training_form.php<?= $employeeIdFilter ? '?employee_id=' . $employeeIdFilter : '' ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มการอบรม</a>
</div>

<form method="get" class="filter-bar">
    <?php if ($employeeIdFilter): ?>
        <input type="hidden" name="employee_id" value="<?= $employeeIdFilter ?>">
    <?php else: ?>
        <input type="text" name="employee_search" placeholder="ค้นหารหัส / ชื่อพนักงาน" value="<?= e($employeeSearch) ?>">
    <?php endif; ?>
    <input type="text" name="search" placeholder="ค้นหาชื่อหลักสูตร" value="<?= e($search) ?>">
    <select name="department_id">
        <option value="">ทุกแผนก</option>
        <?php foreach ($departments as $dept): ?>
            <option value="<?= (int) $dept['department_id'] ?>" <?= (string) $dept['department_id'] === (string) $departmentFilter ? 'selected' : '' ?>>
                <?= e($dept['department_name_th']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
    <input type="date" name="date_to" value="<?= e($dateTo) ?>">
    <button type="submit"><i class="bi bi-search"></i> ค้นหา</button>
</form>

<table class="data-table">
    <thead>
        <tr>
            <th>หลักสูตร</th>
            <th>พนักงาน</th>
            <th>แผนก</th>
            <th>วันที่อบรม</th>
            <th>ระยะเวลา</th>
            <th>ใบรับรอง</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($trainings)): ?>
        <tr><td colspan="7">ไม่พบข้อมูล</td></tr>
        <?php endif; ?>
        <?php foreach ($trainings as $t): ?>
        <tr>
            <td><?= e($t['course_name']) ?></td>
            <td><a href="employee_training.php?employee_id=<?= (int) $t['employee_id'] ?>"><?= e($t['employee_code']) ?> — <?= e($t['first_name'] . ' ' . $t['last_name']) ?></a></td>
            <td><?= e($t['department_name_th']) ?: '-' ?></td>
            <td><?= e($t['start_date']) ?: '-' ?><?= $t['end_date'] ? ' — ' . e($t['end_date']) : '' ?></td>
            <td><?= $t['duration'] ? e((string) $t['duration']) . ' ' . e($t['duration_unit']) : '-' ?></td>
            <td><?= $t['certificate_received'] ? '<span class="badge badge-green">มี</span>' : '<span class="badge badge-gray">ไม่มี</span>' ?></td>
            <td><a href="employee_training_form.php?employee_id=<?= (int) $t['employee_id'] ?>&id=<?= (int) $t['training_id'] ?>"><i class="bi bi-pencil-square"></i> แก้ไข</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php renderPagination($pagination); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
