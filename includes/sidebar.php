<?php

declare(strict_types=1);

$activeMenu = $activeMenu ?? '';

function isActiveMenu(string $key, string $activeMenu): string
{
    return $key === $activeMenu ? ' class="active"' : '';
}
?>
<aside class="app-sidebar">
    <nav>
        <ul>
            <li<?= isActiveMenu('dashboard', $activeMenu) ?>>
                <a href="dashboard.php"><span class="menu-icon icon-blue"><i class="bi bi-speedometer2"></i></span> Dashboard</a>
            </li>

            <li class="menu-group">Master Data</li>
            <li<?= isActiveMenu('departments', $activeMenu) ?>>
                <a href="departments.php"><span class="menu-icon icon-orange"><i class="bi bi-building"></i></span> Department</a>
            </li>
            <li<?= isActiveMenu('positions', $activeMenu) ?>>
                <a href="positions.php"><span class="menu-icon icon-purple"><i class="bi bi-briefcase"></i></span> Position</a>
            </li>
            <li<?= isActiveMenu('employee_types', $activeMenu) ?>>
                <a href="employee_types.php"><span class="menu-icon icon-pink"><i class="bi bi-tags"></i></span> Employee Type</a>
            </li>

            <li class="menu-group">Employee</li>
            <li<?= isActiveMenu('employees', $activeMenu) ?>>
                <a href="employees.php"><span class="menu-icon icon-blue"><i class="bi bi-people"></i></span> Employee List</a>
            </li>
            <li<?= isActiveMenu('movements', $activeMenu) ?>>
                <a href="employee_movements.php"><span class="menu-icon icon-green"><i class="bi bi-arrow-left-right"></i></span> Movement</a>
            </li>
            <li<?= isActiveMenu('trainings', $activeMenu) ?>>
                <a href="employee_training.php"><span class="menu-icon icon-indigo"><i class="bi bi-mortarboard"></i></span> Training</a>
            </li>

            <li class="menu-group">Reports</li>
            <li<?= isActiveMenu('report_employee', $activeMenu) ?>>
                <a href="reports.php?type=employee"><span class="menu-icon icon-blue"><i class="bi bi-file-earmark-text"></i></span> Employee Report</a>
            </li>
            <li<?= isActiveMenu('report_movement', $activeMenu) ?>>
                <a href="reports.php?type=movement"><span class="menu-icon icon-green"><i class="bi bi-graph-up-arrow"></i></span> Movement Report</a>
            </li>
            <li<?= isActiveMenu('report_training', $activeMenu) ?>>
                <a href="reports.php?type=training"><span class="menu-icon icon-indigo"><i class="bi bi-journal-bookmark"></i></span> Training Report</a>
            </li>

            <li class="menu-group">Help</li>
            <li>
                <a href="../manual/index.php"><span class="menu-icon icon-green"><i class="bi bi-book"></i></span> คู่มือการใช้งาน</a>
            </li>
        </ul>
    </nav>
</aside>
