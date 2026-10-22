<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$totalEmployees = (int) db()->query('SELECT COUNT(*) FROM hr_employee')->fetchColumn();
$activeCount = (int) db()->query("SELECT COUNT(*) FROM hr_employee WHERE employment_status = 'ACTIVE'")->fetchColumn();
$probationCount = (int) db()->query("SELECT COUNT(*) FROM hr_employee WHERE employment_status = 'PROBATION'")->fetchColumn();

$newHiresCount = (int) db()->query(
    "SELECT COUNT(*) FROM hr_employee
     WHERE hire_date >= date_trunc('month', CURRENT_DATE)
       AND hire_date < date_trunc('month', CURRENT_DATE) + interval '1 month'"
)->fetchColumn();

$resignedThisMonthCount = (int) db()->query(
    "SELECT COUNT(*) FROM hr_employee
     WHERE resign_date >= date_trunc('month', CURRENT_DATE)
       AND resign_date < date_trunc('month', CURRENT_DATE) + interval '1 month'"
)->fetchColumn();

$byDepartment = db()->query(
    "SELECT d.department_name_th, COUNT(e.employee_id) AS total
     FROM hr_department d
     LEFT JOIN hr_employee e ON e.department_id = d.department_id AND e.employment_status != 'RESIGNED'
     WHERE d.is_active = true
     GROUP BY d.department_name_th
     ORDER BY total DESC"
)->fetchAll();

$byPosition = db()->query(
    "SELECT p.position_name_th, COUNT(e.employee_id) AS total
     FROM hr_position p
     LEFT JOIN hr_employee e ON e.position_id = p.position_id AND e.employment_status != 'RESIGNED'
     WHERE p.is_active = true
     GROUP BY p.position_name_th
     ORDER BY total DESC"
)->fetchAll();

$recentMovements = db()->query(
    "SELECT m.movement_type, m.effective_date, e.employee_code, e.first_name, e.last_name
     FROM hr_employee_movement m
     JOIN hr_employee e ON e.employee_id = m.employee_id
     ORDER BY m.movement_id DESC
     LIMIT 8"
)->fetchAll();

$alerts = [];

$probationSoon = db()->query(
    "SELECT employee_id, employee_code, first_name, last_name, probation_end_date
     FROM hr_employee
     WHERE employment_status = 'PROBATION'
       AND probation_end_date IS NOT NULL
       AND probation_end_date BETWEEN CURRENT_DATE AND CURRENT_DATE + interval '14 days'
     ORDER BY probation_end_date"
)->fetchAll();
foreach ($probationSoon as $row) {
    $alerts[] = [
        'text' => e($row['employee_code']) . ' — ' . e($row['first_name'] . ' ' . $row['last_name']) . ' ใกล้ครบกำหนดทดลองงาน (' . e($row['probation_end_date']) . ')',
        'employee_id' => $row['employee_id'],
    ];
}

$certExpiring = db()->query(
    "SELECT c.employee_id, e.employee_code, e.first_name, e.last_name, c.certificate_name, c.expire_date
     FROM hr_employee_certificate c
     JOIN hr_employee e ON e.employee_id = c.employee_id
     WHERE c.expire_date BETWEEN CURRENT_DATE AND CURRENT_DATE + interval '30 days'
     ORDER BY c.expire_date"
)->fetchAll();
foreach ($certExpiring as $row) {
    $alerts[] = [
        'text' => e($row['employee_code']) . ' — ใบรับรอง "' . e($row['certificate_name']) . '" ใกล้หมดอายุ (' . e($row['expire_date']) . ')',
        'employee_id' => $row['employee_id'],
    ];
}

$noEducation = db()->query(
    "SELECT e.employee_id, e.employee_code, e.first_name, e.last_name
     FROM hr_employee e
     WHERE e.employment_status != 'RESIGNED'
       AND NOT EXISTS (SELECT 1 FROM hr_employee_education ed WHERE ed.employee_id = e.employee_id)
     ORDER BY e.employee_code
     LIMIT 10"
)->fetchAll();
foreach ($noEducation as $row) {
    $alerts[] = [
        'text' => e($row['employee_code']) . ' — ' . e($row['first_name'] . ' ' . $row['last_name']) . ' ยังไม่มีข้อมูลประวัติการศึกษา',
        'employee_id' => $row['employee_id'],
    ];
}

$incompleteProfile = db()->query(
    "SELECT employee_id, employee_code, first_name, last_name
     FROM hr_employee
     WHERE employment_status != 'RESIGNED'
       AND (email IS NULL OR phone IS NULL OR address IS NULL OR birth_date IS NULL OR national_id IS NULL)
     ORDER BY employee_code
     LIMIT 10"
)->fetchAll();
foreach ($incompleteProfile as $row) {
    $alerts[] = [
        'text' => e($row['employee_code']) . ' — ' . e($row['first_name'] . ' ' . $row['last_name']) . ' ข้อมูลโปรไฟล์ยังไม่ครบถ้วน',
        'employee_id' => $row['employee_id'],
    ];
}

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$breadcrumb = ['Dashboard'];

