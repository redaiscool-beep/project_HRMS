<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$employeeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = db()->prepare(
    'SELECT e.*, d.department_name_th, p.position_name_th, t.employee_type_name
     FROM hr_employee e
     LEFT JOIN hr_department d ON d.department_id = e.department_id
     LEFT JOIN hr_position p ON p.position_id = e.position_id
     LEFT JOIN hr_employee_type t ON t.employee_type_id = e.employee_type_id
     WHERE e.employee_id = :id'
);
$stmt->execute(['id' => $employeeId]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: employees.php');
    exit;
}

function countFor(string $table, int $employeeId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE employee_id = :id");
    $stmt->execute(['id' => $employeeId]);

    return (int) $stmt->fetchColumn();
}

$educationCount = countFor('hr_employee_education', $employeeId);
$workHistoryCount = countFor('hr_employee_work_history', $employeeId);
$certificateCount = countFor('hr_employee_certificate', $employeeId);
$skillCount = countFor('hr_employee_skill', $employeeId);

$generalComplete = $employee['email'] && $employee['phone'] && $employee['address']
    && $employee['birth_date'] && $employee['national_id'];

$completenessItems = [
    'ข้อมูลทั่วไปครบถ้วน' => (bool) $generalComplete,
    'ประวัติการศึกษา' => $educationCount > 0,
    'ประวัติการทำงาน' => $workHistoryCount > 0,
    'ใบรับรอง/Certificate' => $certificateCount > 0,
    'ทักษะ/Skill' => $skillCount > 0,
];
$completenessScore = (int) round((count(array_filter($completenessItems)) / count($completenessItems)) * 100);

$movementStmt = db()->prepare(
    'SELECT movement_type, effective_date, remark
     FROM hr_employee_movement
     WHERE employee_id = :id
     ORDER BY effective_date DESC, movement_id DESC
     LIMIT 5'
);
$movementStmt->execute(['id' => $employeeId]);
$recentMovements = $movementStmt->fetchAll();

$alerts = [];
if (!$generalComplete) {
    $alerts[] = 'ข้อมูลทั่วไปยังไม่ครบถ้วน (อีเมล/เบอร์โทร/ที่อยู่/วันเกิด/เลขบัตรประชาชน)';
}
if ($educationCount === 0) {
    $alerts[] = 'ยังไม่มีข้อมูลประวัติการศึกษา';
}
if ($employee['employment_status'] === 'PROBATION' && $employee['probation_end_date']) {
    $daysLeft = (int) ((strtotime($employee['probation_end_date']) - strtotime('today')) / 86400);
    if ($daysLeft <= 14 && $daysLeft >= 0) {
        $alerts[] = "ใกล้ครบกำหนดทดลองงานในอีก {$daysLeft} วัน";
    }
}

$fullName = trim(($employee['prefix'] ?? '') . $employee['first_name'] . ' ' . $employee['last_name']);

$pageTitle = $fullName;
$activeMenu = 'employees';
$breadcrumb = ['Employee', 'Employee List', $fullName];

require_once __DIR__ . '/../includes/header.php';
?>

<h1><span class="page-title-icon icon-blue"><i class="bi bi-person-badge"></i></span><?= e($fullName) ?></h1>
<p class="page-description"><?= e($employee['employee_code']) ?> &middot; <?= employmentStatusBadge($employee['employment_status']) ?></p>

<?php renderEmployeeTabs($employeeId, 'general'); ?>

<?php foreach ($alerts as $alert): ?>
    <div class="alert alert-error"><?= e($alert) ?></div>
<?php endforeach; ?>

<div class="two-col">
    <div class="card">
        <div class="page-toolbar">
            <h2 class="section-title" style="margin:0;">ข้อมูลทั่วไป</h2>
            <a href="employee_form.php?id=<?= $employeeId ?>" class="btn btn-sm">แก้ไขข้อมูล</a>
        </div>

        <table class="data-table" style="box-shadow:none;">
            <tbody>
                <tr><td>ชื่อ (อังกฤษ)</td><td><?= e(trim(($employee['first_name_en'] ?? '') . ' ' . ($employee['last_name_en'] ?? ''))) ?: '-' ?></td></tr>
                <tr><td>เพศ</td><td><?= e($employee['gender']) ?: '-' ?></td></tr>
                <tr><td>วันเกิด</td><td><?= e($employee['birth_date']) ?: '-' ?></td></tr>
                <tr><td>เลขบัตรประชาชน</td><td><?= e($employee['national_id']) ?: '-' ?></td></tr>
                <tr><td>อีเมล</td><td><?= e($employee['email']) ?: '-' ?></td></tr>
                <tr><td>เบอร์โทร</td><td><?= e($employee['phone']) ?: '-' ?></td></tr>
                <tr><td>ที่อยู่</td><td><?= e($employee['address']) ?: '-' ?></td></tr>
                <tr><td>แผนก</td><td><?= e($employee['department_name_th']) ?: '-' ?></td></tr>
                <tr><td>ตำแหน่ง</td><td><?= e($employee['position_name_th']) ?: '-' ?></td></tr>
                <tr><td>ประเภทพนักงาน</td><td><?= e($employee['employee_type_name']) ?: '-' ?></td></tr>
                <tr><td>วันที่เริ่มงาน</td><td><?= e($employee['hire_date']) ?: '-' ?></td></tr>
                <tr><td>วันสิ้นสุดทดลองงาน</td><td><?= e($employee['probation_end_date']) ?: '-' ?></td></tr>
                <?php if ($employee['resign_date']): ?>
                <tr><td>วันที่ลาออก</td><td><?= e($employee['resign_date']) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div>
        <div class="card">
            <h2 class="section-title">Profile Completeness</h2>
            <div style="background:var(--gray-100); border-radius:999px; height:10px; overflow:hidden; margin-bottom:10px;">
                <div style="background:var(--blue-600); height:100%; width:<?= $completenessScore ?>%;"></div>
            </div>
            <p style="font-weight:700; color:var(--blue-700); margin:0 0 12px;"><?= $completenessScore ?>%</p>
            <ul style="margin:0; padding-left:18px; font-size:14px; color:var(--gray-600);">
                <?php foreach ($completenessItems as $label => $done): ?>
                    <li style="color: <?= $done ? 'var(--green-700)' : 'var(--gray-500)' ?>;">
                        <i class="bi <?= $done ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i> <?= e($label) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card">
            <div class="page-toolbar">
                <h2 class="section-title" style="margin:0;">Employee Timeline</h2>
                <a href="employee_movements.php?employee_id=<?= $employeeId ?>" class="btn btn-sm">ดูทั้งหมด</a>
            </div>
            <?php if (empty($recentMovements)): ?>
                <p class="text-muted">ยังไม่มีประวัติการเปลี่ยนแปลง</p>
            <?php else: ?>
                <ul style="margin:0; padding-left:18px; font-size:14px;">
                    <?php foreach ($recentMovements as $movement): ?>
                        <li style="margin-bottom:8px;">
                            <strong><?= e(movementTypeLabel($movement['movement_type'])) ?></strong>
                            — <?= e($movement['effective_date']) ?>
                            <?php if ($movement['remark']): ?>
                                <br><span class="text-muted"><?= e($movement['remark']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
