<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function paginationParams(int $totalRows, int $defaultPerPage = 10): array
{
    $allowedPerPage = [10, 20, 50];

    $perPage = (int) ($_GET['per_page'] ?? $defaultPerPage);
    if (!in_array($perPage, $allowedPerPage, true)) {
        $perPage = $defaultPerPage;
    }

    $totalPages = max(1, (int) ceil($totalRows / $perPage));

    $page = (int) ($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    return [
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
        'allowedPerPage' => $allowedPerPage,
    ];
}

function activeBadge(bool $isActive): string
{
    return $isActive
        ? '<span class="badge badge-green">ใช้งาน</span>'
        : '<span class="badge badge-gray">ปิดใช้งาน</span>';
}

function employmentStatusBadge(string $status): string
{
    $map = [
        'ACTIVE' => ['ทำงานอยู่', 'badge-green'],
        'PROBATION' => ['ทดลองงาน', 'badge-amber'],
        'RESIGNED' => ['ลาออกแล้ว', 'badge-red'],
        'INACTIVE' => ['ไม่ใช้งาน', 'badge-gray'],
    ];
    [$label, $class] = $map[$status] ?? [$status, 'badge-gray'];

    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

function leaveStatusBadge(string $status): string
{
    $map = [
        'DRAFT' => ['ร่าง', 'badge-gray'],
        'SUBMITTED' => ['รออนุมัติ', 'badge-amber'],
        'APPROVED' => ['อนุมัติแล้ว', 'badge-green'],
        'REJECTED' => ['ไม่อนุมัติ', 'badge-red'],
        'CANCELLED' => ['ยกเลิก', 'badge-gray'],
    ];
    [$label, $class] = $map[$status] ?? [$status, 'badge-gray'];

    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

function movementTypeLabel(string $type): string
{
    $map = [
        'HIRE' => 'บรรจุใหม่',
        'PASS_PROBATION' => 'ผ่านทดลองงาน',
        'TRANSFER' => 'ย้ายแผนก',
        'POSITION_CHANGE' => 'เปลี่ยนตำแหน่ง',
        'PROMOTION' => 'เลื่อนตำแหน่ง',
        'STATUS_CHANGE' => 'เปลี่ยนสถานะ',
        'RESIGN' => 'ลาออก',
    ];

    return $map[$type] ?? $type;
}

function renderEmployeeTabs(int $employeeId, string $activeTab): void
{
    $tabs = [
        'general' => ['employee_detail.php?id=' . $employeeId, 'General'],
        'education' => ['employee_education.php?employee_id=' . $employeeId, 'Education'],
        'work_history' => ['employee_work_history.php?employee_id=' . $employeeId, 'Work History'],
        'movement' => ['employee_movements.php?employee_id=' . $employeeId, 'Movement'],
        'training' => ['employee_training.php?employee_id=' . $employeeId, 'Training'],
        'certificate' => ['employee_certificates.php?employee_id=' . $employeeId, 'Certificate'],
        'skill' => ['employee_skills.php?employee_id=' . $employeeId, 'Skill'],
    ];
    ?>
    <div class="tabs">
        <?php foreach ($tabs as $key => $tab): ?>
            <a href="<?= e($tab[0]) ?>" class="<?= $key === $activeTab ? 'active' : '' ?>"><?= e($tab[1]) ?></a>
        <?php endforeach; ?>
    </div>
    <?php
}

function fetchEmployeeOrRedirect(int $employeeId): array
{
    $stmt = db()->prepare('SELECT employee_id, employee_code, first_name, last_name FROM hr_employee WHERE employee_id = :id');
    $stmt->execute(['id' => $employeeId]);
    $employee = $stmt->fetch();

    if (!$employee) {
        header('Location: employees.php');
        exit;
    }

    return $employee;
}

function isValidDateOrEmpty(string $value): bool
{
    if ($value === '') {
        return true;
    }

    $d = DateTime::createFromFormat('Y-m-d', $value);

    return $d && $d->format('Y-m-d') === $value;
}

function renderPagination(array $pagination): void
{
    $queryWithoutPage = $_GET;
    unset($queryWithoutPage['page']);
    ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
            <a href="?<?= http_build_query(array_merge($queryWithoutPage, ['page' => $p])) ?>"
               class="<?= $p === $pagination['page'] ? 'active' : '' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>

        <form method="get" class="per-page-form">
            <?php foreach ($queryWithoutPage as $key => $value): ?>
                <?php if ($key !== 'per_page'): ?>
                    <input type="hidden" name="<?= e((string) $key) ?>" value="<?= e((string) $value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <select name="per_page" onchange="this.form.submit()">
                <?php foreach ($pagination['allowedPerPage'] as $option): ?>
                    <option value="<?= $option ?>" <?= $option === $pagination['perPage'] ? 'selected' : '' ?>>
                        <?= $option ?> / หน้า
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php
}