require_once __DIR__ . '/../includes/header.php';

$maxDept = max(array_merge(array_column($byDepartment, 'total'), [1]));
$maxPos = max(array_merge(array_column($byPosition, 'total'), [1]));

$deptColors = ['#3b82f6', '#22c55e', '#f59e0b', '#a855f7', '#06b6d4', '#ef4444', '#84cc16'];
?>

<h1><span class="page-title-icon icon-blue"><i class="bi bi-speedometer2"></i></span>Dashboard HRMS</h1>
<p class="page-description">
    ยินดีต้อนรับ, <?= e($_SESSION['username'] ?? '') ?>
</p>

<div class="stat-grid">
    <div class="stat-card stat-card--blue">
        <div class="stat-card__icon"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="stat-card__label">พนักงานทั้งหมด</div>
            <div class="stat-card__value"><?= $totalEmployees ?></div>
        </div>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-card__icon"><i class="bi bi-check-circle-fill"></i></div>
        <div>
            <div class="stat-card__label">Active</div>
            <div class="stat-card__value"><?= $activeCount ?></div>
        </div>
    </div>
    <div class="stat-card stat-card--amber">
        <div class="stat-card__icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="stat-card__label">Probation</div>
            <div class="stat-card__value"><?= $probationCount ?></div>
        </div>
    </div>
    <div class="stat-card stat-card--teal">
        <div class="stat-card__icon"><i class="bi bi-person-plus-fill"></i></div>
        <div>
            <div class="stat-card__label">เข้าใหม่เดือนนี้</div>
            <div class="stat-card__value"><?= $newHiresCount ?></div>
        </div>
    </div>
    <div class="stat-card stat-card--red">
        <div class="stat-card__icon"><i class="bi bi-person-dash-fill"></i></div>
        <div>
            <div class="stat-card__label">ลาออกเดือนนี้</div>
            <div class="stat-card__value"><?= $resignedThisMonthCount ?></div>
        </div>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <h2 class="section-title"><i class="bi bi-building"></i> พนักงานแยกตามแผนก</h2>
        <?php if (empty($byDepartment)): ?>
            <p class="text-muted">ยังไม่มีข้อมูล</p>
        <?php endif; ?>
        <?php foreach ($byDepartment as $i => $row): ?>
            <div class="bar-row">
                <div class="bar-row__label"><?= e($row['department_name_th']) ?></div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?= (int) $row['total'] === 0 ? 0 : round(((int) $row['total'] / $maxDept) * 100) ?>%; background:<?= $deptColors[$i % count($deptColors)] ?>;"></div>
                </div>
                <div class="bar-row__value"><?= (int) $row['total'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2 class="section-title"><i class="bi bi-briefcase"></i> พนักงานแยกตามตำแหน่ง</h2>
        <?php if (empty($byPosition)): ?>
            <p class="text-muted">ยังไม่มีข้อมูล</p>
        <?php endif; ?>
        <?php foreach ($byPosition as $i => $row): ?>
            <div class="bar-row">
                <div class="bar-row__label"><?= e($row['position_name_th']) ?></div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?= (int) $row['total'] === 0 ? 0 : round(((int) $row['total'] / $maxPos) * 100) ?>%; background:<?= $deptColors[$i % count($deptColors)] ?>;"></div>
                </div>
                <div class="bar-row__value"><?= (int) $row['total'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <div class="page-toolbar">
            <h2 class="section-title" style="margin:0;"><i class="bi bi-arrow-left-right"></i> Movement ล่าสุด</h2>
            <a href="employee_movements.php" class="btn btn-sm">ดูทั้งหมด</a>
        </div>
        <?php if (empty($recentMovements)): ?>
            <p class="text-muted">ยังไม่มีข้อมูล</p>
        <?php else: ?>
            <ul class="timeline-list">
                <?php foreach ($recentMovements as $m): ?>
                    <li>
                        <span class="badge badge-blue"><?= e(movementTypeLabel($m['movement_type'])) ?></span>
                        <?= e($m['employee_code']) ?> — <?= e($m['first_name'] . ' ' . $m['last_name']) ?>
                        <span class="text-muted">(<?= e($m['effective_date']) ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="section-title"><i class="bi bi-exclamation-triangle-fill"></i> HR Alerts</h2>
        <?php if (empty($alerts)): ?>
            <p class="text-muted"><i class="bi bi-check-circle"></i> ไม่มีรายการที่ต้องแจ้งเตือน</p>
        <?php else: ?>
            <ul class="alert-list">
                <?php foreach (array_slice($alerts, 0, 12) as $alert): ?>
                    <li>
                        <a href="employee_detail.php?id=<?= (int) $alert['employee_id'] ?>">
                            <span class="alert-list__icon"><i class="bi bi-exclamation-triangle-fill"></i></span> <?= $alert['text'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
