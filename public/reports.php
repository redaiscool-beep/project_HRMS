<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$type = $_GET['type'] ?? 'employee';
if (!in_array($type, ['employee', 'movement', 'training'], true)) {
    $type = 'employee';
}

$departmentFilter = $_GET['department_id'] ?? '';
$positionFilter = $_GET['position_id'] ?? '';
$employeeTypeFilter = $_GET['employee_type_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$hireDateFrom = trim($_GET['hire_date_from'] ?? '');
$hireDateTo = trim($_GET['hire_date_to'] ?? '');
$movementTypeFilter = $_GET['movement_type'] ?? '';
$employeeSearch = trim($_GET['employee_search'] ?? '');
$courseSearch = trim($_GET['course'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$columns = [];
$rows = [];

if ($type === 'employee') {
    $where = [];
    $params = [];

    if ($departmentFilter !== '') {
        $where[] = 'e.department_id = :department_id';
        $params['department_id'] = (int) $departmentFilter;
    }
    if ($positionFilter !== '') {
        $where[] = 'e.position_id = :position_id';
        $params['position_id'] = (int) $positionFilter;
    }
    if ($employeeTypeFilter !== '') {
        $where[] = 'e.employee_type_id = :employee_type_id';
        $params['employee_type_id'] = (int) $employeeTypeFilter;
    }
    if ($statusFilter !== '') {
        $where[] = 'e.employment_status = :status';
        $params['status'] = $statusFilter;
    }
    if ($hireDateFrom !== '') {
        $where[] = 'e.hire_date >= :hire_from';
        $params['hire_from'] = $hireDateFrom;
    }
    if ($hireDateTo !== '') {
        $where[] = 'e.hire_date <= :hire_to';
        $params['hire_to'] = $hireDateTo;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = db()->prepare(
        "SELECT e.employee_code, e.prefix, e.first_name, e.last_name,
                d.department_name_th, p.position_name_th, t.employee_type_name,
                e.hire_date, e.employment_status
         FROM hr_employee e
         LEFT JOIN hr_department d ON d.department_id = e.department_id
         LEFT JOIN hr_position p ON p.position_id = e.position_id
         LEFT JOIN hr_employee_type t ON t.employee_type_id = e.employee_type_id
         {$whereSql}
         ORDER BY e.employee_code"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $columns = [
        'employee_code' => 'รหัสพนักงาน',
        'name' => 'ชื่อ-นามสกุล',
        'department_name_th' => 'แผนก',
        'position_name_th' => 'ตำแหน่ง',
        'employee_type_name' => 'ประเภทพนักงาน',
        'hire_date' => 'วันที่เริ่มงาน',
        'employment_status' => 'สถานะ',
    ];
} elseif ($type === 'movement') {
    $where = [];
    $params = [];

    if ($movementTypeFilter !== '') {
        $where[] = 'm.movement_type = :movement_type';
        $params['movement_type'] = $movementTypeFilter;
    }
    if ($departmentFilter !== '') {
        $where[] = '(m.old_department_id = :dept OR m.new_department_id = :dept OR e.department_id = :dept)';
        $params['dept'] = (int) $departmentFilter;
    }
    if ($employeeSearch !== '') {
        $where[] = '(e.employee_code ILIKE :search OR e.first_name ILIKE :search OR e.last_name ILIKE :search)';
        $params['search'] = '%' . $employeeSearch . '%';
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

    $stmt = db()->prepare(
        "SELECT m.movement_type, m.effective_date, m.remark,
                e.employee_code, e.first_name, e.last_name,
                od.department_name_th AS old_dept, nd.department_name_th AS new_dept,
                op.position_name_th AS old_pos, np.position_name_th AS new_pos,
                m.old_status, m.new_status
         FROM hr_employee_movement m
         JOIN hr_employee e ON e.employee_id = m.employee_id
         LEFT JOIN hr_department od ON od.department_id = m.old_department_id
         LEFT JOIN hr_department nd ON nd.department_id = m.new_department_id
         LEFT JOIN hr_position op ON op.position_id = m.old_position_id
         LEFT JOIN hr_position np ON np.position_id = m.new_position_id
         {$whereSql}
         ORDER BY m.effective_date DESC, m.movement_id DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $columns = [
        'effective_date' => 'วันที่มีผล',
        'employee_code' => 'รหัสพนักงาน',
        'name' => 'ชื่อพนักงาน',
        'movement_type' => 'ประเภท',
        'old_dept' => 'แผนกเดิม',
        'new_dept' => 'แผนกใหม่',
        'old_pos' => 'ตำแหน่งเดิม',
        'new_pos' => 'ตำแหน่งใหม่',
        'old_status' => 'สถานะเดิม',
        'new_status' => 'สถานะใหม่',
        'remark' => 'หมายเหตุ',
    ];
} else {
    $where = [];
    $params = [];

    if ($employeeSearch !== '') {
        $where[] = '(e.employee_code ILIKE :emp_search OR e.first_name ILIKE :emp_search OR e.last_name ILIKE :emp_search)';
        $params['emp_search'] = '%' . $employeeSearch . '%';
    }
    if ($courseSearch !== '') {
        $where[] = 't.course_name ILIKE :course_search';
        $params['course_search'] = '%' . $courseSearch . '%';
    }
    if ($departmentFilter !== '') {
        $where[] = 'e.department_id = :department_id';
        $params['department_id'] = (int) $departmentFilter;
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

    $stmt = db()->prepare(
        "SELECT t.course_name, t.training_provider, t.start_date, t.end_date, t.duration, t.duration_unit,
                t.certificate_received, e.employee_code, e.first_name, e.last_name, d.department_name_th
         FROM hr_employee_training t
         JOIN hr_employee e ON e.employee_id = t.employee_id
         LEFT JOIN hr_department d ON d.department_id = e.department_id
         {$whereSql}
         ORDER BY t.start_date DESC NULLS LAST"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $columns = [
        'course_name' => 'หลักสูตร',
        'employee_code' => 'รหัสพนักงาน',
        'name' => 'ชื่อพนักงาน',
        'department_name_th' => 'แผนก',
        'start_date' => 'วันที่เริ่ม',
        'end_date' => 'วันที่สิ้นสุด',
        'duration' => 'ระยะเวลา',
        'duration_unit' => 'หน่วย',
        'certificate_received' => 'ได้รับใบรับรอง',
    ];
}

function reportRowToCsvArray(string $type, array $row): array
{
    $name = trim(($row['prefix'] ?? '') . ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

    if ($type === 'employee') {
        return [
            $row['employee_code'], $name, $row['department_name_th'] ?? '', $row['position_name_th'] ?? '',
            $row['employee_type_name'] ?? '', $row['hire_date'], $row['employment_status'],
        ];
    }

    if ($type === 'movement') {
        return [
            $row['effective_date'], $row['employee_code'], $name, movementTypeLabel($row['movement_type']),
            $row['old_dept'] ?? '', $row['new_dept'] ?? '', $row['old_pos'] ?? '', $row['new_pos'] ?? '',
            $row['old_status'] ?? '', $row['new_status'] ?? '', $row['remark'] ?? '',
        ];
    }

    return [
        $row['course_name'], $row['employee_code'], $name, $row['department_name_th'] ?? '',
        $row['start_date'] ?? '', $row['end_date'] ?? '', $row['duration'] ?? '', $row['duration_unit'] ?? '',
        $row['certificate_received'] ? 'Yes' : 'No',
    ];
}

if (($_GET['export'] ?? '') === 'csv') {
    $filename = $type . '_report_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_values($columns), ',', '"', '\\');

    foreach ($rows as $row) {
        fputcsv($out, reportRowToCsvArray($type, $row), ',', '"', '\\');
    }

    fclose($out);
    exit;
}

$departments = db()->query('SELECT department_id, department_name_th FROM hr_department ORDER BY department_name_th')->fetchAll();
$positions = db()->query('SELECT position_id, position_name_th FROM hr_position ORDER BY position_name_th')->fetchAll();
$employeeTypes = db()->query('SELECT employee_type_id, employee_type_name FROM hr_employee_type ORDER BY employee_type_name')->fetchAll();

$pageTitle = 'Reports';
$activeMenu = 'report_' . $type;
$breadcrumb = ['Reports', ucfirst($type) . ' Report'];

require_once __DIR__ . '/../includes/header.php';

$exportQuery = $_GET;
$exportQuery['export'] = 'csv';
?>

<h1><span class="page-title-icon icon-blue"><i class="bi bi-clipboard-data"></i></span>Reports</h1>

<div class="tabs">
    <a href="reports.php?type=employee" class="<?= $type === 'employee' ? 'active' : '' ?>">Employee Report</a>
    <a href="reports.php?type=movement" class="<?= $type === 'movement' ? 'active' : '' ?>">Movement Report</a>
    <a href="reports.php?type=training" class="<?= $type === 'training' ? 'active' : '' ?>">Training Report</a>
</div>

<div class="page-toolbar no-print">
    <div></div>
    <div class="form-actions">
        <button type="button" class="btn" onclick="window.print()"><i class="bi bi-printer"></i> พิมพ์</button>
        <a href="?<?= http_build_query($exportQuery) ?>" class="btn btn-primary"><i class="bi bi-download"></i> Export CSV</a>
    </div>
</div>

<?php if ($type === 'employee'): ?>
    <form method="get" class="filter-bar no-print">
        <input type="hidden" name="type" value="employee">
        <select name="department_id">
            <option value="">ทุกแผนก</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= (int) $d['department_id'] ?>" <?= (string) $d['department_id'] === (string) $departmentFilter ? 'selected' : '' ?>><?= e($d['department_name_th']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="position_id">
            <option value="">ทุกตำแหน่ง</option>
            <?php foreach ($positions as $p): ?>
                <option value="<?= (int) $p['position_id'] ?>" <?= (string) $p['position_id'] === (string) $positionFilter ? 'selected' : '' ?>><?= e($p['position_name_th']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="employee_type_id">
            <option value="">ทุกประเภท</option>
            <?php foreach ($employeeTypes as $t): ?>
                <option value="<?= (int) $t['employee_type_id'] ?>" <?= (string) $t['employee_type_id'] === (string) $employeeTypeFilter ? 'selected' : '' ?>><?= e($t['employee_type_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">ทุกสถานะ</option>
            <?php foreach (['ACTIVE', 'PROBATION', 'RESIGNED', 'INACTIVE'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="hire_date_from" value="<?= e($hireDateFrom) ?>">
        <input type="date" name="hire_date_to" value="<?= e($hireDateTo) ?>">
        <button type="submit"><i class="bi bi-search"></i> ค้นหา</button>
    </form>

    <table class="data-table">
        <thead>
            <tr>
                <th>รหัสพนักงาน</th><th>ชื่อ-นามสกุล</th><th>แผนก</th><th>ตำแหน่ง</th>
                <th>ประเภทพนักงาน</th><th>วันที่เริ่มงาน</th><th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?><tr><td colspan="7">ไม่พบข้อมูล</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['employee_code']) ?></td>
                <td><?= e(trim(($row['prefix'] ?? '') . $row['first_name'] . ' ' . $row['last_name'])) ?></td>
                <td><?= e($row['department_name_th']) ?: '-' ?></td>
                <td><?= e($row['position_name_th']) ?: '-' ?></td>
                <td><?= e($row['employee_type_name']) ?: '-' ?></td>
                <td><?= e($row['hire_date']) ?></td>
                <td><?= employmentStatusBadge($row['employment_status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="text-muted">พบทั้งหมด <?= count($rows) ?> รายการ</p>

<?php elseif ($type === 'movement'): ?>
    <form method="get" class="filter-bar no-print">
        <input type="hidden" name="type" value="movement">
        <input type="text" name="employee_search" placeholder="ค้นหาพนักงาน" value="<?= e($employeeSearch) ?>">
        <select name="movement_type">
            <option value="">ทุกประเภท</option>
            <?php foreach (['HIRE', 'PASS_PROBATION', 'TRANSFER', 'POSITION_CHANGE', 'PROMOTION', 'STATUS_CHANGE', 'RESIGN'] as $mt): ?>
                <option value="<?= $mt ?>" <?= $movementTypeFilter === $mt ? 'selected' : '' ?>><?= movementTypeLabel($mt) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="department_id">
            <option value="">ทุกแผนก</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= (int) $d['department_id'] ?>" <?= (string) $d['department_id'] === (string) $departmentFilter ? 'selected' : '' ?>><?= e($d['department_name_th']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
        <input type="date" name="date_to" value="<?= e($dateTo) ?>">
        <button type="submit"><i class="bi bi-search"></i> ค้นหา</button>
    </form>

    <table class="data-table">
        <thead>
            <tr><th>วันที่มีผล</th><th>พนักงาน</th><th>ประเภท</th><th>รายละเอียด</th><th>หมายเหตุ</th></tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?><tr><td colspan="5">ไม่พบข้อมูล</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['effective_date']) ?></td>
                <td><?= e($row['employee_code']) ?> — <?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><span class="badge badge-blue"><?= e(movementTypeLabel($row['movement_type'])) ?></span></td>
                <td>
                    <?php if ($row['old_dept'] || $row['new_dept']): ?>แผนก: <?= e($row['old_dept'] ?? '-') ?> → <?= e($row['new_dept'] ?? '-') ?><br><?php endif; ?>
                    <?php if ($row['old_pos'] || $row['new_pos']): ?>ตำแหน่ง: <?= e($row['old_pos'] ?? '-') ?> → <?= e($row['new_pos'] ?? '-') ?><br><?php endif; ?>
                    <?php if ($row['old_status'] || $row['new_status']): ?>สถานะ: <?= e($row['old_status'] ?? '-') ?> → <?= e($row['new_status'] ?? '-') ?><?php endif; ?>
                </td>
                <td><?= e($row['remark']) ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="text-muted">พบทั้งหมด <?= count($rows) ?> รายการ</p>

<?php else: ?>
    <form method="get" class="filter-bar no-print">
        <input type="hidden" name="type" value="training">
        <input type="text" name="employee_search" placeholder="ค้นหาพนักงาน" value="<?= e($employeeSearch) ?>">
        <input type="text" name="course" placeholder="ค้นหาหลักสูตร" value="<?= e($courseSearch) ?>">
        <select name="department_id">
            <option value="">ทุกแผนก</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= (int) $d['department_id'] ?>" <?= (string) $d['department_id'] === (string) $departmentFilter ? 'selected' : '' ?>><?= e($d['department_name_th']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
        <input type="date" name="date_to" value="<?= e($dateTo) ?>">
        <button type="submit"><i class="bi bi-search"></i> ค้นหา</button>
    </form>

    <table class="data-table">
        <thead>
            <tr><th>หลักสูตร</th><th>พนักงาน</th><th>แผนก</th><th>วันที่อบรม</th><th>ระยะเวลา</th><th>ใบรับรอง</th></tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?><tr><td colspan="6">ไม่พบข้อมูล</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['course_name']) ?></td>
                <td><?= e($row['employee_code']) ?> — <?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= e($row['department_name_th']) ?: '-' ?></td>
                <td><?= e($row['start_date']) ?: '-' ?><?= $row['end_date'] ? ' — ' . e($row['end_date']) : '' ?></td>
                <td><?= $row['duration'] ? e((string) $row['duration']) . ' ' . e($row['duration_unit']) : '-' ?></td>
                <td><?= $row['certificate_received'] ? '<span class="badge badge-green">มี</span>' : '<span class="badge badge-gray">ไม่มี</span>' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="text-muted">พบทั้งหมด <?= count($rows) ?> รายการ</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
