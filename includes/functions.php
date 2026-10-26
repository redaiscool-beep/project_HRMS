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

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function renderFlash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $class = $flash['type'] === 'success' ? 'alert-success' : 'alert-error';
    ?>
    <div class="alert <?= $class ?>"><?= e($flash['message']) ?></div>
    <?php
}

/**
 * Master Data ที่ลบได้ พร้อมรายการ table/column ที่อ้างอิงถึง
 * ชื่อ table/column มาจาก whitelist นี้เท่านั้น ไม่รับจาก user input
 */
function masterDeleteConfig(): array
{
    return [
        'department' => [
            'table' => 'hr_department',
            'pk' => 'department_id',
            'label' => 'แผนก',
            'references' => [
                ['hr_employee', 'department_id', 'พนักงาน'],
                ['hr_employee_movement', 'old_department_id', 'ประวัติ Movement'],
                ['hr_employee_movement', 'new_department_id', 'ประวัติ Movement'],
                ['hr_department', 'parent_department_id', 'แผนกย่อย'],
            ],
        ],
        'position' => [
            'table' => 'hr_position',
            'pk' => 'position_id',
            'label' => 'ตำแหน่ง',
            'references' => [
                ['hr_employee', 'position_id', 'พนักงาน'],
                ['hr_employee_movement', 'old_position_id', 'ประวัติ Movement'],
                ['hr_employee_movement', 'new_position_id', 'ประวัติ Movement'],
            ],
        ],
        'employee_type' => [
            'table' => 'hr_employee_type',
            'pk' => 'employee_type_id',
            'label' => 'ประเภทพนักงาน',
            'references' => [
                ['hr_employee', 'employee_type_id', 'พนักงาน'],
                ['hr_employee_movement', 'old_employee_type_id', 'ประวัติ Movement'],
                ['hr_employee_movement', 'new_employee_type_id', 'ประวัติ Movement'],
            ],
        ],
    ];
}

/**
 * Hard delete เฉพาะ Master Data ที่ไม่มีข้อมูลอ้างอิง
 * ถ้ามีการอ้างอิงจะไม่ลบ และแนะนำให้ปิดใช้งาน (is_active = false) แทน
 */
function deleteMasterRecord(string $type, int $id): void
{
    $config = masterDeleteConfig()[$type] ?? null;

    if ($config === null || $id <= 0) {
        setFlash('error', 'ไม่พบข้อมูลที่ต้องการลบ');
        return;
    }

    $usedBy = [];
    foreach ($config['references'] as [$refTable, $refColumn, $refLabel]) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM {$refTable} WHERE {$refColumn} = :id");
        $stmt->execute(['id' => $id]);
        $count = (int) $stmt->fetchColumn();

        if ($count > 0) {
            $usedBy[$refLabel] = ($usedBy[$refLabel] ?? 0) + $count;
        }
    }

    if ($usedBy) {
        $parts = [];
        foreach ($usedBy as $label => $count) {
            $parts[] = $label . ' ' . $count . ' รายการ';
        }
        setFlash(
            'error',
            'ไม่สามารถลบ' . $config['label'] . 'นี้ได้ เนื่องจากถูกใช้งานอยู่ (' . implode(', ', $parts) . ') กรุณาเปลี่ยนเป็น "ปิดใช้งาน" แทน'
        );
        return;
    }

    $stmt = db()->prepare("DELETE FROM {$config['table']} WHERE {$config['pk']} = :id");
    $stmt->execute(['id' => $id]);

    setFlash(
        $stmt->rowCount() > 0 ? 'success' : 'error',
        $stmt->rowCount() > 0 ? 'ลบ' . $config['label'] . 'เรียบร้อยแล้ว' : 'ไม่พบข้อมูลที่ต้องการลบ'
    );
}

function renderDeleteButton(int $id, string $name): void
{
    ?>
    <form method="post" class="inline-form"
          onsubmit="return confirm(<?= e(json_encode('ยืนยันการลบ "' . $name . '" ?', JSON_UNESCAPED_UNICODE)) ?>);">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="link-danger"><i class="bi bi-trash"></i> ลบ</button>
    </form>
    <?php
}
