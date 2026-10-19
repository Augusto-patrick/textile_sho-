<?php
// Expects $pageTitle to be set by the including page.
if (!isset($pageTitle)) { $pageTitle = 'Textile Shop Manager'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> | Textile Shop Manager</title>
<link rel="stylesheet" href="/textile_shop/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-area">
        <header class="topbar">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <div class="topbar-user">
                <?php if (isset($_SESSION['full_name'])): ?>
                    <span><?= htmlspecialchars($_SESSION['full_name']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</span>
                    <a href="/textile_shop/profile.php" class="btn-link">My Account</a>
                    <a href="/textile_shop/logout.php" class="btn-link">Logout</a>
                <?php endif; ?>
            </div>
        </header>
        <main class="content">
