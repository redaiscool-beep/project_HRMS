<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'HRMS';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | HRMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="app-header">
    <div class="app-header__title">
        <span class="app-header__logo"><i class="bi bi-building"></i></span>
        HRMS
    </div>
    <div class="app-header__user">
        <span class="app-header__avatar"><?= strtoupper(substr($_SESSION['username'] ?? '?', 0, 1)) ?></span>
        <?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
    </div>
</header>

<div class="app-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="app-content">
        <div class="page-nav">
            <?php if (($activeMenu ?? '') !== 'dashboard'): ?>
            <a href="dashboard.php" class="btn btn-sm" data-back="<?= htmlspecialchars($backUrl ?? 'dashboard.php', ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            <?php endif; ?>
            <?php if (!empty($breadcrumb)): ?>
            <nav class="breadcrumb">
                <?php foreach ($breadcrumb as $i => $item): ?>
                    <?= $i > 0 ? ' &raquo; ' : '' ?><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>
        </div>
        <?php if (function_exists('renderFlash')) {
            renderFlash();
        } ?>
