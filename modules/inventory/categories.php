<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireLogin();

$pageTitle = "Categories";
include __DIR__ . "/../../includes/header.php";
?>
<div class="card">
    <h3 style="margin-top:0;">Categories</h3>
    <p style="color: var(--text-muted);">This module is scaffolded and ready to build next — layout, nav, and DB connection are already wired in.</p>
</div>
<?php include __DIR__ . "/../../includes/footer.php"; ?>
